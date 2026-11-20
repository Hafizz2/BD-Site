<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    /** [{method,bank_name,account_name,account_number,note,note_am,show_on_donate}] */
    public array $accounts;

    public ?string $payment_instructions;

    public ?string $payment_instructions_am;

    public ?string $donate_intro;

    public ?string $donate_intro_am;

    public static function group(): string
    {
        return 'payment';
    }

    public function donateAccounts(): array
    {
        return array_values(array_filter($this->accounts, fn ($a) => (bool) ($a['show_on_donate'] ?? true)));
    }
}
