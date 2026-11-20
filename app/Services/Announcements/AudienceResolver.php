<?php

namespace App\Services\Announcements;

use App\Enums\AnnouncementAudience;
use App\Enums\DueStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Resolves an announcement audience to the people who should receive it.
 */
class AudienceResolver
{
    /** @return array{students:Builder,members:Builder} un-filtered (not yet Telegram-reachable) queries */
    public function queries(AnnouncementAudience $audience, array $filter = []): array
    {
        $none = fn (Builder $q) => $q->whereRaw('1 = 0');
        $students = Student::query()->where('status', '!=', 'blocked');
        $members = Member::query()->where('is_active', true);

        $enrolledIn = fn (Builder $q, array $batchIds) => $q->whereHas('enrollments', fn ($e) => $e
            ->whereIn('batch_id', $batchIds)
            ->whereIn('status', [EnrollmentStatus::Approved->value, EnrollmentStatus::Completed->value]));

        switch ($audience) {
            case AnnouncementAudience::Everyone:
                break;
            case AnnouncementAudience::AllStudents:
                $none($members);
                break;
            case AnnouncementAudience::AllMembers:
                $none($students);
                break;
            case AnnouncementAudience::Program:
                $programIds = array_map('intval', $filter['program_ids'] ?? []);
                $students->whereHas('enrollments', fn ($e) => $e
                    ->whereIn('status', [EnrollmentStatus::Approved->value, EnrollmentStatus::Completed->value])
                    ->whereHas('batch', fn ($b) => $b->whereIn('program_id', $programIds)));
                $none($members);
                break;
            case AnnouncementAudience::Batch:
                $enrolledIn($students, array_map('intval', $filter['batch_ids'] ?? []));
                $none($members);
                break;
            case AnnouncementAudience::Team:
                $members->whereIn('team', $filter['teams'] ?? []);
                $none($students);
                break;
            case AnnouncementAudience::Unpaid:
                $students->whereHas('dues', fn ($d) => $d->whereIn('status', DueStatus::outstanding()));
                $members->whereHas('dues', fn ($d) => $d->whereIn('status', DueStatus::outstanding()));
                break;
            case AnnouncementAudience::Custom:
                $students->whereIn('id', array_map('intval', $filter['student_ids'] ?? []));
                $members->whereIn('id', array_map('intval', $filter['member_ids'] ?? []));
                break;
        }

        return ['students' => $students, 'members' => $members];
    }

    /**
     * Telegram-reachable recipients, de-duplicated by chat id (a person can be both student and member).
     *
     * @return Collection<int,Student|Member>
     */
    public function recipients(Announcement $a): Collection
    {
        ['students' => $s, 'members' => $m] = $this->queries($a->audience, $a->audience_filter ?? []);

        return $s->telegramReachable()->get()
            ->concat($m->telegramReachable()->get())
            ->unique('telegram_chat_id')
            ->values();
    }

    /** @return array{total:int,reachable:int} */
    public function preview(AnnouncementAudience $audience, array $filter = []): array
    {
        ['students' => $s, 'members' => $m] = $this->queries($audience, $filter);
        $total = (clone $s)->count() + (clone $m)->count();
        $chats = (clone $s)->telegramReachable()->pluck('telegram_chat_id')
            ->concat((clone $m)->telegramReachable()->pluck('telegram_chat_id'))
            ->unique()->count();

        return ['total' => $total, 'reachable' => $chats];
    }
}
