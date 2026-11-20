<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query->clone()->with(['payable', 'due.feePlan', 'reviewer'])->reorder('paid_at', 'desc');
    }

    public function headings(): array
    {
        return ['ID', 'Paid at', 'Payer', 'Type', 'Phone', 'For', 'Amount (ETB)', 'Method', 'Reference', 'Status', 'Via', 'Reviewed by', 'Reviewed at', 'Rejection reason'];
    }

    /** @param Payment $p */
    public function map($p): array
    {
        return [
            $p->ulid, $p->paid_at?->format('Y-m-d H:i'), $p->payable?->full_name, $p->payable_type, $p->payable?->phone,
            $p->due?->label, (float) $p->amount, $p->method?->getLabel(), $p->transaction_ref, $p->status?->getLabel(),
            $p->submitted_via?->getLabel(), $p->reviewer?->name, $p->reviewed_at?->format('Y-m-d H:i'), $p->rejection_reason,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0B3D2A']]]];
    }
}
