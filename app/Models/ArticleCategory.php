<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ArticleCategory extends Model
{
    use HasFactory, HasLocalizedFields;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(fn (ArticleCategory $c) => $c->slug = $c->slug ?: Str::slug($c->name));
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
