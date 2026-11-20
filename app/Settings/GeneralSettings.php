<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public ?string $site_name_am;

    public ?string $tagline;

    public ?string $tagline_am;

    public ?string $meta_description;

    public ?string $meta_description_am;

    public ?string $footer_about;

    public ?string $footer_about_am;

    public ?string $phone;

    public ?string $phone_secondary;

    public ?string $email;

    public ?string $address;

    public ?string $address_am;

    public ?string $office_hours;

    public ?string $office_hours_am;

    public ?string $map_embed_url;

    public ?string $telegram_url;

    public ?string $facebook_url;

    public ?string $youtube_url;

    public ?string $tiktok_url;

    public ?string $instagram_url;

    public ?string $logo_path;

    public ?string $og_image_path;

    public ?string $verse_arabic;

    public ?string $verse_translation;

    public ?string $verse_translation_am;

    public ?string $verse_reference;

    public ?string $verse_reference_am;

    public ?string $copyright;

    public ?string $copyright_am;

    public static function group(): string
    {
        return 'general';
    }

    public function t(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $am = $field.'_am';
        if ($locale === 'am' && property_exists($this, $am) && filled($this->{$am})) {
            return $this->{$am};
        }

        return $this->{$field} ?? null;
    }

    public function socials(): array
    {
        return array_filter([
            'telegram' => $this->telegram_url,
            'facebook' => $this->facebook_url,
            'youtube' => $this->youtube_url,
            'tiktok' => $this->tiktok_url,
            'instagram' => $this->instagram_url,
        ]);
    }
}
