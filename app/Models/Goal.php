<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory, HasLocalizedFields, LogsAllActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'progress' => 'integer'];
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort');
    }
}
