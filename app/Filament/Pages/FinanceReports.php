<?php

namespace App\Filament\Pages;

use App\Exports\FinanceReportExport;
use App\Models\Payment;
use App\Services\Finance\FinanceReport;
use App\Settings\GeneralSettings;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class FinanceReports extends Page
{
    protected string $view = 'filament.pages.finance-reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Finance reports';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Payment::class) && auth()->user()->hasAnyRole(['super_admin', 'finance']);
    }

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function setRange(string $range): void
    {
        [$this->from, $this->to] = match ($range) {
            'this_month' => [now()->startOfMonth(), now()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()],
            'year' => [now()->startOfYear(), now()],
            default => [now()->subDays(30), now()],
        };
        $this->from = Carbon::parse($this->from)->toDateString();
        $this->to = Carbon::parse($this->to)->toDateString();
    }

    public function report(): FinanceReport
    {
        $from = rescue(fn () => Carbon::parse($this->from), now()->startOfMonth(), false);
        $to = rescue(fn () => Carbon::parse($this->to), now(), false);

        return new FinanceReport($from->min($to), $to->max($from));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('xlsx')->label('Export Excel')->icon('heroicon-o-table-cells')->color('gray')
                ->action(fn () => Excel::download(new FinanceReportExport($this->report()), "finance-{$this->from}_{$this->to}.xlsx")),
            Action::make('pdf')->label('Export PDF')->icon('heroicon-o-document-arrow-down')->color('gold')
                ->action(function () {
                    $r = $this->report();
                    $pdf = Pdf::loadView('pdf.finance-report', ['r' => $r, 'site' => app(GeneralSettings::class)])->setPaper('a4');

                    return response()->streamDownload(fn () => print($pdf->output()), "finance-{$this->from}_{$this->to}.pdf");
                }),
        ];
    }

    protected function getViewData(): array
    {
        $r = $this->report();

        return [
            'summary' => $r->summary(),
            'byMethod' => $r->byMethod(),
            'byBatch' => $r->byBatch(),
            'expenses' => $r->expensesByCategory(),
            'unpaid' => $r->unpaid(),
        ];
    }
}
