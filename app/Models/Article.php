<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Models\Concerns\HasLocalizedFields;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Article extends Model implements HasMedia
{
    use HasFactory, HasLocalizedFields, InteractsWithMedia, LogsAllActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Article $a) {
            $a->slug = $a->slug ?: Str::slug($a->title).'-'.Str::lower(Str::random(4));
            if ($a->status === ArticleStatus::Published && ! $a->published_at) {
                $a->published_at = now();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'author_id');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at');
    }

    public function authorName(): ?string
    {
        return $this->author?->t('full_name') ?? $this->author_name;
    }

    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags((string) $this->t('body')));

        return max(1, (int) ceil($words / 200));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->useDisk('public')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(900)->height(560)->nonQueued();
        $this->addMediaConversion('wide')->width(1600)->nonQueued();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
