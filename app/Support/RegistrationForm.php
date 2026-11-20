<?php

namespace App\Support;

use App\Models\Batch;
use Illuminate\Support\Str;

/**
 * The registration form of a batch: built-in fields (stored in registration columns) and
 * custom questions (stored in registrations.answers). Admins edit it per batch.
 *
 * A field is ['key', 'type', 'builtin', 'label', 'label_am', 'help', 'required', 'enabled', 'options'].
 */
final class RegistrationForm
{
    /** Built-in field => input type */
    public const BUILTIN = [
        'full_name' => 'text',
        'gender' => 'gender',
        'date_of_birth' => 'date',
        'phone' => 'phone',
        'alt_phone' => 'phone',
        'email' => 'email',
        'school' => 'text',
        'grade_or_year' => 'text',
        'sub_city' => 'sub_city',
        'address' => 'text',
        'guardian_name' => 'text',
        'guardian_phone' => 'phone',
        'guardian_relation' => 'text',
        'guardian_consent' => 'checkbox',
        'photo_consent' => 'checkbox',
        'notes' => 'textarea',
    ];

    /** Always on and required: the student record and duplicate check need them. */
    public const LOCKED = ['full_name', 'phone'];

    /** Off by default — the admin switches them on for a batch that needs them (e.g. a kids program). */
    public const OFF_BY_DEFAULT = ['guardian_name', 'guardian_phone', 'guardian_relation', 'guardian_consent', 'photo_consent'];

    public const CUSTOM_TYPES = [
        'text' => 'Short answer',
        'textarea' => 'Paragraph',
        'select' => 'Multiple choice (one answer)',
        'checkboxes' => 'Checkboxes (many answers)',
        'yes_no' => 'Yes / No',
        'number' => 'Number',
        'date' => 'Date',
        'section' => 'Title & description (no answer)',
    ];

    public const SUB_CITIES = ['Addis Ketema', 'Akaky Kaliti', 'Arada', 'Bole', 'Gullele', 'Kirkos', 'Kolfe Keranio', 'Lemi Kura', 'Lideta', 'Nifas Silk-Lafto', 'Yeka', 'Other'];

    private const DEFAULT_REQUIRED = ['full_name', 'phone', 'gender'];

    /** The default form: every built-in field, in the usual order. */
    public static function defaults(): array
    {
        return collect(self::BUILTIN)->map(fn ($type, $key) => [
            'key' => $key,
            'type' => $type,
            'builtin' => true,
            'label' => self::defaultLabel($key, 'en'),
            'label_am' => self::defaultLabel($key, 'am'),
            'help' => null,
            'required' => in_array($key, self::DEFAULT_REQUIRED, true),
            'enabled' => ! in_array($key, self::OFF_BY_DEFAULT, true),
            'options' => [],
        ])->values()->all();
    }

    private static function defaultLabel(string $key, string $locale): string
    {
        // Consent wording comes from Settings → Registration, if set there.
        $settings = rescue(fn () => app(\App\Settings\RegistrationSettings::class), null, false);
        $fromSettings = match ($key) {
            'guardian_consent' => $locale === 'am' ? ($settings?->consent_text_am ?: $settings?->consent_text) : $settings?->consent_text,
            'photo_consent' => $locale === 'am' ? ($settings?->photo_consent_text_am ?: $settings?->photo_consent_text) : $settings?->photo_consent_text,
            default => null,
        };

        return $fromSettings ?: __("site.form.$key", [], $locale);
    }

    /** A new, empty custom question. */
    public static function newQuestion(): array
    {
        return ['key' => 'q_'.Str::lower(Str::random(8)), 'type' => 'text', 'builtin' => false, 'label' => '', 'label_am' => null,
            'help' => null, 'required' => false, 'enabled' => true, 'options' => []];
    }

    /** All configured fields of a batch (including hidden ones), cleaned up. */
    public static function all(?Batch $batch): array
    {
        return self::complete($batch?->form_fields);
    }

