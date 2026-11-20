<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query->clone()->with(['enrollments.batch.program'])->reorder('full_name');
    }

    public function headings(): array
    {
        return ['Full name', 'Gender', 'Date of birth', 'Phone', 'Alt. phone', 'Email', 'Sub-city', 'School', 'Grade/Year',
            'Guardian', 'Guardian phone', 'Guardian consent', 'Photo consent', 'Status', 'Source', 'Batches', 'Telegram linked', 'Registered'];
    }

    /** @param Student $s */
    public function map($s): array
    {
        return [
            $s->full_name, $s->gender?->getLabel(), $s->date_of_birth?->toDateString(), $s->phone, $s->alt_phone, $s->email,
            $s->sub_city, $s->school, $s->grade_or_year, $s->guardian_name, $s->guardian_phone,
            $s->guardian_consent ? 'Yes' : 'No', $s->photo_consent ? 'Yes' : 'No',
            $s->status?->getLabel(), $s->source?->getLabel(),
            $s->enrollments->map(fn ($e) => $e->batch?->label.' ('.$e->status->value.')')->implode('; '),
            $s->telegram_chat_id ? 'Yes' : 'No',
            ($s->registered_at ?? $s->created_at)?->toDateString(),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0B3D2A']]]];
    }
}
