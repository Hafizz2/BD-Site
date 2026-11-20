<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubmittedVia;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Payment extends Model implements HasMedia
{
    use HasFactory, HasPublicUlid, InteractsWithMedia, LogsAllActivity, SoftDeletes;

    protected $guarded = ['id', 'ulid'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'submitted_via' => SubmittedVia::class,
            'paid_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function due(): BelongsTo
    {
        return $this->belongsTo(Due::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')->useDisk('private')->singleFile();
    }

    /** Short-lived signed URL to the private receipt (only served to authorized users). */
    public function receiptUrl(): ?string
    {
        $media = $this->getFirstMedia('receipt');

        return $media ? URL::temporarySignedRoute('media.private', now()->addMinutes(30), ['media' => $media->uuid]) : null;
    }
}
