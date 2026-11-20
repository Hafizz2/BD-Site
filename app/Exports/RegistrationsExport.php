<?php

namespace App\Exports;

use App\Models\Registration;
use App\Support\RegistrationForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Registrations (as filtered in admin) with one column per custom question.
 */
class RegistrationsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private Collection $rows;

    /** @var array<string,string> answer key => question */
    private array $questions = [];

    public function __construct(Builder $query)
    {
        $this->rows = $query->clone()->with('batch.program')->reorder('created_at')->get();

        // Question columns: from each batch's current form first (keeps its order), then any older answers.
        foreach ($this->rows->pluck('batch')->filter()->unique('id') as $batch) {
            foreach (RegistrationForm::custom($batch, false) as $f) {
                $this->questions[$f['key']] ??= $f['label'] ?: $f['key'];
            }
        }
        foreach ($this->rows as $r) {
            foreach ($r->answers ?? [] as $key => $item) {
                $this->questions[$key] ??= $item['q'] ?? $key;
            }
        }
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Submitted', 'Status', 'Program', 'Batch', 'Full name', 'Phone', 'Alt. phone', 'Gender', 'Date of birth', 'Under 18',
            'Email', 'School', 'Grade/Year', 'Sub-city', 'Address', 'Guardian', 'Guardian phone', 'Relation', 'Guardian consent',
            'Photo consent', 'Notes', 'Last called', 'Call note', 'Telegram linked',
            ...array_values($this->questions),
        ];
    }

    /** @param Registration $r */
    public function map($r): array
    {
        $answers = $r->answers ?? [];

        return [
            $r->created_at?->format('Y-m-d H:i'), $r->status?->getLabel(), $r->batch?->program?->name, $r->batch?->name,
            $r->full_name, $r->phone, $r->alt_phone, $r->gender?->getLabel(), $r->date_of_birth?->toDateString(), $r->is_minor ? 'Yes' : 'No',
            $r->email, $r->school, $r->grade_or_year, $r->sub_city, $r->address,
            $r->guardian_name, $r->guardian_phone, $r->guardian_relation, $r->guardian_consent ? 'Yes' : 'No',
            $r->photo_consent ? 'Yes' : 'No', $r->notes, $r->called_at?->format('Y-m-d H:i'), $r->call_note,
            $r->telegram_chat_id ? 'Yes' : 'No',
            ...array_map(fn ($key) => isset($answers[$key]) ? RegistrationForm::display($answers[$key]['a'] ?? $answers[$key]) : '', array_keys($this->questions)),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('F2');

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0B3D2A']]]];
    }
}
