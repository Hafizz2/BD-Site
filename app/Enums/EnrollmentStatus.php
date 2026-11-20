<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EnrollmentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Dropped = 'dropped';
    case Completed = 'completed';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'dropped' => 'gray', 'completed' => 'primary',
            default => 'gray',
        };
    }
}
