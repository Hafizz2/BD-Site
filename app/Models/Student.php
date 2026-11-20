<?php

namespace App\Models;

use App\Enums\DueStatus;
use App\Enums\Gender;
use App\Enums\StudentSource;
use App\Enums\StudentStatus;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Concerns\HasTelegram;
use App\Models\Concerns\LogsAllActivity;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, HasPublicUlid, HasTelegram, LogsAllActivity, SoftDeletes;

    protected $guarded = ['id', 'ulid'];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'status' => StudentStatus::class,
            'source' => StudentSource::class,
            'date_of_birth' => 'date',
            'guardian_consent' => 'boolean',
            'photo_consent' => 'boolean',
            'consent_at' => 'datetime',
            'registered_at' => 'datetime',
            'telegram_linked_at' => 'datetime',
            'telegram_blocked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Student $s) {
            foreach (['phone', 'alt_phone', 'guardian_phone'] as $f) {
                if (filled($s->{$f})) {
                    $s->{$f} = PhoneNumber::normalize($s->{$f}) ?? $s->{$f};
                }
            }
        });
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'enrollments')->withPivot(['status', 'enrolled_at'])->withTimestamps();
    }

    public function latestEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)->latestOfMany();
    }

    public function dues(): MorphMany
    {
        return $this->morphMany(Due::class, 'payable');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function outstandingDues(): MorphMany
    {
        return $this->dues()->whereIn('status', DueStatus::outstanding());
    }

    public function scopeWithUnpaidDues(Builder $q): Builder
    {
        return $q->whereHas('dues', fn ($d) => $d->whereIn('status', DueStatus::outstanding()));
    }

    public function isMinor(): bool
    {
        return $this->date_of_birth !== null && $this->date_of_birth->age < 18;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name;
    }
}
