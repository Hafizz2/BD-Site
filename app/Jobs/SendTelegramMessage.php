<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Services\Telegram\Exceptions\BotBlockedException;
use App\Services\Telegram\Exceptions\RateLimitedException;
use App\Services\Telegram\TelegramGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Sends one Telegram message and records the outcome on its NotificationLog.
 * Retry-safe: a log that is already sent/blocked is never sent again.
 */
class SendTelegramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $maxExceptions = 3;

    public function __construct(
        public int $logId,
        public string $html,
        public ?array $buttons = null,
        public ?string $photoPath = null,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('telegram')];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(6);
    }

    public function handle(TelegramGateway $telegram): void
    {
        $log = NotificationLog::find($this->logId);
        if (! $log || in_array($log->status, [NotificationStatus::Sent, NotificationStatus::BlockedBot], true) || ! $log->chat_id) {
            return;
        }

        $log->increment('attempts');

        try {
            if ($this->photoPath && is_file($this->photoPath)) {
                // Captions are limited to 1024 chars: long texts go as a separate message.
                if (mb_strlen($this->html) > 1000) {
                    $telegram->sendFile($log->chat_id, $this->photoPath, '');
                    $telegram->sendMessage($log->chat_id, $this->html, $this->buttons);
                } else {
                    $telegram->sendFile($log->chat_id, $this->photoPath, $this->html, $this->buttons);
                }
            } else {
                $telegram->sendMessage($log->chat_id, $this->html, $this->buttons);
            }

            $log->forceFill(['status' => NotificationStatus::Sent, 'sent_at' => now(), 'error' => null])->save();
        } catch (RateLimitedException $e) {
            // Respect Telegram's retry_after.
            $this->release($e->retryAfter + 1);
        } catch (BotBlockedException $e) {
            $log->forceFill(['status' => NotificationStatus::BlockedBot, 'error' => $e->getMessage()])->save();
            $log->notifiable?->forceFill(['telegram_blocked_at' => now()])->save();
        } catch (Throwable $e) {
            $log->forceFill(['status' => NotificationStatus::Failed, 'error' => mb_substr($e->getMessage(), 0, 1000)])->save();
            if ($this->attempts() < 3) {
                $this->release(30 * $this->attempts());
            }
        }
    }
}
