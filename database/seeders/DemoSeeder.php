<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Enums\ExpenseCategory;
use App\Enums\FeeAppliesTo;
use App\Enums\FeeFrequency;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubmittedVia;
use App\Models\Batch;
use App\Models\Donation;
use App\Models\Due;
use App\Models\Enrollment;
use App\Models\Expense;
use App\Models\FeePlan;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\DuesGenerator;
use Illuminate\Database\Seeder;

/**
 * Fake PEOPLE and MONEY for trying the system on your own computer — local environment only:
 * students, registrations, fee plans, dues, payments, expenses, donations, extra members.
 * (Programs, batches and website content come from SampleContentSeeder.)
 * Remove with `php artisan demo:cleanup`.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SampleContentSeeder::class); // needs the sample programs & batches

        if (Student::count() >= 40) {
            return; // already seeded
        }

        $admin = User::where('email', config('app.admin.email'))->first();
        $college = Program::where('slug', 'college-trainings')->firstOrFail();
        $batch = fn (string $name) => Batch::where('name', $name)->firstOrFail();
        [$b4, $b5, $b6, $m1, $k1] = [$batch('Batch 4'), $batch('Batch 5'), $batch('Batch 6'), $batch('Madrasa Batch 2'), $batch('Kids Term 1')];

        // ---------- Fee plans ----------
        $feeB5 = FeePlan::firstOrCreate(['name' => 'Batch 5 training fee'], ['amount' => '600.00', 'frequency' => FeeFrequency::PerBatch, 'applies_to' => FeeAppliesTo::Batch, 'batch_id' => $b5->id, 'due_in_days' => 14, 'is_active' => true]);
        FeePlan::firstOrCreate(['name' => 'Madrasa monthly fee'], ['amount' => '150.00', 'frequency' => FeeFrequency::Monthly, 'applies_to' => FeeAppliesTo::Batch, 'batch_id' => $m1->id, 'due_day' => 10, 'is_active' => true]);
        $contrib = FeePlan::firstOrCreate(['name' => 'Member monthly contribution'], ['amount' => '200.00', 'frequency' => FeeFrequency::Monthly, 'applies_to' => FeeAppliesTo::MemberContribution, 'due_day' => 5, 'is_active' => true]);

        // ---------- Students & enrollments (~50) ----------
        $students = Student::factory()->count(36)->create();
        $minors = Student::factory()->minor()->count(10)->create();
        Student::factory()->linked()->count(4)->create()->each(fn ($s) => $students->push($s));

        $students->each(function (Student $s, $i) use ($b4, $b5) {
            Enrollment::create(['student_id' => $s->id, 'batch_id' => $i % 3 === 0 ? $b4->id : $b5->id, 'status' => $i % 3 === 0 ? EnrollmentStatus::Completed : EnrollmentStatus::Approved, 'enrolled_at' => now()->subWeeks(rand(2, 20))]);
        });
        $minors->each(fn ($s) => Enrollment::create(['student_id' => $s->id, 'batch_id' => $m1->id, 'status' => EnrollmentStatus::Approved, 'enrolled_at' => now()->subWeeks(3)]));

        // ---------- Members (confidential — never shown on the website) ----------
        $team = [
            ['Abdulhafiz Jemal', 'Coordinator', \App\Enums\MemberTeam::Leadership, true],
            ['Hanan Kemal', 'Finance Head', \App\Enums\MemberTeam::Finance, true],
            ['Yusuf Ali', 'Lead Teacher', \App\Enums\MemberTeam::Teaching, true],
            ['Meryem Ahmed', 'Media Lead', \App\Enums\MemberTeam::Media, false],
            ['Nasir Hussein', "Da'wah Coordinator", \App\Enums\MemberTeam::Dawah, true],
            ['Rahma Nuru', 'Logistics', \App\Enums\MemberTeam::Logistics, false],
        ];
        foreach ($team as $i => [$n, $r, $t, $gives]) {
            Member::factory()->create(['full_name' => $n, 'role_title' => $r, 'team' => $t, 'show_on_website' => false, 'sort' => $i,
                'monthly_contribution_plan_id' => $gives ? $contrib->id : null]);
        }
        Member::factory()->count(6)->create(['monthly_contribution_plan_id' => $contrib->id, 'show_on_website' => false]);

        // ---------- Dues & payments ----------
        $gen = app(DuesGenerator::class);
        $gen->forPlan($feeB5);
        $gen->generateMonthly(now()->subMonth());
        $gen->generateMonthly(now());
        $gen->markOverdue();

        Due::with('payable')->inRandomOrder()->take(30)->get()->each(function (Due $due, $i) use ($admin) {
            $status = match (true) {
                $i < 18 => PaymentStatus::Approved,
                $i < 24 => PaymentStatus::PendingReview,
                default => PaymentStatus::Rejected,
            };
            $amount = $i % 5 === 0 ? bcdiv((string) $due->amount, '2', 2) : (string) $due->amount;
            Payment::create([
                'due_id' => $due->id, 'payable_type' => $due->payable_type, 'payable_id' => $due->payable_id,
                'amount' => $amount, 'method' => collect(PaymentMethod::cases())->random(),
                'transaction_ref' => strtoupper(\Illuminate\Support\Str::random(10)), 'paid_at' => now()->subDays(rand(0, 40)),
                'status' => $status, 'submitted_via' => SubmittedVia::Admin,
                'reviewed_by' => $status === PaymentStatus::PendingReview ? null : $admin?->id,
                'reviewed_at' => $status === PaymentStatus::PendingReview ? null : now()->subDays(rand(0, 30)),
                'rejection_reason' => $status === PaymentStatus::Rejected ? 'Receipt is not readable' : null,
            ]);
            $due->recalculate();
        });

        // ---------- Expenses & donations (last 6 months) ----------
        foreach (range(0, 5) as $m) {
            foreach (collect(ExpenseCategory::cases())->random(3) as $cat) {
                Expense::create(['category' => $cat, 'amount' => rand(5, 60) * 100, 'spent_at' => now()->subMonths($m)->day(rand(1, 27)), 'description' => ucfirst(str_replace('_', ' ', $cat->value)).' for '.now()->subMonths($m)->format('F'), 'program_id' => $college->id, 'recorded_by' => $admin?->id]);
            }
            Donation::create(['donor_name' => $m % 2 ? null : 'Anonymous supporter', 'is_anonymous' => (bool) ($m % 2), 'amount' => rand(10, 80) * 100, 'method' => PaymentMethod::Telebirr, 'purpose' => 'General support', 'received_at' => now()->subMonths($m)->day(rand(1, 27)), 'recorded_by' => $admin?->id]);
        }

        // ---------- Registrations ----------
        Registration::factory()->count(6)->create(['batch_id' => $b6->id]);
        Registration::factory()->count(2)->create(['batch_id' => $k1->id, 'is_minor' => true, 'guardian_name' => 'Kemal Ali', 'guardian_phone' => '+251911000111', 'guardian_consent' => true, 'consent_at' => now(), 'date_of_birth' => now()->subYears(11)->toDateString()]);
        // One registration whose phone already exists → shows the "Already a student" warning.
        Registration::factory()->create(['batch_id' => $b6->id, 'full_name' => $students->first()->full_name, 'phone' => $students->first()->phone]);
    }
}
