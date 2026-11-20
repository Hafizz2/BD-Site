<?php

namespace App\Services\Import;

/**
 * Guesses which spreadsheet column maps to which student field from messy
 * Google-Forms question headers (English or Amharic).
 */
class HeaderMapper
{
    /**
     * @param  array<int,string|null>  $headers  column index => header text
     * @return array<string,int|null> field => column index (null when not found)
     */
    public function guess(array $headers): array
    {
        $candidates = [];
        foreach ($headers as $index => $header) {
            $h = self::normalize((string) $header);
            if ($h === '') {
                continue;
            }
            foreach ($this->scores($h) as $field => $score) {
                $candidates[] = ['field' => $field, 'index' => $index, 'score' => $score];
            }
        }

        // Highest score first; earlier columns win ties.
        usort($candidates, fn ($a, $b) => [$b['score'], $a['index']] <=> [$a['score'], $b['index']]);

        $mapping = array_fill_keys(array_keys(StudentFields::LABELS), null);
        $usedColumns = [];
        foreach ($candidates as $c) {
            if ($mapping[$c['field']] !== null || isset($usedColumns[$c['index']])) {
                continue;
            }
            $mapping[$c['field']] = $c['index'];
            $usedColumns[$c['index']] = true;
        }

        return $mapping;
    }

    /** @return array<string,int> field => score for a normalized header */
    private function scores(string $h): array
    {
        $scores = [];
        $isGuardian = $this->containsAny($h, StudentFields::GUARDIAN_KEYWORDS) > 0;

        if ($isGuardian) {
            if ($s = $this->containsAny($h, StudentFields::KEYWORDS['guardian_relation'])) {
                $scores['guardian_relation'] = 100 + $s;
            } elseif ($s = $this->containsAny($h, StudentFields::KEYWORDS['phone'])) {
                $scores['guardian_phone'] = 100 + $s;
            } else {
                $scores['guardian_name'] = 90 + $this->containsAny($h, StudentFields::KEYWORDS['full_name']);
            }

            return $scores;
        }

        // "Relationship to student" only makes sense for a guardian.
        if ($s = $this->containsAny($h, StudentFields::KEYWORDS['guardian_relation'])) {
            return ['guardian_relation' => 100 + $s];
        }

        foreach (StudentFields::KEYWORDS as $field => $keywords) {
            if (str_starts_with($field, 'guardian_')) {
                continue;
            }
            if ($s = $this->containsAny($h, $keywords)) {
                $scores[$field] = $s;
            }
        }

        // "Email address" is an email, not a street address.
        if (isset($scores['email'], $scores['address'])) {
            unset($scores['address']);
            $scores['email'] += 10;
        }

        // "Phone" inside an alt-phone header shouldn't also claim the main phone slot.
        if (isset($scores['alt_phone'], $scores['phone'])) {
            unset($scores['phone']);
        }
        // "Date of birth" / "year of birth" are not grade/year.
        if (isset($scores['date_of_birth'], $scores['grade_or_year'])) {
            unset($scores['grade_or_year']);
        }
        // A "name of school" header is a school, not the student's name.
        if (isset($scores['school'], $scores['full_name'])) {
            unset($scores['full_name']);
        }

        return $scores;
    }

    /** Returns the length of the longest keyword contained in the header (0 if none). */
    private function containsAny(string $header, array $keywords): int
    {
        $best = 0;
        foreach ($keywords as $k) {
            $k = self::normalize($k);
            if ($k === '') {
                continue;
            }
            // Short latin keywords (tel, dob, sex) must match a whole word.
            $matched = (mb_strlen($k) <= 3 && preg_match('/^[a-z]+$/', $k))
                ? (bool) preg_match('/\b'.preg_quote($k, '/').'\b/u', $header)
                : str_contains($header, $k);
            if ($matched) {
                $best = max($best, mb_strlen($k));
            }
        }

        return $best;
    }

    public static function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[\s_]+/u', ' ', $s) ?? $s;

        return trim(preg_replace('/[?:*()\[\]።፣፤]+/u', ' ', $s) ?? $s);
    }
}
