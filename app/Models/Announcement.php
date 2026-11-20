<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Enums\NotificationStatus;
use App\Models\Concerns\HasLocalizedFields;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Announcement extends Model implements HasMedia
{
    use HasFactory, HasLocalizedFields, HasPublicUlid, InteractsWithMedia, LogsAllActivity, SoftDeletes;

    protected $guarded = ['id', 'ulid'];

    protected function casts(): array
    {
        return [
            'audience' => AnnouncementAudience::class,
            'status' => AnnouncementStatus::class,
            'audience_filter' => 'array',
            'channels' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->created_by ??= auth()->id());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->channels ?? [], true);
    }

    public function scopeOnWebsite(Builder $q): Builder
    {
        return $q->where('status', AnnouncementStatus::Sent->value)
            ->where('channels', 'like', '%website%')
            ->latest('sent_at');
    }

    /** @return array{sent:int,failed:int,blocked:int,queued:int} */
    public function deliveryStats(): array
    {
        $counts = $this->logs()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return [
            'sent' => (int) ($counts[NotificationStatus::Sent->value] ?? 0),
            'failed' => (int) ($counts[NotificationStatus::Failed->value] ?? 0),
            'blocked' => (int) ($counts[NotificationStatus::BlockedBot->value] ?? 0),
            'queued' => (int) ($counts[NotificationStatus::Queued->value] ?? 0),
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(900)->nonQueued();
    }
}
