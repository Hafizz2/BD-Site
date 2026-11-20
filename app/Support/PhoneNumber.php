<?php

namespace App\Support;

/**
 * Ethiopian phone number normalization to E.164 (+2519xxxxxxxx / +2517xxxxxxxx).
 */
final class PhoneNumber
{
    /**
     * Normalize a raw phone number. Returns null when the value is not a valid
     * Ethiopian mobile number.
     */
    public static function normalize(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        // Excel may give us numbers as floats/ints (leading 0 lost: 911234567).
        if (is_float($raw) || is_int($raw)) {
            $raw = number_format((float) $raw, 0, '', '');
        }

        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        // Convert Ethiopic / Arabic-Indic digits just in case, then keep digits and a leading +.
        $value = strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $hasPlus = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($hasPlus && ! str_starts_with($digits, '251')) {
            return null;
        }

        if (str_starts_with($digits, '00251')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '251')) {
            $local = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $local = substr($digits, 1);
        } else {
            $local = $digits;
        }

        if (! preg_match('/^[79]\d{8}$/', $local)) {
            return null;
        }

        return '+251'.$local;
    }

    public static function isValid(mixed $raw): bool
    {
        return self::normalize($raw) !== null;
    }

    /** Pretty format for display: +251 91 123 4567 */
    public static function format(?string $e164): ?string
    {
        if (! $e164 || ! preg_match('/^\+251(\d{2})(\d{3})(\d{4})$/', $e164, $m)) {
            return $e164;
        }

        return "+251 {$m[1]} {$m[2]} {$m[3]}";
    }
}
