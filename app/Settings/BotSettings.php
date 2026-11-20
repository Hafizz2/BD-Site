<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Telegram bot configuration (the token lives in .env only).
 */
class BotSettings extends Settings
{
    public bool $enabled;

    public ?string $bot_username;

    public ?string $finance_group_chat_id;

    public ?string $public_channel_id;

    public ?string $welcome_message;

    public ?string $welcome_message_am;

    public bool $weekly_summary_enabled;

    public string $weekly_summary_day;

    public string $weekly_summary_time;

    public static function group(): string
    {
        return 'bot';
    }
}
