<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Donation;
use App\Models\Due;
use App\Models\Enrollment;
use App\Models\Expense;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes the fake people + money data created by the demo seeder, keeping
 * settings, roles, users, programs/batches and website content.
 */
class CleanupDemoData extends Command
{
    protected $signature = 'demo:cleanup {--force : Run without confirmation} {--all-content : Also remove demo programs, batches and website content}';

    protected $description = 'Delete demo students, members, registrations and finance data before going live';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This permanently deletes ALL students, members, registrations, dues, payments, expenses, donations and notifications. Continue?')) {
            return self::FAILURE;
        }

        DB::transaction(function () {
            NotificationLog::query()->delete();
            Payment::withTrashed()->get()->each->forceDelete();
            Due::withTrashed()->forceDelete();
            Expense::withTrashed()->get()->each->forceDelete();
            Donation::withTrashed()->forceDelete();
            Enrollment::query()->delete();
            Registration::withTrashed()->forceDelete();
            Announcement::withTrashed()->get()->each->forceDelete();
            Member::withTrashed()->get()->each->forceDelete();
            DB::table('fee_plans')->delete();
            Student::withTrashed()->forceDelete();
            DB::table('telegram_link_tokens')->delete();
            DB::table('imports')->delete();

            if ($this->option('all-content')) {
                foreach (['articles', 'events', 'gallery_albums', 'testimonials', 'batches', 'programs'] as $t) {
                    DB::table($t)->delete();
                }
            }
        });

        $this->info('Demo data removed.');

        return self::SUCCESS;
    }
}
