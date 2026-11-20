<?php

namespace App\Models;

use App\Enums\FeeAppliesTo;
use App\Enums\FeeFrequency;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeePlan extends Model
{
    use HasFactory, LogsAllActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'frequency' => FeeFrequency::class,
            'applies_to' => FeeAppliesTo::class,
            'is_active' => 'boolean',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function dues(): HasMany
    {
        return $this->hasMany(Due::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'monthly_contribution_plan_id');
    }
}
