<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Expense extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsAllActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'amount' => 'decimal:2',
            'spent_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->recorded_by ??= auth()->id());
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')->useDisk('private')->singleFile();
    }

    public function receiptUrl(): ?string
    {
        $media = $this->getFirstMedia('receipt');

        return $media ? URL::temporarySignedRoute('media.private', now()->addMinutes(30), ['media' => $media->uuid]) : null;
    }
}
