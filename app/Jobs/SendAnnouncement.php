<?php

namespace App\Jobs;

use App\Enums\AnnouncementStatus;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Models\Announcement;
use App\Models\NotificationLog;
use App\Services\Announcements\AudienceResolver;
use App\Services\Telegram\Exceptions\RateLimitedException;
use App\Services\Telegram\TelegramGateway;
use App\Settings\BotSettings;
use App\Support\TelegramHtml;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Fans an announcement out to every recipient as individual rate-limited jobs.
 * Retry-safe: recipients that already have a log row are skipped.
 */
class SendAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public int $announcementId) {}

    public function handle(AudienceResolver $resolver, TelegramGateway $telegram, BotSettings $bot): void
    {
        $announcement = DB::transaction(function () {
            $a = Announcement::whereKey($this->announcementId)->lockForUpdate()->first();
            if (! $a || ! in_array($a->status, [AnnouncementStatus::Draft, AnnouncementStatus::Scheduled, AnnouncementStatus::Sending], true)) {
                return null;
            }
            $a->forceFill(['status' => AnnouncementStatus::Sending])->save();

            return $a;
        });

        if (! $announcement) {
            return;
        }

        try {
            $image = $announcement->getFirstMedia('image');
            $photoPath = $image && is_file($image->getPath()) ? $image->getPath() : null;
            $count = 0;

            if ($announcement->hasChannel('telegram_bot')) {
                $already = $announcement->logs()->pluck('chat_id')->flip();

                foreach ($resolver->recipients($announcement) as $person) {
                    if ($already->has($person->telegram_chat_id)) {
                        continue;
                    }
                    $count++;
                    $log = NotificationLog::create([
                        'announcement_id' => $announcement->id,
                        'notifiable_type' => $person->getMorphClass(),
                        'notifiable_id' => $person->getKey(),
                        'chat_id' => $person->telegram_chat_id,
                        'type' => NotificationType::Announcement,
                        'status' => NotificationStatus::Queued,
                    ]);
                    SendTelegramMessage::dispatch($log->id, $this->render($announcement, $person->locale ?: 'am'), null, $photoPath);
                }
            }

            if ($announcement->hasChannel('telegram_channel') && $bot->public_channel_id && ! $announcement->channel_message_id) {
                $html = $this->render($announcement, 'am', bilingual: true);
                try {
                    if ($photoPath && mb_strlen($html) <= 1000) {
                        $id = $telegram->sendFile($bot->public_channel_id, $photoPath, $html);
                    } else {
                        if ($photoPath) {
                            $telegram->sendFile($bot->public_channel_id, $photoPath, '');
                        }
                        $id = $telegram->sendMessage($bot->public_channel_id, $html);
                    }
                    $announcement->forceFill(['channel_message_id' => (string) $id])->save();
                } catch (RateLimitedException $e) {
                    $this->release($e->retryAfter + 1);

                    return;
                }
            }

            $announcement->forceFill([
                'status' => AnnouncementStatus::Sent,
                'sent_at' => $announcement->sent_at ?? now(),
                'recipients_count' => $announcement->logs()->count(),
            ])->save();
        } catch (Throwable $e) {
            $announcement->forceFill(['status' => AnnouncementStatus::Failed])->save();
            throw $e;
        }
    }

    public static function renderFor(Announcement $a, string $locale, bool $bilingual = false): string
    {
        $title = fn ($l) => e((string) $a->t('title', $l));
        $body = fn ($l) => TelegramHtml::fromRich($a->t('body', $l));

        if ($bilingual && filled($a->title_am) && filled($a->body_am)) {
            return "📢 <b>{$title('am')}</b>\n\n{$body('am')}\n\n———\n\n<b>{$title('en')}</b>\n\n{$body('en')}";
        }

        return "📢 <b>{$title($locale)}</b>\n\n{$body($locale)}";
    }

    private function render(Announcement $a, string $locale, bool $bilingual = false): string
    {
        return self::renderFor($a, $locale, $bilingual);
    }
}
