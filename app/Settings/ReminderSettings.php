<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ReminderSettings extends Settings
{
    public bool $enabled;

    public int $days_before;

    public int $overdue_interval_days;

    public int $overdue_max_reminders;

    /** Quiet hours (HH:MM, Addis time). Wraps past midnight when start > end. */
    public string $quiet_start;

    public string $quiet_end;

    public bool $jumuah_pause;

    public string $jumuah_start;

    public string $jumuah_end;

    public static function group(): string
    {
        return 'reminders';
    }
}
