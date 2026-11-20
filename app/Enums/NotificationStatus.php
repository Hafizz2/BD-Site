<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NotificationStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case BlockedBot = 'blocked_bot';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'queued' => 'gray', 'sent' => 'success', 'failed' => 'danger', 'blocked_bot' => 'warning',
            default => 'gray',
        };
    }
}
