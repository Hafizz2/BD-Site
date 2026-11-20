<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProgramType: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case CollegeTraining = 'college_training';
    case Madrasa = 'madrasa';
    case KidsTraining = 'kids_training';
    case Charity = 'charity';
    case Event = 'event';
    case Trip = 'trip';
    case DigitalDawah = 'digital_dawah';
    case Other = 'other';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'college_training' => 'primary', 'madrasa' => 'success', 'charity' => 'warning',
            default => 'gray',
        };
    }
}
