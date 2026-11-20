<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StudentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Active = 'active';
    case Inactive = 'inactive';
    case Graduated = 'graduated';
    case Blocked = 'blocked';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'active' => 'success', 'inactive' => 'gray', 'graduated' => 'primary', 'blocked' => 'danger',
            default => 'gray',
        };
    }
}
