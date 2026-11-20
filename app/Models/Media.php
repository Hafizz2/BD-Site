<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * Uploaded file. If a resized copy (thumb / card / wide …) was never created on the server
 * — e.g. the image extension was missing when it was uploaded — fall back to the original
 * image instead of showing a broken picture.
 */
class Media extends BaseMedia
{
    public function getUrl(string $conversionName = ''): string
    {
        if ($conversionName !== '' && ! $this->hasGeneratedConversion($conversionName)) {
            return parent::getUrl();
        }

        return parent::getUrl($conversionName);
    }

    public function getPath(string $conversionName = ''): string
    {
        if ($conversionName !== '' && ! $this->hasGeneratedConversion($conversionName)) {
            return parent::getPath();
        }

        return parent::getPath($conversionName);
    }
}
