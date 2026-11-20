<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduler — one cron line runs everything (cheap cPanel hosting friendly):
|   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
|--------------------------------------------------------------------------
*/

// Process the database queue in short bursts (no long-running worker needed on shared hosting).
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=55 --sleep=1')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

Schedule::command('announcements:dispatch-scheduled')->everyMinute()->withoutOverlapping();

// Finance
Schedule::command('dues:generate')->monthlyOn(1, '00:30')->timezone('Africa/Addis_Ababa');
Schedule::command('dues:generate')->dailyAt('01:00')->timezone('Africa/Addis_Ababa'); // idempotent: catches new members/plans + flags overdue
Schedule::command('dues:send-reminders')->hourlyAt(5)->withoutOverlapping();
Schedule::command('finance:weekly-summary')->hourlyAt(0);

// Housekeeping
Schedule::command('backup:clean')->dailyAt('02:00')->timezone('Africa/Addis_Ababa');
Schedule::command('backup:run --only-db')->dailyAt('02:30')->timezone('Africa/Addis_Ababa');
Schedule::command('queue:prune-failed --hours=720')->weekly();
Schedule::command('activitylog:clean --days=730')->monthly();
