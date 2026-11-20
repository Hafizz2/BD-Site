<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory, LogsAllActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'enrolled_at' => 'datetime',
            'certificate_issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Per-batch fees are generated as soon as an enrollment is approved (idempotent).
        static::saved(function (Enrollment $e) {
            if ($e->status === EnrollmentStatus::Approved && ($e->wasRecentlyCreated || $e->wasChanged('status'))) {
                app(\App\Services\Finance\DuesGenerator::class)->forEnrollment($e);
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
