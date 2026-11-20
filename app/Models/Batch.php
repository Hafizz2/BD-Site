<?php

namespace App\Models;

use App\Enums\BatchSeason;
use App\Enums\BatchStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory, LogsAllActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'season' => BatchSeason::class,
            'status' => BatchStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_open' => 'boolean',
            'form_fields' => 'array',
            'form_settings' => 'array',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments')->withPivot(['status', 'enrolled_at'])->withTimestamps();
    }

    public function approvedStudents(): BelongsToMany
    {
        return $this->students()->wherePivotIn('status', [EnrollmentStatus::Approved->value, EnrollmentStatus::Completed->value]);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function feePlans(): HasMany
    {
        return $this->hasMany(FeePlan::class);
    }

    public function scopeOpenForRegistration(Builder $q): Builder
    {
        return $q->where('registration_open', true)->where('status', '!=', BatchStatus::Completed->value);
    }

    public function getLabelAttribute(): string
    {
        return ($this->program?->name ? $this->program->name.' — ' : '').$this->name;
    }

    public function seatsTaken(): int
    {
        return $this->enrollments()->whereIn('status', [EnrollmentStatus::Approved->value, EnrollmentStatus::Completed->value])->count();
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->seatsTaken() >= $this->capacity;
    }
}
