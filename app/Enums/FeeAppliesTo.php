<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FeeAppliesTo: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Batch = 'batch';
    case MemberContribution = 'member_contribution';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'batch' => 'primary', 'member_contribution' => 'warning',
            default => 'gray',
        };
    }
}
