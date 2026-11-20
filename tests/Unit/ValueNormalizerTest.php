<?php

use App\Services\Import\ValueNormalizer;
use Carbon\CarbonImmutable;

$today = CarbonImmutable::create(2026, 11, 18, 12, 0, 0, 'Africa/Addis_Ababa');

it('parses Excel serial dates', function () use ($today) {
    [$d, $warn] = ValueNormalizer::date(37000, 'dob', $today); // 2001-04-19
    expect($warn)->toBeNull()->and($d->toDateString())->toBe('2001-04-19');
});

it('parses common string date formats', function (string $input, string $expected) use ($today) {
    [$d, $warn] = ValueNormalizer::date($input, 'dob', $today);
    expect($warn)->toBeNull()->and($d->toDateString())->toBe($expected);
})->with([
    'Y-m-d' => ['2003-05-14', '2003-05-14'],
    'd/m/Y' => ['14/05/2003', '2003-05-14'],
    'm/d/Y (day > 12)' => ['05/14/2003', '2003-05-14'],
    'ambiguous defaults to day-first' => ['03/04/2003', '2003-04-03'],
    'd-m-Y' => ['14-05-2003', '2003-05-14'],
    'd.m.Y' => ['14.05.2003', '2003-05-14'],
]);

it('parses Google Forms timestamps', function () use ($today) {
    [$d, $warn] = ValueNormalizer::date('11/15/2026 14:32:10', 'timestamp', $today);
    expect($warn)->toBeNull()->and($d->format('Y-m-d H:i'))->toBe('2026-11-15 14:32');
});

it('flags Ethiopian calendar dates instead of guessing', function () use ($today) {
    // Month 13 (Pagume) only exists in the Ethiopian calendar.
    expect(ValueNormalizer::date('03/13/2010', 'dob', $today))->sequence(fn ($d) => $d->toBeNull(), fn ($w) => $w->toContain('Ethiopian'));
    // A 2019 timestamp when it is 2026 Gregorian (2019 Ethiopian).
    expect(ValueNormalizer::date('2019-03-10', 'timestamp', $today)[1])->toContain('Ethiopian');
    // A college student born "2017" is clearly an Ethiopian-calendar year.
    expect(ValueNormalizer::date('2017-01-01', 'dob', $today, 15)[1])->toContain('Ethiopian');
});

it('rejects impossible dates', function () use ($today) {
    expect(ValueNormalizer::date('31/02/2003', 'dob', $today)[1])->toContain('Invalid')
        ->and(ValueNormalizer::date('2030-01-01', 'dob', $today)[1])->toContain('future')
        ->and(ValueNormalizer::date('not a date', 'dob', $today)[1])->toContain('Unrecognised');
});

it('maps gender values in English and Amharic', function ($in, $out) {
    expect(ValueNormalizer::gender($in))->toBe($out);
})->with([['ወንድ', 'male'], ['ሴት', 'female'], ['Male', 'male'], ['F', 'female'], ['m', 'male'], ['?', null]]);

it('title-cases Latin names and keeps Amharic names unchanged', function () {
    expect(ValueNormalizer::name('  abdulhafiz   JEMAL  '))->toBe('Abdulhafiz Jemal')
        ->and(ValueNormalizer::name('ሐናን  ከማል'))->toBe('ሐናን ከማል');
});
