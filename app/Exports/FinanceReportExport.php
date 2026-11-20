<?php

namespace App\Exports;

use App\Services\Finance\FinanceReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class FinanceReportExport implements WithMultipleSheets
{
    public function __construct(private FinanceReport $report) {}

    public function sheets(): array
    {
        $r = $this->report;
        $s = $r->summary();
        $sheet = fn (string $title, array $rows) => new class($title, $rows) implements FromArray, ShouldAutoSize, WithTitle
        {
            public function __construct(private string $t, private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }

            public function title(): string
            {
                return $this->t;
            }
        };

        return [
            $sheet('Summary', [
                ['Finance report', $r->from->toDateString().' to '.$r->to->toDateString()],
                [],
                ['Fees collected', $s['collected']], ['Donations', $s['donations']], ['Total income', $s['income']],
                ['Expenses', $s['expenses']], ['Net', $s['net']], ['Outstanding (all time)', $s['outstanding']],
                ['Approved payments', $s['payments_count']], ['Receipts pending review', $s['pending_count']],
            ]),
            $sheet('By program-batch', array_merge([['Program', 'Batch', 'Payments', 'Total (ETB)']],
                $r->byBatch()->map(fn ($x) => [$x->program, $x->batch, $x->count, $x->total])->all())),
            $sheet('By method', array_merge([['Method', 'Payments', 'Total (ETB)']],
                $r->byMethod()->map(fn ($x) => [$x->label, $x->count, $x->total])->all())),
            $sheet('Expenses', array_merge([['Category', 'Total (ETB)']],
                $r->expensesByCategory()->map(fn ($x) => [$x->label, $x->total])->all())),
            $sheet('Unpaid', array_merge([['Name', 'Phone', 'Type', 'For', 'Oldest due', 'Overdue', 'Balance (ETB)']],
                $r->unpaid()->map(fn ($x) => [$x->name, $x->phone, $x->type, $x->items, $x->oldest?->toDateString(), $x->overdue ? 'Yes' : 'No', $x->balance])->all())),
        ];
    }
}
