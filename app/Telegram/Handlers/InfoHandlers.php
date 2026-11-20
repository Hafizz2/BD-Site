<?php

namespace App\Telegram\Handlers;

use App\Enums\EnrollmentStatus;
use App\Jobs\SendAnnouncement;
use App\Models\Announcement;
use App\Models\Student;
use App\Telegram\BotContext;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class InfoHandlers
{
    public function help(Nutgram $bot): void
    {
        $bot->sendMessage(text: __('bot.help'), parse_mode: ParseMode::HTML);
    }

    public function balance(Nutgram $bot): void
    {
        $person = $this->requireLinked($bot);
        if (! $person) {
            return;
        }

        $locale = app()->getLocale();
        $lines = [__('bot.balance_title'), ''];
        $dues = $person->outstandingDues()->with('feePlan')->orderBy('due_date')->get();

        if ($dues->isEmpty()) {
            $lines[] = __('bot.balance_none');
        } else {
            $total = '0';
            foreach ($dues as $due) {
                $total = bcadd($total, $due->balance(), 2);
                $lines[] = __('bot.balance_line', [
                    'label' => e($due->label),
                    'amount' => number_format((float) $due->balance(), 2),
                    'date' => $due->due_date->format('d M Y'),
                    'status' => __('enums.DueStatus.'.$due->status->value, [], $locale),
                ]);
            }
            $lines[] = '';
            $lines[] = __('bot.balance_total', ['amount' => number_format((float) $total, 2)]);
        }

        $payments = $person->payments()->latest('paid_at')->limit(5)->get();
        if ($payments->isNotEmpty()) {
            $lines[] = '';
            $lines[] = __('bot.recent_payments');
            foreach ($payments as $p) {
                $lines[] = __('bot.payment_line', [
                    'date' => $p->paid_at?->format('d M Y') ?? '—',
                    'amount' => number_format((float) $p->amount, 2),
                    'method' => e($p->method->getLabel()),
                    'status' => __('enums.PaymentStatus.'.$p->status->value, [], $locale),
                ]);
            }
        }

        $bot->sendMessage(text: implode("\n", $lines), parse_mode: ParseMode::HTML);
    }

    public function announcements(Nutgram $bot): void
    {
        $locale = app()->getLocale();
        $items = Announcement::query()
            ->where('status', 'sent')
            ->latest('sent_at')
            ->limit(5)
            ->get();

        if ($items->isEmpty()) {
            $bot->sendMessage(__('bot.announcements_none'));

            return;
        }

        $bot->sendMessage(text: __('bot.announcements_title'), parse_mode: ParseMode::HTML);
        foreach ($items as $a) {
            $html = SendAnnouncement::renderFor($a, $locale);
            $bot->sendMessage(text: $html."\n\n<i>".$a->sent_at?->format('d M Y').'</i>', parse_mode: ParseMode::HTML);
        }
    }

    public function myBatch(Nutgram $bot): void
    {
        $person = $this->requireLinked($bot);
        if (! $person) {
            return;
        }
        $student = $person instanceof Student ? $person : BotContext::people($bot)['student'];
        $locale = app()->getLocale();

        $enrollment = $student?->enrollments()
            ->with('batch.program')
            ->where('status', EnrollmentStatus::Approved->value)
            ->whereHas('batch', fn ($b) => $b->where('status', '!=', 'completed'))
            ->latest('enrolled_at')
            ->first();

        if (! $enrollment) {
            $bot->sendMessage(__('bot.mybatch_none'));

            return;
        }

        $batch = $enrollment->batch;
        $lines = [__('bot.mybatch_line', ['batch' => e($batch->name), 'program' => e((string) $batch->program?->t('name', $locale))])];
        if ($batch->start_date) {
            $lines[] = __('bot.mybatch_dates', ['start' => $batch->start_date->format('d M Y'), 'end' => $batch->end_date?->format('d M Y') ?? '—']);
        }
        $schedule = $locale === 'am' ? ($batch->schedule_note_am ?: $batch->schedule_note) : $batch->schedule_note;
        if ($schedule) {
            $lines[] = __('bot.mybatch_schedule', ['schedule' => e($schedule)]);
        }

        $bot->sendMessage(text: implode("\n", $lines), parse_mode: ParseMode::HTML);
    }

    private function requireLinked(Nutgram $bot)
    {
        $person = BotContext::person($bot);
        if (! $person) {
            $bot->sendMessage(__('bot.not_linked'));
        }

        return $person;
    }
}
