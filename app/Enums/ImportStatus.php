<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ImportStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'pending' => 'gray', 'processing' => 'warning', 'completed' => 'success', 'failed' => 'danger',
            default => 'gray',
        };
    }
}
