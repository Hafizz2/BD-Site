<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MemberTeam: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Leadership = 'leadership';
    case Finance = 'finance';
    case Media = 'media';
    case Teaching = 'teaching';
    case Dawah = 'dawah';
    case Logistics = 'logistics';
    case Charity = 'charity';
    case Other = 'other';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'finance' => 'warning', 'media' => 'info', 'teaching' => 'success', 'dawah' => 'primary',
            default => 'gray',
        };
    }
}
