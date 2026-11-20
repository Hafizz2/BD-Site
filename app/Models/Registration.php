<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Concerns\HasTelegram;
use App\Models\Concerns\LogsAllActivity;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registration extends Model
{
    use HasFactory, HasPublicUlid, HasTelegram, LogsAllActivity, SoftDeletes;

    protected $guarded = ['id', 'ulid'];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'status' => RegistrationStatus::class,
            'date_of_birth' => 'date',
            'is_minor' => 'boolean',
            'guardian_consent' => 'boolean',
            'photo_consent' => 'boolean',
            'consent_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'answers' => 'array',
            'called_at' => 'datetime',
            'telegram_linked_at' => 'datetime',
            'telegram_blocked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Registration $r) {
            foreach (['phone', 'alt_phone', 'guardian_phone'] as $f) {
                if (filled($r->{$f})) {
                    $r->{$f} = PhoneNumber::normalize($r->{$f}) ?? $r->{$f};
                }
            }
        });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** An existing student with the same normalized phone, if any. */
    public function matchingStudent(): ?Student
    {
        return $this->phone ? Student::where('phone', $this->phone)->first() : null;
    }
}
