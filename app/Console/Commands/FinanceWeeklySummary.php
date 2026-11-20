<?php

namespace App\Console\Commands;

use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use App\Models\Due;
use App\Models\Payment;
use App\Services\Telegram\TelegramGateway;
use App\Settings\BotSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FinanceWeeklySummary extends Command
{
    protected $signature = 'finance:weekly-summary {--force : Send now regardless of the configured day/time}';

    protected $description = 'Post the weekly finance summary to the finance Telegram group';

    public function handle(BotSettings $bot, TelegramGateway $telegram): int
    {
        if (! $bot->finance_group_chat_id || ! $bot->enabled) {
            $this->warn('No finance group configured.');

            return self::SUCCESS;
        }

        $now = now('Africa/Addis_Ababa');
        if (! $this->option('force')) {
            if (! $bot->weekly_summary_enabled || strtolower($now->englishDayOfWeek) !== strtolower($bot->weekly_summary_day)
                || $now->format('H') !== substr($bot->weekly_summary_time, 0, 2)) {
                return self::SUCCESS;
            }
            // Once per week.
            if (! Cache::add('finance-weekly-'.$now->format('o-W'), true, now()->addDays(6))) {
                return self::SUCCESS;
            }
        }

        $from = $now->copy()->subDays(7);
        $collected = Payment::where('status', PaymentStatus::Approved->value)->where('reviewed_at', '>=', $from)->sum('amount');
        $count = Payment::where('status', PaymentStatus::Approved->value)->where('reviewed_at', '>=', $from)->count();
        $outstanding = Due::whereIn('status', DueStatus::outstanding())->selectRaw('COALESCE(SUM(amount - paid_amount),0) as s')->value('s');
        $overdue = Due::where('status', DueStatus::Overdue->value)->count();
        $pending = Payment::where('status', PaymentStatus::PendingReview->value)->count();

        $text = implode("\n", [
            '📊 <b>Weekly finance summary</b>',
            '<i>'.$from->format('d M').' – '.$now->format('d M Y').'</i>',
            '',
            '✅ Collected: <b>'.number_format((float) $collected, 2).' ETB</b> ('.$count.' payments)',
            '⏳ Outstanding: <b>'.number_format((float) $outstanding, 2).' ETB</b>',
            '⚠️ Overdue dues: <b>'.$overdue.'</b>',
            '🧾 Receipts waiting for review: <b>'.$pending.'</b>',
            '',
            '<a href="'.url('/admin').'">Open the admin panel</a>',
        ]);

        $telegram->sendMessage($bot->finance_group_chat_id, $text);
        $this->info('Summary sent.');

        return self::SUCCESS;
    }
}