    /**
     * Clean up a saved field list: fix built-in types, keep name + phone on, and append any
     * standard field that is missing (as hidden), so the admin can always switch it on.
     */
    public static function complete(?array $saved): array
    {
        $fields = collect($saved ?: self::defaults())
            ->filter(fn ($f) => filled($f['key'] ?? null))
            ->map(function ($f) {
                $builtin = array_key_exists($f['key'], self::BUILTIN);
                $f = array_merge(self::newQuestion(), $f, ['builtin' => $builtin]);
                if ($builtin) {
                    $f['type'] = self::BUILTIN[$f['key']];
                }
                if (in_array($f['key'], self::LOCKED, true)) {
                    $f['enabled'] = $f['required'] = true;
                }
                $f['options'] = array_values(array_filter((array) $f['options'], 'filled'));

                return $f;
            })
            ->unique('key')
            ->values();

        $defaults = collect(self::defaults());

        // Name and phone can never be removed.
        foreach (array_reverse(self::LOCKED) as $key) {
            if (! $fields->contains('key', $key)) {
                $fields->prepend($defaults->firstWhere('key', $key));
            }
        }

        // Other standard fields that are missing → listed at the end, hidden.
        foreach ($defaults as $d) {
            if (! $fields->contains('key', $d['key'])) {
                $fields->push(array_merge($d, ['enabled' => false]));
            }
        }

        return $fields->values()->all();
    }

    /** Fields shown on the public form. */
    public static function enabled(?Batch $batch): array
    {
        return array_values(array_filter(self::all($batch), fn ($f) => $f['enabled']));
    }

    public static function custom(?Batch $batch, bool $onlyEnabled = true): array
    {
        // Custom questions that take an answer ("Title & description" blocks don't).
        return array_values(array_filter($onlyEnabled ? self::enabled($batch) : self::all($batch), fn ($f) => ! $f['builtin'] && $f['type'] !== 'section'));
    }

    /**
     * The form's header (Google-Forms style), in the current language, with sensible fallbacks.
     *
     * @return array{title:string, description:?string, thanks:?string, cover:?string}
     */
    public static function header(Batch $batch, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $s = $batch->form_settings ?? [];
        $pick = fn (string $key) => ($locale === 'am' ? ($s[$key.'_am'] ?? null) : null) ?: ($s[$key] ?? null);
        $cover = $s['cover'] ?? null;

        return [
            'title' => $pick('title') ?: trim(($batch->program?->t('name', $locale) ?? '').' — '.$batch->name, ' —'),
            'description' => $pick('description'),
            'thanks' => $pick('thanks'),
            'cover' => $cover ? \Illuminate\Support\Facades\Storage::disk('public')->url($cover) : null,
        ];
    }

    public static function label(array $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return (string) (($locale === 'am' ? ($field['label_am'] ?? null) : null) ?: ($field['label'] ?? $field['key']));
    }

    /** Turn raw answers [key => value] into what we store: [key => ['q' => question, 'a' => answer]]. */
    public static function pack(?Batch $batch, array $raw): array
    {
        $out = [];
        foreach (self::custom($batch, false) as $f) {
            $value = $raw[$f['key']] ?? null;
            if (is_array($value)) {
                $value = array_values(array_filter($value, 'filled'));
            }
            if (blank($value)) {
                continue;
            }
            $out[$f['key']] = ['q' => self::label($f, 'en'), 'a' => $value];
        }

        return $out;
    }

    /** Stored answers → [key => value], for editing. */
    public static function unpack(?array $answers): array
    {
        return collect($answers ?? [])->map(fn ($item) => is_array($item) && array_key_exists('a', $item) ? $item['a'] : $item)->all();
    }

    /** Human-readable answer. */
    public static function display(mixed $answer): string
    {
        return match (true) {
            is_array($answer) => implode(', ', $answer),
            $answer === 'yes' => 'Yes',
            $answer === 'no' => 'No',
            default => (string) $answer,
        };
    }
}
