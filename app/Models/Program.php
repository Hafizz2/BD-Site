<?php

namespace App\Models;

use App\Enums\BatchStatus;
use App\Enums\ProgramType;
use App\Models\Concerns\HasLocalizedFields;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Program extends Model implements HasMedia
{
    use HasFactory, HasLocalizedFields, InteractsWithMedia, LogsAllActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => ProgramType::class,
            'is_active' => 'boolean',
            'show_on_website' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Program $p) {
            if (blank($p->slug)) {
                $p->slug = Str::slug($p->name);
            }
        });
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function openBatches(): HasMany
    {
        return $this->batches()->where('registration_open', true)->where('status', '!=', BatchStatus::Completed->value);
    }

    public function enrollments(): HasManyThrough
    {
        return $this->hasManyThrough(Enrollment::class, Batch::class);
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('show_on_website', true)->orderBy('sort');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(800)->height(520)->nonQueued();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
