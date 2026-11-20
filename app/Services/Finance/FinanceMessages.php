<?php

namespace App\Services\Finance;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\PhoneNumber;

/**
 * Builds the (English) messages posted to the finance team group.
 */
class FinanceMessages
{
    public static function paymentCaption(Payment $p): string
    {
        $payer = $p->payable;
        $type = $payer instanceof \App\Models\Member ? 'Member' : 'Student';
        $lines = [
            '💰 <b>New payment for review</b>',
            '',
            '<b>Payer:</b> '.e($payer?->full_name ?? '—')." ({$type})",
            '<b>Phone:</b> '.e(PhoneNumber::format($payer?->phone) ?? '—'),
            '<b>Amount:</b> '.number_format((float) $p->amount, 2).' ETB',
            '<b>For:</b> '.e($p->due?->label ?? 'General payment'),
        ];
        if ($p->due) {
            $lines[] = '<b>Due balance:</b> '.number_format((float) $p->due->balance(), 2).' ETB of '.number_format((float) $p->due->amount, 2);
        }
        $lines[] = '<b>Method:</b> '.e($p->method?->getLabel() ?? '—');
        $lines[] = '<b>Ref:</b> <code>'.e($p->transaction_ref ?: '—').'</code>';
        $lines[] = '<b>Via:</b> '.e($p->submitted_via?->getLabel() ?? '—').' · '.$p->created_at?->timezone('Africa/Addis_Ababa')->format('d M Y H:i');
        $lines[] = '<b>ID:</b> <code>'.$p->ulid.'</code>';

        if ($p->status !== PaymentStatus::PendingReview) {
            $who = e($p->reviewer?->name ?? 'finance');
            $lines[] = '';
            $lines[] = $p->status === PaymentStatus::Approved
                ? "✅ <b>Approved</b> by {$who}"
                : "❌ <b>Rejected</b> by {$who}: ".e((string) $p->rejection_reason);
        }

        return implode("\n", $lines);
    }

    public static function reviewButtons(Payment $p): ?array
    {
        if ($p->status !== PaymentStatus::PendingReview) {
            return null;
        }

        return [[
            ['text' => '✅ Approve', 'callback_data' => 'pay:a:'.$p->ulid],
            ['text' => '❌ Reject', 'callback_data' => 'pay:r:'.$p->ulid],
        ]];
    }
}
