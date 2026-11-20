<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StudentSource: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case WebForm = 'web_form';
    case Import = 'import';
    case Manual = 'manual';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'web_form' => 'info', 'import' => 'warning', 'manual' => 'gray',
            default => 'gray',
        };
    }
}
