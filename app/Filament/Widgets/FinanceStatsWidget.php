<?php

namespace App\Filament\Widgets;

use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\DueResource;
use App\Filament\Resources\PaymentResource;
use App\Models\Due;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Payment::class) ?? false;
    }

    protected function getStats(): array
    {
        $month = Payment::where('status', PaymentStatus::Approved->value)->whereBetween('reviewed_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');
        $last = Payment::where('status', PaymentStatus::Approved->value)->whereBetween('reviewed_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->sum('amount');
        $outstanding = Due::whereIn('status', DueStatus::outstanding())->selectRaw('COALESCE(SUM(amount - paid_amount),0) as s')->value('s');
        $overdue = Due::where('status', DueStatus::Overdue->value)->count();
        $exhausted = Due::where('status', DueStatus::Overdue->value)->where('overdue_reminders_count', '>=', 3)->count();
        $pending = Payment::where('status', PaymentStatus::PendingReview->value)->count();
        $change = $last > 0 ? round(($month - $last) / $last * 100) : null;

        return [
            Stat::make('Collected this month', number_format((float) $month, 2).' ETB')
                ->description($change === null ? 'No payments last month' : ($change >= 0 ? "+{$change}% vs last month" : "{$change}% vs last month"))
                ->descriptionIcon($change !== null && $change < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($change !== null && $change < 0 ? 'danger' : 'success'),
            Stat::make('Outstanding', number_format((float) $outstanding, 2).' ETB')->description('Unpaid + partially paid')
                ->descriptionIcon('heroicon-m-clock')->color('warning')->url(DueResource::getUrl()),
            Stat::make('Overdue dues', $overdue)->description($exhausted ? "{$exhausted} need personal follow-up" : 'Reminders running')
                ->descriptionIcon('heroicon-m-exclamation-triangle')->color($overdue ? 'danger' : 'success')->url(DueResource::getUrl()),
            Stat::make('Receipts to review', $pending)->description($pending ? 'Open the payments queue →' : 'Nothing waiting')
                ->descriptionIcon('heroicon-m-document-magnifying-glass')->color($pending ? 'warning' : 'success')
                ->url(PaymentResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'pending_review']]])),
        ];
    }
}
