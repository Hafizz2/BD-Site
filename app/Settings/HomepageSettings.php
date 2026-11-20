<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Everything on the home page that isn't a list of records.
 */
class HomepageSettings extends Settings
{
    // Hero
    public ?string $hero_eyebrow;

    public ?string $hero_eyebrow_am;

    public string $hero_title;

    public ?string $hero_title_highlight;

    public ?string $hero_title_am;

    public ?string $hero_lead;

    public ?string $hero_lead_am;

    public ?string $hero_primary_label;

    public ?string $hero_primary_label_am;

    public ?string $hero_primary_url;

    public ?string $hero_secondary_label;

    public ?string $hero_secondary_label_am;

    public ?string $hero_secondary_url;

    public bool $show_stats;

    public bool $show_verse;

    /** About cards: [{title,title_am,text,text_am}] */
    public array $about_cards;

    /** Value pills: [{label,label_am}] */
    public array $pillars;

    /**
     * Section headings + toggles keyed by section:
     * about, services, goals, journey, articles, events, gallery, testimonials, team.
     * Each: {enabled, eyebrow, eyebrow_am, title, title_am, intro, intro_am, limit}
     */
    public array $sections;

    // Call to action band
    public bool $show_cta;

    public ?string $cta_title;

    public ?string $cta_title_am;

    public ?string $cta_text;

    public ?string $cta_text_am;

    public ?string $cta_primary_label;

    public ?string $cta_primary_label_am;

    public ?string $cta_primary_url;

    public ?string $cta_secondary_label;

    public ?string $cta_secondary_label_am;

    public ?string $cta_secondary_url;

    public static function group(): string
    {
        return 'homepage';
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

    /** Section helper: section('services', 'title') respecting the locale. */
    public function section(string $key, string $field, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();
        $s = $this->sections[$key] ?? [];
        if ($locale === 'am' && filled($s[$field.'_am'] ?? null)) {
            return $s[$field.'_am'];
        }

        return $s[$field] ?? null;
    }

    public function enabled(string $key): bool
    {
        return (bool) ($this->sections[$key]['enabled'] ?? false);
    }
}
