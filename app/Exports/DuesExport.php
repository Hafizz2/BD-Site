<?php

namespace App\Exports;

use App\Models\Due;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DuesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query->clone()->with(['payable', 'feePlan'])->reorder('due_date');
    }

    public function headings(): array
    {
        return ['Owed by', 'Type', 'Phone', 'For', 'Period', 'Amount', 'Paid', 'Balance', 'Due date', 'Status', 'Overdue reminders sent'];
    }

    /** @param Due $d */
    public function map($d): array
    {
        return [
            $d->payable?->full_name, $d->payable_type, $d->payable?->phone, $d->label, $d->period,
            (float) $d->amount, (float) $d->paid_amount, (float) $d->balance(), $d->due_date?->toDateString(),
            $d->status?->getLabel(), $d->overdue_reminders_count,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0B3D2A']]]];
    }
}
