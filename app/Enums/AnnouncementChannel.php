<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AnnouncementChannel: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case TelegramBot = 'telegram_bot';
    case TelegramChannel = 'telegram_channel';
    case Website = 'website';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'telegram_bot' => 'info', 'telegram_channel' => 'primary', 'website' => 'success',
            default => 'gray',
        };
    }
}
