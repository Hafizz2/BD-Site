<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NotificationType: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Announcement = 'announcement';
    case DueReminder = 'due_reminder';
    case PaymentApproved = 'payment_approved';
    case PaymentRejected = 'payment_rejected';
    case PaymentSubmitted = 'payment_submitted';
    case RegistrationUpdate = 'registration_update';
    case DirectMessage = 'direct_message';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'announcement' => 'primary', 'due_reminder' => 'warning', 'payment_approved' => 'success', 'payment_rejected' => 'danger',
            default => 'gray',
        };
    }
}
