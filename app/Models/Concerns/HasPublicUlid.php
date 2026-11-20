<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Keeps integer primary keys internally but exposes a ULID in URLs.
 */
trait HasPublicUlid
{
    public static function bootHasPublicUlid(): void
    {
        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
