<?php

namespace App\Services\Finance;

use App\Enums\DueStatus;
use App\Enums\NotificationType;
use App\Models\Due;
use App\Services\Telegram\Notifier;
use App\Services\Telegram\QuietHours;
use App\Settings\ReminderSettings;
use Carbon\CarbonInterface;

/**
 * Due reminders: N days before, on the due date, and overdue every X days up to Y times.
 * Never during quiet hours or Jumu'ah. Each stage is stamped on the due, so it's retry-safe.
 */
class ReminderService
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly QuietHours $quiet,
        private readonly ReminderSettings $settings,
    ) {}

    /** @return array{before:int,due:int,overdue:int,skipped_quiet:bool} */
    public function run(?CarbonInterface $now = null): array
    {
        $now ??= now();
        $result = ['before' => 0, 'due' => 0, 'overdue' => 0, 'skipped_quiet' => false];

        if (! $this->settings->enabled) {
            return $result;
        }
        if ($this->quiet->isQuiet($now)) {
            $result['skipped_quiet'] = true;

            return $result;
        }

        $today = $now->copy()->setTimezone('Africa/Addis_Ababa')->startOfDay();

        // 1) N days before
        Due::with(['payable', 'feePlan'])
            ->whereIn('status', [DueStatus::Unpaid->value, DueStatus::PartiallyPaid->value])
            ->whereNull('reminder_before_sent_at')
            ->whereDate('due_date', '>', $today->toDateString())
            ->whereDate('due_date', '<=', $today->copy()->addDays($this->settings->days_before)->toDateString())
            ->each(function (Due $due) use (&$result, $now) {
                $this->send($due, 'bot.reminder_before');
                $due->forceFill(['reminder_before_sent_at' => $now])->saveQuietly();
                $result['before']++;
            });

        // 2) On the due date
        Due::with(['payable', 'feePlan'])
            ->whereIn('status', [DueStatus::Unpaid->value, DueStatus::PartiallyPaid->value])
            ->whereNull('reminder_due_sent_at')
            ->whereDate('due_date', $today->toDateString())
            ->each(function (Due $due) use (&$result, $now) {
                $this->send($due, 'bot.reminder_due');
                $due->forceFill(['reminder_due_sent_at' => $now])->saveQuietly();
                $result['due']++;
            });

        // 3) Overdue: every X days, at most Y times
        Due::with(['payable', 'feePlan'])
            ->whereIn('status', DueStatus::outstanding())
            ->whereDate('due_date', '<', $today->toDateString())
            ->where('overdue_reminders_count', '<', $this->settings->overdue_max_reminders)
            ->where(fn ($q) => $q->whereNull('last_overdue_reminder_at')
                ->orWhere('last_overdue_reminder_at', '<=', $now->copy()->subDays($this->settings->overdue_interval_days)))
            ->each(function (Due $due) use (&$result, $now) {
                $this->send($due, 'bot.reminder_overdue');
                $due->forceFill([
                    'overdue_reminders_count' => $due->overdue_reminders_count + 1,
                    'last_overdue_reminder_at' => $now,
                    'status' => $due->status === DueStatus::Unpaid ? DueStatus::Overdue : $due->status,
                ])->saveQuietly();
                $result['overdue']++;
            });

        return $result;
    }

    private function send(Due $due, string $key): void
    {
        if (! $due->payable) {
            return;
        }

        $this->notifier->notify($due->payable, NotificationType::DueReminder, fn (string $locale) => __($key, [
            'label' => e($due->label),
            'amount' => number_format((float) $due->balance(), 2),
            'date' => $due->due_date->format('d M Y'),
        ], $locale));
    }
}
