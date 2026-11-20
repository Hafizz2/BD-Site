<?php

namespace App\Services\Finance;

use App\Enums\DueStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\FeeAppliesTo;
use App\Enums\FeeFrequency;
use App\Models\Due;
use App\Models\Enrollment;
use App\Models\FeePlan;
use App\Models\Member;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

/**
 * Creates dues from fee plans. Idempotent: the (payable, fee_plan, period) unique key
 * guarantees running it twice never creates duplicates.
 */
class DuesGenerator
{
    /** @return int number of dues created */
    public function generateMonthly(?CarbonInterface $month = null): int
    {
        $month = Carbon::instance($month ?? now())->startOfMonth();
        $period = $month->format('Y-m');
        $created = 0;

        FeePlan::query()
            ->where('is_active', true)
            ->where('frequency', FeeFrequency::Monthly->value)
            ->get()
            ->each(function (FeePlan $plan) use ($month, $period, &$created) {
                $dueDate = $month->copy()->day(min(max((int) ($plan->due_day ?: 10), 1), $month->daysInMonth));

                if ($plan->applies_to === FeeAppliesTo::MemberContribution) {
                    Member::where('is_active', true)
                        ->where('monthly_contribution_plan_id', $plan->id)
                        ->each(function (Member $m) use ($plan, $period, $dueDate, &$created) {
                            $created += (int) $this->createDue($m, $plan, $period, $dueDate);
                        });

                    return;
                }

                // Monthly batch fee → every approved student in that batch while the batch runs.
                if ($plan->batch_id) {
                    $batch = $plan->batch;
                    if (! $batch || ($batch->end_date && $batch->end_date->lt($month)) || ($batch->start_date && $batch->start_date->gt($month->copy()->endOfMonth()))) {
                        return;
                    }
                    Enrollment::with('student')
                        ->where('batch_id', $plan->batch_id)
                        ->where('status', EnrollmentStatus::Approved->value)
                        ->each(function (Enrollment $e) use ($plan, $period, $dueDate, &$created) {
                            if ($e->student) {
                                $created += (int) $this->createDue($e->student, $plan, $period, $dueDate);
                            }
                        });
                }
            });

        return $created;
    }

    /** Per-batch / one-time batch plans when an enrollment becomes approved. */
    public function forEnrollment(Enrollment $enrollment): int
    {
        if ($enrollment->status !== EnrollmentStatus::Approved || ! $enrollment->student) {
            return 0;
        }

        $created = 0;
        FeePlan::where('is_active', true)
            ->where('batch_id', $enrollment->batch_id)
            ->where('applies_to', FeeAppliesTo::Batch->value)
            ->whereIn('frequency', [FeeFrequency::PerBatch->value, FeeFrequency::OneTime->value])
            ->get()
            ->each(function (FeePlan $plan) use ($enrollment, &$created) {
                $dueDate = now()->addDays($plan->due_in_days ?: 14);
                $created += (int) $this->createDue($enrollment->student, $plan, 'batch-'.$enrollment->batch_id, $dueDate);
            });

        return $created;
    }

    /** Generate per-batch dues for everyone already approved in a batch (used when a plan is added later). */
    public function forPlan(FeePlan $plan): int
    {
        if (! $plan->is_active || ! $plan->batch_id || $plan->frequency === FeeFrequency::Monthly) {
            return $plan->frequency === FeeFrequency::Monthly ? $this->generateMonthly() : 0;
        }

        $created = 0;
        Enrollment::with('student')->where('batch_id', $plan->batch_id)->where('status', EnrollmentStatus::Approved->value)
            ->each(function (Enrollment $e) use ($plan, &$created) {
                if ($e->student) {
                    $created += (int) $this->createDue($e->student, $plan, 'batch-'.$plan->batch_id, now()->addDays($plan->due_in_days ?: 14));
                }
            });

        return $created;
    }

    /** Flag unpaid dues whose date has passed. */
    public function markOverdue(): int
    {
        return Due::where('status', DueStatus::Unpaid->value)
            ->whereDate('due_date', '<', today())
            ->update(['status' => DueStatus::Overdue->value]);
    }

    private function createDue(Student|Member|Model $payable, FeePlan $plan, string $period, CarbonInterface $dueDate): bool
    {
        $attributes = [
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'fee_plan_id' => $plan->id,
            'period' => $period,
        ];

        if (Due::withTrashed()->where($attributes)->exists()) {
            return false;
        }

        try {
            Due::create($attributes + [
                'amount' => $plan->amount,
                'description' => $plan->name,
                'due_date' => $dueDate->toDateString(),
                'status' => DueStatus::Unpaid,
            ]);
        } catch (UniqueConstraintViolationException) {
            return false; // created concurrently
        }

        return true;
    }
}
