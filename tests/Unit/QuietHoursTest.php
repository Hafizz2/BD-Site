<?php

use App\Services\Telegram\QuietHours;
use App\Settings\ReminderSettings;
use Carbon\Carbon;

function quiet(): QuietHours
{
    $s = Mockery::mock(ReminderSettings::class)->makePartial();
    $s->quiet_start = '22:00';
    $s->quiet_end = '07:00';
    $s->jumuah_pause = true;
    $s->jumuah_start = '12:00';
    $s->jumuah_end = '13:30';

    return new QuietHours($s);
}

it('blocks reminders during night quiet hours (wrapping midnight)', function (string $time, bool $quiet) {
    // 2026-11-18 is a Wednesday.
    expect(quiet()->isQuiet(Carbon::parse("2026-11-18 {$time}", 'Africa/Addis_Ababa')))->toBe($quiet);
})->with([['21:59', false], ['22:00', true], ['23:30', true], ['03:00', true], ['06:59', true], ['07:00', false], ['12:30', false]]);

it("blocks reminders during Jumu'ah on Friday only", function () {
    expect(quiet()->isQuiet(Carbon::parse('2026-11-20 12:30', 'Africa/Addis_Ababa')))->toBeTrue() // Friday
        ->and(quiet()->isQuiet(Carbon::parse('2026-11-20 13:30', 'Africa/Addis_Ababa')))->toBeFalse()
        ->and(quiet()->isQuiet(Carbon::parse('2026-11-19 12:30', 'Africa/Addis_Ababa')))->toBeFalse(); // Thursday
});

it('evaluates times in Addis Ababa even when given UTC', function () {
    // 19:30 UTC = 22:30 Addis
    expect(quiet()->isQuiet(Carbon::parse('2026-11-18 19:30', 'UTC')))->toBeTrue();
});
