<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * CMS page. Pages with a `key` are "system pages" whose header (title, eyebrow, intro, SEO)
 * is used by the listing pages (services, articles, gallery, ...). Pages without a key are
 * free-form pages served at /p/{slug}.
 */
class Page extends Model implements HasMedia
{
    use HasFactory, HasLocalizedFields, InteractsWithMedia, LogsAllActivity;

    public const SYSTEM_KEYS = [
        'home', 'about', 'goals', 'services', 'programs', 'articles', 'events', 'gallery',
        'team', 'announcements', 'faq', 'contact', 'donate', 'register',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn (Page $p) => $p->key && Cache::forget("page.{$p->key}"));
        static::deleted(fn (Page $p) => $p->key && Cache::forget("page.{$p->key}"));
    }

    public static function system(string $key): ?self
    {
        return Cache::remember("page.{$key}", 3600, fn () => static::where('key', $key)->first());
    }

    public function isSystem(): bool
    {
        return filled($this->key);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->useDisk('public')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('wide')->width(1600)->nonQueued();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
