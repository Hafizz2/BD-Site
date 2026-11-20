<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Telebirr = 'telebirr';
    case CbeBirr = 'cbe_birr';
    case Cbe = 'cbe';
    case Awash = 'awash';
    case Abyssinia = 'abyssinia';
    case Cash = 'cash';
    case Other = 'other';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'telebirr' => 'info', 'cash' => 'success',
            default => 'gray',
        };
    }
}
