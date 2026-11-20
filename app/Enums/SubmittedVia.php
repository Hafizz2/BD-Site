<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubmittedVia: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Admin = 'admin';
    case Telegram = 'telegram';
    case Web = 'web';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'telegram' => 'info', 'web' => 'primary',
            default => 'gray',
        };
    }
}
