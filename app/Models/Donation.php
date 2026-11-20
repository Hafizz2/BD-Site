<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use HasFactory, LogsAllActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'received_at' => 'date',
            'is_anonymous' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->recorded_by ??= auth()->id());
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
