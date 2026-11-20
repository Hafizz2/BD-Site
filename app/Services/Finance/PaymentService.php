<?php

namespace App\Services\Finance;

use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Enums\SubmittedVia;
use App\Models\Due;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\Telegram\Notifier;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(private readonly Notifier $notifier) {}

    /**
     * Record a submitted payment (pending review) and alert the finance group.
     *
     * @param  array{amount:string|float|int,method:string,transaction_ref?:?string,paid_at?:mixed,notes?:?string}  $data
     */
    public function submit(Student|Member $payer, ?Due $due, array $data, SubmittedVia $via, ?string $receiptPath = null, ?User $by = null, bool $deleteReceiptSource = false): Payment
    {
        if ($due && ($due->payable_type !== $payer->getMorphClass() || (int) $due->payable_id !== (int) $payer->getKey())) {
            throw new InvalidArgumentException('Due does not belong to this payer.');
        }

        $amount = number_format((float) $data['amount'], 2, '.', '');
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        $payment = DB::transaction(function () use ($payer, $due, $data, $via, $receiptPath, $by, $amount, $deleteReceiptSource) {
            $payment = Payment::create([
                'due_id' => $due?->id,
                'payable_type' => $payer->getMorphClass(),
                'payable_id' => $payer->getKey(),
                'amount' => $amount,
                'method' => $data['method'],
                'transaction_ref' => $data['transaction_ref'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'status' => PaymentStatus::PendingReview,
                'submitted_via' => $via,
                'submitted_by' => $by?->id,
            ]);

            if ($receiptPath && is_file($receiptPath)) {
                $adder = $payment->addMedia($receiptPath);
                if (! $deleteReceiptSource) {
                    $adder->preservingOriginal();
                }
                $adder->toMediaCollection('receipt');
            }

            return $payment;
        });


        return $payment;
    }

    /** Approve a pending payment. Returns false if it was already reviewed (idempotent). */
    public function approve(Payment $payment, User $by): bool
    {
        $done = DB::transaction(function () use ($payment, $by) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== PaymentStatus::PendingReview) {
                return false;
            }
            $locked->forceFill([
                'status' => PaymentStatus::Approved,
                'reviewed_by' => $by->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ])->save();
            $locked->due?->recalculate();
            $payment->setRawAttributes($locked->getAttributes(), true);

            activity('Payment')->performedOn($locked)->causedBy($by)->event('approved')->log('Payment approved');

            return true;
        });

        if ($done) {
            $this->afterReview($payment->fresh(['due.feePlan', 'payable']));
        }

        return $done;
    }

    public function reject(Payment $payment, User $by, string $reason): bool
    {
        $done = DB::transaction(function () use ($payment, $by, $reason) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== PaymentStatus::PendingReview) {
                return false;
            }
            $locked->forceFill([
                'status' => PaymentStatus::Rejected,
                'reviewed_by' => $by->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ])->save();
            $locked->due?->recalculate();
            $payment->setRawAttributes($locked->getAttributes(), true);

            activity('Payment')->performedOn($locked)->causedBy($by)->event('rejected')->withProperties(['reason' => $reason])->log('Payment rejected');

            return true;
        });

        if ($done) {
            $this->afterReview($payment->fresh(['due.feePlan', 'payable']));
        }

        return $done;
    }

    private function afterReview(Payment $payment): void
    {
        $payer = $payment->payable;
        $label = $payment->due?->label ?? __('Payment');
        $amount = number_format((float) $payment->amount, 2);

        if ($payer) {
            $approved = $payment->status === PaymentStatus::Approved;
            $this->notifier->notify(
                $payer,
                $approved ? NotificationType::PaymentApproved : NotificationType::PaymentRejected,
                fn (string $locale) => __($approved ? 'bot.payment_approved' : 'bot.payment_rejected', [
                    'amount' => $amount,
                    'label' => e($label),
                    'reason' => e((string) $payment->rejection_reason),
                ], $locale),
            );
        }

    }
}
