<?php

namespace App\Console\Commands;

use App\Services\Finance\ReminderService;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'dues:send-reminders';

    protected $description = 'Send Telegram due reminders (before, on the day, overdue) outside quiet hours';

    public function handle(ReminderService $reminders): int
    {
        $r = $reminders->run();

        $r['skipped_quiet']
            ? $this->info('Quiet hours — no reminders sent.')
            : $this->info("Reminders queued: {$r['before']} upcoming, {$r['due']} due today, {$r['overdue']} overdue.");

        return self::SUCCESS;
    }
}
