<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
    use HasFactory, HasLocalizedFields, LogsAllActivity;

    public const AUTO_SOURCES = [
        'students' => 'Total students',
        'graduates' => 'Graduated students',
        'batches' => 'Total batches',
        'programs' => 'Active programs',
        'members' => 'Active members / volunteers',
        'events' => 'Published events',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'value' => 'integer'];
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort');
    }

    /** Live value when an auto source is chosen, otherwise the manual value. */
    public function resolvedValue(): int
    {
        return match ($this->auto_source) {
            'students' => Student::count(),
            'graduates' => Student::where('status', 'graduated')->count(),
            'batches' => Batch::count(),
            'programs' => Program::where('is_active', true)->count(),
            'members' => Member::where('is_active', true)->count(),
            'events' => Event::where('is_published', true)->count(),
            default => (int) $this->value,
        };
    }
}
