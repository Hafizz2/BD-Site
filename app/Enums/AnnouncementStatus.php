<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AnnouncementStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'draft' => 'gray', 'scheduled' => 'info', 'sending' => 'warning', 'sent' => 'success', 'failed' => 'danger',
            default => 'gray',
        };
    }
}
