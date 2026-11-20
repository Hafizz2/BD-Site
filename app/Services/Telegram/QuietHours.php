<?php

namespace App\Services\Telegram;

use App\Settings\ReminderSettings;
use Carbon\CarbonInterface;

/**
 * Decides whether automated reminders may be sent at a given moment (Addis Ababa time).
 */
class QuietHours
{
    public function __construct(private readonly ReminderSettings $settings) {}

    public function isQuiet(CarbonInterface $at): bool
    {
        $local = $at->copy()->setTimezone('Africa/Addis_Ababa');
        $minutes = $local->hour * 60 + $local->minute;

        if ($this->inWindow($minutes, $this->settings->quiet_start, $this->settings->quiet_end)) {
            return true;
        }

        if ($this->settings->jumuah_pause && $local->isFriday()
            && $this->inWindow($minutes, $this->settings->jumuah_start, $this->settings->jumuah_end)) {
            return true;
        }

        return false;
    }

    private function inWindow(int $minutes, string $start, string $end): bool
    {
        $s = $this->toMinutes($start);
        $e = $this->toMinutes($end);

        if ($s === $e) {
            return false;
        }

        // Window wrapping midnight, e.g. 22:00 → 07:00
        return $s < $e ? ($minutes >= $s && $minutes < $e) : ($minutes >= $s || $minutes < $e);
    }

    private function toMinutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm) + [0, 0]);

        return $h * 60 + $m;
    }
}
