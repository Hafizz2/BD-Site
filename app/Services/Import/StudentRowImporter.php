<?php

namespace App\Services\Import;

use App\Enums\DuplicateRule;
use App\Enums\EnrollmentStatus;
use App\Enums\ProgramType;
use App\Enums\StudentSource;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\Import;
use App\Models\Student;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;

/**
 * Turns one spreadsheet row into a student (+ enrollment) according to the mapping and duplicate rule.
 */
class StudentRowImporter
{
    private ?int $minAge = null;

    public function __construct(private readonly Import $import) {}

    /**
     * Map a raw row (column index => value) to field => raw value.
     *
     * @param  array<string,int|string|null>  $mapping  field => column index
     */
    public static function extract(array $row, array $mapping): array
    {
        $out = [];
        foreach ($mapping as $field => $col) {
            if ($col === null || $col === '' || $col === 'ignore') {
                continue;
            }
            $out[$field] = $row[(int) $col] ?? null;
        }

        return $out;
    }

    /**
     * Validate + normalize. Returns [attributes, errors, warnings].
     *
     * @return array{0:array<string,mixed>,1:list<string>,2:list<string>}
     */
    public function normalize(array $raw): array
    {
        $errors = [];
        $warnings = [];
        $a = [];

        $a['full_name'] = ValueNormalizer::name($raw['full_name'] ?? null);
        if (! $a['full_name']) {
            $errors[] = 'Full name is missing';
        }

        foreach (['phone', 'alt_phone', 'guardian_phone'] as $f) {
            $value = ValueNormalizer::text($raw[$f] ?? null);
            if ($value === null) {
                $a[$f] = null;

                continue;
            }
            $a[$f] = PhoneNumber::normalize($raw[$f]);
            if ($a[$f] === null) {
                if ($f === 'phone') {
                    $errors[] = "Invalid phone number \"{$value}\"";
                } else {
                    $warnings[] = 'Ignored invalid '.str_replace('_', ' ', $f)." \"{$value}\"";
                }
            }
        }
        $phoneInvalid = collect($errors)->contains(fn ($e) => str_starts_with($e, 'Invalid phone'));
        if (! $a['phone'] && ! $a['guardian_phone'] && ! $phoneInvalid) {
            $errors[] = 'Phone number is missing';
        }

        if (array_key_exists('gender', $raw)) {
            $a['gender'] = ValueNormalizer::gender($raw['gender']);
            if ($a['gender'] === null && ValueNormalizer::text($raw['gender']) !== null) {
                $warnings[] = 'Unknown gender "'.ValueNormalizer::text($raw['gender']).'"';
            }
        }

        if (array_key_exists('date_of_birth', $raw)) {
            [$dob, $warn] = ValueNormalizer::date($raw['date_of_birth'], 'dob', null, $this->minAge());
            $a['date_of_birth'] = $dob?->toDateString();
            if ($warn) {
                $warnings[] = 'Date of birth: '.$warn;
            }
        }

        if (array_key_exists('registered_at', $raw)) {
            [$ts, $warn] = ValueNormalizer::date($raw['registered_at'], 'timestamp');
            $a['registered_at'] = $ts;
            if ($warn) {
                $warnings[] = 'Timestamp: '.$warn;
            }
        }

        if (array_key_exists('email', $raw)) {
            $a['email'] = ValueNormalizer::email($raw['email']);
            if (! $a['email'] && ValueNormalizer::text($raw['email'])) {
                $warnings[] = 'Ignored invalid email "'.ValueNormalizer::text($raw['email']).'"';
            }
        }

        foreach (['school', 'grade_or_year', 'sub_city', 'address', 'guardian_relation', 'notes'] as $f) {
            if (array_key_exists($f, $raw)) {
                $a[$f] = ValueNormalizer::text($raw[$f]);
            }
        }
        if (array_key_exists('guardian_name', $raw)) {
            $a['guardian_name'] = ValueNormalizer::name($raw['guardian_name']);
        }

        // Only keep fields that were actually mapped (+ the always-present ones).
        $a = array_filter($a, fn ($v, $k) => array_key_exists($k, $raw) || in_array($k, ['full_name', 'phone'], true), ARRAY_FILTER_USE_BOTH);

        return [$a, $errors, $warnings];
    }

    /**
     * @return array{result:'created'|'updated'|'skipped', student:Student, warnings:list<string>}
     *
     * @throws RowFailedException
     */
    public function import(array $raw): array
    {
        [$attrs, $errors, $warnings] = $this->normalize($raw);
        if ($errors) {
            throw new RowFailedException(implode('; ', $errors));
        }

        $existing = $this->findExisting($attrs);
        $rule = $this->import->duplicate_rule ?? DuplicateRule::Skip;

        if (! $existing) {
            $student = Student::create(array_filter($attrs, fn ($v) => $v !== null) + [
                'status' => StudentStatus::Active,
                'source' => StudentSource::Import,
                'import_id' => $this->import->id,
                'registered_at' => $attrs['registered_at'] ?? now(),
            ]);
            $result = 'created';
        } else {
            $student = $existing;
            $result = 'skipped';
            if ($rule !== DuplicateRule::Skip) {
                foreach ($attrs as $field => $value) {
                    if ($value === null || $field === 'registered_at') {
                        continue;
                    }
                    if ($rule === DuplicateRule::Overwrite || blank($student->{$field})) {
                        $student->{$field} = $value;
                    }
                }
                if ($student->isDirty()) {
                    $student->save();
                    $result = 'updated';
                }
            }
        }

        if ($this->import->batch_id) {
            $enrollment = Enrollment::firstOrNew(['student_id' => $student->id, 'batch_id' => $this->import->batch_id]);
            if (! $enrollment->exists) {
                $enrollment->fill([
                    'status' => EnrollmentStatus::Approved,
                    'enrolled_at' => $attrs['registered_at'] ?? now(),
                    'import_id' => $this->import->id,
                ])->save();
            }
        }

        return ['result' => $result, 'student' => $student, 'warnings' => $warnings];
    }

    private function findExisting(array $attrs): ?Student
    {
        if (! empty($attrs['phone'])) {
            return Student::where('phone', $attrs['phone'])->first();
        }

        // No own phone (young children): same name + same guardian phone is the same child.
        if (! empty($attrs['guardian_phone'])) {
            return Student::where('guardian_phone', $attrs['guardian_phone'])
                ->where('full_name', $attrs['full_name'])
                ->first();
        }

        return null;
    }

    private function minAge(): int
    {
        if ($this->minAge !== null) {
            return $this->minAge;
        }
        $type = $this->import->batch?->program?->type;

        return $this->minAge = $type === ProgramType::CollegeTraining ? 15 : 3;
    }

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now('Africa/Addis_Ababa');
    }
}
