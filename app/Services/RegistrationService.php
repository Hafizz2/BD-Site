<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\NotificationType;
use App\Enums\RegistrationStatus;
use App\Enums\StudentSource;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use App\Services\Telegram\Notifier;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    /** Student fields copied from a registration. */
    public const FIELDS = [
        'full_name', 'gender', 'date_of_birth', 'phone', 'alt_phone', 'email', 'sub_city', 'address',
        'school', 'grade_or_year', 'guardian_name', 'guardian_phone', 'guardian_relation',
        'guardian_consent', 'consent_at', 'photo_consent', 'locale',
    ];

    public function __construct(private readonly Notifier $notifier) {}

    /**
     * Approve: match an existing student by normalized phone (or create one), enroll them, notify.
     */
    public function approve(Registration $registration, ?User $by = null): Student
    {
        $student = DB::transaction(function () use ($registration, $by) {
            $registration = Registration::whereKey($registration->id)->lockForUpdate()->firstOrFail();

            $student = $registration->student ?? $registration->matchingStudent();

            if ($student) {
                // Only fill blanks on an existing student — never overwrite data an admin curated.
                foreach (self::FIELDS as $f) {
                    $new = $registration->{$f};
                    if (blank($student->{$f}) && filled($new)) {
                        $student->{$f} = $new;
                    }
                }
                if ($registration->guardian_consent) {
                    $student->guardian_consent = true;
                    $student->consent_at ??= $registration->consent_at;
                }
                $student->photo_consent = $student->photo_consent || $registration->photo_consent;
                $student->save();
            } else {
                $student = Student::create(
                    collect(self::FIELDS)->mapWithKeys(fn ($f) => [$f => $registration->{$f}])->all() + [
                        'notes' => $registration->notes,
                        'status' => StudentStatus::Active,
                        'source' => StudentSource::WebForm,
                        'registered_at' => $registration->created_at,
                    ]
                );
            }

            // Carry over a Telegram link made from the thank-you page.
            if ($registration->telegram_chat_id && ! $student->telegram_chat_id) {
                $student->linkTelegram($registration->telegram_chat_id);
            }

            $enrollment = Enrollment::firstOrNew(['student_id' => $student->id, 'batch_id' => $registration->batch_id]);
            $enrollment->status = EnrollmentStatus::Approved;
            $enrollment->enrolled_at ??= now();
            $enrollment->save();

            $registration->forceFill([
                'status' => RegistrationStatus::Approved,
                'student_id' => $student->id,
                'reviewed_by' => $by?->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ])->save();

            return $student;
        });

        $this->notifyStudent($registration->fresh('batch.program'), $student, 'bot.registration_approved');

        return $student;
    }

    public function reject(Registration $registration, ?User $by, ?string $reason): void
    {
        $registration->forceFill([
            'status' => RegistrationStatus::Rejected,
            'reviewed_by' => $by?->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        if ($student = $registration->student ?? $registration->matchingStudent()) {
            $this->notifyStudent($registration, $student, 'bot.registration_rejected', $reason);
        }
    }

    /**
     * One entry point for the admin's "Update status" button.
     * Accepted → creates/links the student and enrolls them (moving away later keeps the student).
     */
    public function setStatus(Registration $registration, RegistrationStatus $status, ?User $by = null, ?string $note = null): void
    {
        match ($status) {
            RegistrationStatus::Approved => $this->approve($registration, $by),
            RegistrationStatus::Interview => $this->inviteToInterview($registration, $by, $note),
            RegistrationStatus::Waitlisted => $this->waitlist($registration, $by),
            RegistrationStatus::Rejected => $this->reject($registration, $by, $note),
            RegistrationStatus::Pending => $registration->forceFill(['status' => RegistrationStatus::Pending])->save(),
        };
    }

    /** Reviewed and invited to an interview; approve after the interview to make them a student. */
    public function inviteToInterview(Registration $registration, ?User $by, ?string $note = null): void
    {
        $registration->forceFill([
            'status' => RegistrationStatus::Interview,
            'reviewed_by' => $by?->id,
            'reviewed_at' => now(),
        ])->save();

        if ($student = $registration->student ?? $registration->matchingStudent()) {
            $this->notifyStudent($registration, $student, 'bot.registration_interview', $note);
        }
    }

    public function waitlist(Registration $registration, ?User $by): void
    {
        $registration->forceFill([
            'status' => RegistrationStatus::Waitlisted,
            'reviewed_by' => $by?->id,
            'reviewed_at' => now(),
        ])->save();

        if ($student = $registration->student ?? $registration->matchingStudent()) {
            $this->notifyStudent($registration, $student, 'bot.registration_waitlisted');
        }
    }

    private function notifyStudent(Registration $registration, Student $student, string $key, ?string $reason = null): void
    {
        $batch = $registration->batch;
        $this->notifier->notify($student, NotificationType::RegistrationUpdate, fn (string $locale) => __($key, [
            'batch' => e(trim(($batch?->program?->t('name', $locale) ?? '').' · '.($batch?->name ?? ''), ' ·')),
            'reason' => e((string) $reason),
        ], $locale));
    }
}
