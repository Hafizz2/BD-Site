<?php

namespace App\Models;

use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Due extends Model
{
    use HasFactory, HasPublicUlid, LogsAllActivity, SoftDeletes;

    protected $guarded = ['id', 'ulid'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'status' => DueStatus::class,
            'reminder_before_sent_at' => 'datetime',
            'reminder_due_sent_at' => 'datetime',
            'last_overdue_reminder_at' => 'datetime',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function feePlan(): BelongsTo
    {
        return $this->belongsTo(FeePlan::class)->withTrashed();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function waiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    public function scopeOutstanding(Builder $q): Builder
    {
        return $q->whereIn('status', DueStatus::outstanding());
    }

    public function balance(): string
    {
        return bcsub((string) $this->amount, (string) $this->paid_amount, 2);
    }

    /**
     * Recalculate paid_amount and status from approved payments. Idempotent.
     */
    public function recalculate(): void
    {
        if ($this->status === DueStatus::Waived) {
            return;
        }

        $paid = (string) $this->payments()->where('status', PaymentStatus::Approved->value)->sum('amount');
        $paid = number_format((float) $paid, 2, '.', '');

        if (bccomp($paid, (string) $this->amount, 2) >= 0) {
            $status = DueStatus::Paid;
        } elseif (bccomp($paid, '0', 2) > 0) {
            $status = DueStatus::PartiallyPaid;
        } elseif ($this->due_date->isPast() && ! $this->due_date->isToday()) {
            $status = DueStatus::Overdue;
        } else {
            $status = DueStatus::Unpaid;
        }

        $this->forceFill(['paid_amount' => $paid, 'status' => $status])->save();
    }

    public function getLabelAttribute(): string
    {
        return trim(($this->description ?: $this->feePlan?->name).' · '.$this->period, ' ·');
    }
}
