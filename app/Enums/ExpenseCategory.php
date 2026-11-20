<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExpenseCategory: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Printing = 'printing';
    case Venue = 'venue';
    case Food = 'food';
    case Transport = 'transport';
    case CharityDistribution = 'charity_distribution';
    case Media = 'media';
    case Other = 'other';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'charity_distribution' => 'success', 'venue' => 'info',
            default => 'gray',
        };
    }
}
