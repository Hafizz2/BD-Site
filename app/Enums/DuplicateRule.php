<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DuplicateRule: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Skip = 'skip';
    case UpdateEmpty = 'update_empty';
    case Overwrite = 'overwrite';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'skip' => 'gray', 'update_empty' => 'info', 'overwrite' => 'warning',
            default => 'gray',
        };
    }
}
