<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class RegistrationSettings extends Settings
{
    public bool $open;

    public ?string $closed_message;

    public ?string $closed_message_am;

    public ?string $consent_text;

    public ?string $consent_text_am;

    public ?string $photo_consent_text;

    public ?string $photo_consent_text_am;

    public ?string $thank_you_title;

    public ?string $thank_you_title_am;

    public ?string $thank_you_text;

    public ?string $thank_you_text_am;

    public bool $turnstile_enabled;

    public ?string $turnstile_site_key;

    public static function group(): string
    {
        return 'registration';
    }
}
