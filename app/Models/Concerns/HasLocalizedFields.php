<?php

namespace App\Models\Concerns;

/**
 * Models store English in `field` and Amharic in `field_am`.
 * `$model->t('title')` returns the value for the current locale, falling back to English.
 */
trait HasLocalizedFields
{
    public function t(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'am') {
            $am = $this->getAttribute($field.'_am');
            if (filled($am)) {
                return $am;
            }
        }

        return $this->getAttribute($field);
    }
}
