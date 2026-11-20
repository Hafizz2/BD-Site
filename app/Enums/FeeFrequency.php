<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FeeFrequency: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case OneTime = 'one_time';
    case Monthly = 'monthly';
    case PerBatch = 'per_batch';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'monthly' => 'info', 'per_batch' => 'primary',
            default => 'gray',
        };
    }
}
