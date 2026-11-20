<?php

namespace App\Models;

use App\Enums\DuplicateRule;
use App\Enums\ImportStatus;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use HasPublicUlid;

    protected $guarded = ['id', 'ulid'];

    protected function casts(): array
    {
        return [
            'column_mapping' => 'array',
            'failures' => 'array',
            'status' => ImportStatus::class,
            'duplicate_rule' => DuplicateRule::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function progress(): int
    {
        return $this->total_rows > 0 ? (int) min(100, round($this->processed_rows / $this->total_rows * 100)) : 0;
    }
}
