<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Male = 'male';
    case Female = 'female';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'male' => 'info', 'female' => 'danger',
            default => 'gray',
        };
    }
}
