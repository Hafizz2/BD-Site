<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'pending_review' => 'warning', 'approved' => 'success', 'rejected' => 'danger',
            default => 'gray',
        };
    }
}
