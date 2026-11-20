<?php

namespace App\Services\Telegram;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Jobs\SendTelegramMessage;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\Student;

/**
 * Sends Telegram notifications to linked students/members, always through a logged, queued job.
 */
class Notifier
{
    /**
     * @param  callable(string $locale): string|string  $html  message text, or a closure receiving the recipient locale
     */
    public function notify(Student|Member $person, NotificationType $type, callable|string $html, ?Announcement $announcement = null, ?array $buttons = null, ?string $photoPath = null): ?NotificationLog
    {
        if (! $person->isTelegramReachable()) {
            return null;
        }

        $text = is_callable($html) ? $html($person->locale ?: 'am') : $html;

        $log = NotificationLog::create([
            'announcement_id' => $announcement?->id,
            'notifiable_type' => $person->getMorphClass(),
            'notifiable_id' => $person->getKey(),
            'chat_id' => $person->telegram_chat_id,
            'type' => $type,
            'status' => NotificationStatus::Queued,
        ]);

        SendTelegramMessage::dispatch($log->id, $text, $buttons, $photoPath)->afterCommit();

        return $log;
    }
}
