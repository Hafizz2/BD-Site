<?php

namespace App\Models\Concerns;

use App\Models\NotificationLog;
use App\Models\TelegramLinkToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared Telegram behaviour for Students and Members.
 */
trait HasTelegram
{
    public function telegramLinkTokens(): MorphMany
    {
        return $this->morphMany(TelegramLinkToken::class, 'linkable');
    }

    public function notificationLogs(): MorphMany
    {
        return $this->morphMany(NotificationLog::class, 'notifiable');
    }

    public function isTelegramReachable(): bool
    {
        return filled($this->telegram_chat_id) && $this->telegram_blocked_at === null;
    }

    public function scopeTelegramReachable(Builder $query): Builder
    {
        return $query->whereNotNull('telegram_chat_id')->whereNull('telegram_blocked_at');
    }

    public function telegramDeepLink(): ?string
    {
        return app(\App\Services\Telegram\TelegramLinker::class)->deepLinkFor($this);
    }

    public function linkTelegram(string|int $chatId): void
    {
        $this->forceFill([
            'telegram_chat_id' => (string) $chatId,
            'telegram_linked_at' => now(),
            'telegram_blocked_at' => null,
        ])->save();
    }
}
