<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DueStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Waived = 'waived';
    case Overdue = 'overdue';

    /** @return list<string> statuses that still need money */
    public static function outstanding(): array
    {
        return [self::Unpaid->value, self::PartiallyPaid->value, self::Overdue->value];
    }

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'unpaid' => 'warning', 'partially_paid' => 'info', 'paid' => 'success', 'waived' => 'gray', 'overdue' => 'danger',
            default => 'gray',
        };
    }
}
