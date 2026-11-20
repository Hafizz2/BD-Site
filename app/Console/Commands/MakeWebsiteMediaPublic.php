<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Article;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\Media;
use App\Models\Page;
use App\Models\Program;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;
use Throwable;

/**
 * Website pictures must live on the public disk so visitors can see them.
 * Older uploads were saved on the private disk by mistake — this moves them (original + resized copies).
 * Safe to run any number of times; receipts and other private files are never touched.
 */
class MakeWebsiteMediaPublic extends Command
{
    protected $signature = 'media:make-website-public';

    protected $description = 'Move website images (covers, gallery, announcements) that were saved privately to the public disk';

    /** model => collections shown on the public website */
    public const PUBLIC_COLLECTIONS = [
        Announcement::class => ['image'],
        Article::class => ['cover'],
        Event::class => ['cover'],
        GalleryAlbum::class => ['photos'],
        Page::class => ['cover'],
        Program::class => ['cover'],
        Service::class => ['cover'],
    ];

    public function handle(): int
    {
        $moved = 0;
        $failed = 0;

        foreach (self::PUBLIC_COLLECTIONS as $model => $collections) {
            Media::query()
                ->where('model_type', (new $model)->getMorphClass())
                ->whereIn('collection_name', $collections)
                ->where(fn ($q) => $q->where('disk', '!=', 'public')->orWhere('conversions_disk', '!=', 'public'))
                ->each(function (Media $media) use (&$moved, &$failed) {
                    try {
                        $this->moveToPublic($media);
                        $moved++;
                    } catch (Throwable $e) {
                        $failed++;
                        $this->components->warn("Image #{$media->id} ({$media->file_name}): ".$e->getMessage());
                    }
                });
        }

        $this->components->info($moved ? "Moved {$moved} website image(s) to the public disk." : 'All website images are already public.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function moveToPublic(Media $media): void
    {
        $paths = PathGeneratorFactory::create($media);
        $public = Storage::disk('public');

        foreach ([[$media->disk, $paths->getPath($media)], [$media->conversions_disk ?: $media->disk, $paths->getPathForConversions($media)]] as [$disk, $dir]) {
            if ($disk === 'public') {
                continue;
            }
            $from = Storage::disk($disk);
            foreach ($from->files($dir) as $file) {
                if (! $public->exists($file)) {
                    $public->writeStream($file, $from->readStream($file));
                }
            }
        }

        // Point the record at the public copies, then remove the private ones.
        $oldDisks = array_unique(array_filter([$media->disk, $media->conversions_disk]));
        $media->forceFill(['disk' => 'public', 'conversions_disk' => 'public'])->save();

        foreach ($oldDisks as $disk) {
            if ($disk !== 'public') {
                Storage::disk($disk)->deleteDirectory(rtrim($paths->getPath($media), '/'));
            }
        }
    }
}
