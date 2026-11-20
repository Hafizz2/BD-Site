<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RegistrationStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Interview = 'interview';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Waitlisted = 'waitlisted';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'pending' => 'warning', 'interview' => 'primary', 'approved' => 'success', 'rejected' => 'danger', 'waitlisted' => 'info',
            default => 'gray',
        };
    }
}
