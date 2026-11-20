<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BatchSeason: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Summer = 'summer';
    case Winter = 'winter';
    case Regular = 'regular';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'summer' => 'warning', 'winter' => 'info',
            default => 'gray',
        };
    }
}
