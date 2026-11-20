<?php

namespace App\Console\Commands;

use App\Enums\AnnouncementStatus;
use App\Jobs\SendAnnouncement;
use App\Models\Announcement;
use Illuminate\Console\Command;

class DispatchScheduledAnnouncements extends Command
{
    protected $signature = 'announcements:dispatch-scheduled';

    protected $description = 'Queue announcements whose scheduled time has arrived';

    public function handle(): int
    {
        $n = 0;
        Announcement::where('status', AnnouncementStatus::Scheduled->value)
            ->where('scheduled_at', '<=', now())
            ->each(function (Announcement $a) use (&$n) {
                SendAnnouncement::dispatch($a->id);
                $n++;
            });

        $this->info("Dispatched {$n} announcement(s).");

        return self::SUCCESS;
    }
}
