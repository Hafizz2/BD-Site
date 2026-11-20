<?php

namespace App\Services\Import;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

final class ValueNormalizer
{
    public static function text(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_float($v) && floor($v) === $v) {
            $v = (string) (int) $v;
        }
        $s = trim(preg_replace('/\s+/u', ' ', (string) $v) ?? '');

        return $s === '' ? null : $s;
    }

    /** Trim, collapse spaces, title-case Latin names; Amharic (Ethiopic) names unchanged. */
    public static function name(mixed $v): ?string
    {
        $s = self::text($v);
        if ($s === null) {
            return null;
        }
        if (preg_match('/\p{Ethiopic}/u', $s)) {
            return $s;
        }

        return mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8');
    }

    public static function gender(mixed $v): ?string
    {
        $s = mb_strtolower((string) self::text($v));

        return match (true) {
            in_array($s, ['ወንድ', 'ወ', 'male', 'm', 'boy', 'man', 'brother'], true) => 'male',
            in_array($s, ['ሴት', 'ሴ', 'female', 'f', 'girl', 'woman', 'sister'], true) => 'female',
            default => null,
        };
    }

    public static function email(mixed $v): ?string
    {
        $s = self::text($v);

        return $s && filter_var($s, FILTER_VALIDATE_EMAIL) ? mb_strtolower($s) : null;
    }

    /**
     * Parse dates from Excel serials or strings (Y-m-d, d/m/Y, m/d/Y, with optional time).
     * Returns [Carbon|null, warning|null]. Ethiopian-calendar-looking values are flagged, not guessed.
     *
     * @param  'dob'|'timestamp'  $context
     * @return array{0:?Carbon,1:?string}
     */
    public static function date(mixed $v, string $context = 'dob', ?CarbonImmutable $today = null, int $minAge = 3): array
    {
        $today ??= CarbonImmutable::now('Africa/Addis_Ababa');

        if ($v === null || $v === '') {
            return [null, null];
        }

        $date = null;

        if ($v instanceof \DateTimeInterface) {
            $date = Carbon::instance($v);
        } elseif (is_numeric($v) && (float) $v > 59 && (float) $v < 2958465) {
            try {
                $date = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v));
            } catch (Throwable) {
                return [null, 'Unreadable date'];
            }
        } else {
            $s = trim((string) $v);
            if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?/', $s, $m)) {
                [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
                $time = [(int) ($m[4] ?? 0), (int) ($m[5] ?? 0), (int) ($m[6] ?? 0)];
            } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?/', $s, $m)) {
                [$a, $b, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
                if ($y < 100) {
                    $y += $y > (int) $today->format('y') ? 1900 : 2000;
                }
                // Disambiguate d/m vs m/d; default to day-first (local convention).
                if ($a > 12 && $b <= 13) {
                    [$d, $mo] = [$a, $b];
                } elseif ($b > 13 && $a <= 12) {
                    // Only clearly m/d when the 2nd part can't be a (Ethiopian) month.
                    [$mo, $d] = [$a, $b];
                } else {
                    [$d, $mo] = [$a, $b];
                }
                $time = [(int) ($m[4] ?? 0), (int) ($m[5] ?? 0), (int) ($m[6] ?? 0)];
            } else {
                return [null, "Unrecognised date \"{$s}\""];
            }

            if ($mo === 13) {
                return [null, "Looks like an Ethiopian calendar date (month 13): \"{$s}\""];
            }
            if (! checkdate($mo, $d, $y)) {
                return [null, "Invalid date \"{$s}\""];
            }
            $date = Carbon::create($y, $mo, $d, ...$time);
        }

        $year = (int) $date->year;
        $gYear = (int) $today->year;

        if ($context === 'timestamp') {
            // Gregorian is 7–8 years ahead of the Ethiopian calendar.
            if ($year <= $gYear - 7 && $year >= $gYear - 8) {
                return [null, "Year {$year} looks like an Ethiopian calendar year"];
            }
            if ($date->greaterThan($today->addDay())) {
                return [null, 'Timestamp is in the future'];
            }

            return [$date, null];
        }

        // Date of birth
        if ($date->greaterThan($today)) {
            return [null, 'Date of birth is in the future'];
        }
        if ($year < 1920) {
            return [null, 'Date of birth is too far in the past'];
        }
        if ($date->diffInYears($today) < $minAge) {
            return [null, $year >= $gYear - 10 // e.g. "2017" in 2026: Gregorian is 7–8 years ahead
                ? "Birth year {$year} looks like an Ethiopian calendar year (too young for this program)"
                : "Birth year {$year} is suspiciously recent"];
        }

        return [$date->startOfDay(), null];
    }
}
