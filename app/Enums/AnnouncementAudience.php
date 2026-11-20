<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AnnouncementAudience: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Everyone = 'everyone';
    case AllStudents = 'all_students';
    case AllMembers = 'all_members';
    case Program = 'program';
    case Batch = 'batch';
    case Team = 'team';
    case Unpaid = 'unpaid';
    case Custom = 'custom';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'everyone' => 'primary', 'unpaid' => 'danger',
            default => 'gray',
        };
    }
}
