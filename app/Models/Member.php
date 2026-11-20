<?php

namespace App\Models;

use App\Enums\DueStatus;
use App\Enums\MemberTeam;
use App\Models\Concerns\HasLocalizedFields;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Concerns\HasTelegram;
use App\Models\Concerns\LogsAllActivity;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Member extends Model implements HasMedia
{
    use HasFactory, HasLocalizedFields, HasPublicUlid, HasTelegram, InteractsWithMedia, LogsAllActivity, SoftDeletes;

    protected $guarded = ['id', 'ulid'];

    protected function casts(): array
    {
        return [
            'team' => MemberTeam::class,
            'show_on_website' => 'boolean',
            'is_active' => 'boolean',
            'telegram_linked_at' => 'datetime',
            'telegram_blocked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Member $m) {
            if (filled($m->phone)) {
                $m->phone = PhoneNumber::normalize($m->phone) ?? $m->phone;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function contributionPlan(): BelongsTo
    {
        return $this->belongsTo(FeePlan::class, 'monthly_contribution_plan_id');
    }

    public function dues(): MorphMany
    {
        return $this->morphMany(Due::class, 'payable');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function outstandingDues(): MorphMany
    {
        return $this->dues()->whereIn('status', DueStatus::outstanding());
    }

    public function scopePublicTeam(Builder $q): Builder
    {
        return $q->where('show_on_website', true)->where('is_active', true)->orderBy('sort');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
    }
}
