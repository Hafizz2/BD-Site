<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BatchStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'planned' => 'info', 'active' => 'success', 'completed' => 'gray',
            default => 'gray',
        };
    }
}
