<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GalleryAlbum extends Model implements HasMedia
{
    use HasFactory, HasLocalizedFields, InteractsWithMedia, LogsAllActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'taken_on' => 'date',
            'contains_minors' => 'boolean',
            'consent_confirmed' => 'boolean',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (GalleryAlbum $a) => $a->slug = $a->slug ?: Str::slug($a->title).'-'.Str::lower(Str::random(4)));
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Privacy rule: albums containing minors are only public once Media confirms consent.
     */
    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_published', true)
            ->where(fn ($w) => $w->where('contains_minors', false)->orWhere('consent_confirmed', true))
            ->orderBy('sort')->orderByDesc('taken_on');
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_published && (! $this->contains_minors || $this->consent_confirmed);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(600)->height(600)->nonQueued();
        $this->addMediaConversion('large')->width(1800)->nonQueued();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
