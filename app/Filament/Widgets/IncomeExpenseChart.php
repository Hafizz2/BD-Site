<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\Expense;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class IncomeExpenseChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Income vs expenses — last 6 months';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 2];

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Payment::class) ?? false;
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonthsNoOverflow($i));
        $income = $months->map(fn ($m) => (float) Payment::where('status', PaymentStatus::Approved->value)
            ->whereBetween('paid_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->sum('amount')
            + (float) Donation::whereBetween('received_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->sum('amount'));
        $expenses = $months->map(fn ($m) => (float) Expense::whereBetween('spent_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->sum('amount'));

        return [
            'datasets' => [
                ['label' => 'Income (fees + donations)', 'data' => $income->all(), 'backgroundColor' => '#0b3d2a', 'borderRadius' => 8],
                ['label' => 'Expenses', 'data' => $expenses->all(), 'backgroundColor' => '#c9a45c', 'borderRadius' => 8],
            ],
            'labels' => $months->map(fn ($m) => $m->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
