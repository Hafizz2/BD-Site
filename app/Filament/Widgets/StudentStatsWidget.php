<?php

namespace App\Filament\Widgets;

use App\Enums\RegistrationStatus;
use App\Enums\StudentStatus;
use App\Filament\Resources\RegistrationResource;
use App\Filament\Resources\StudentResource;
use App\Models\Batch;
use App\Models\Registration;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Student::class) ?? false;
    }

    protected function getStats(): array
    {
        $total = Student::count();
        $active = Student::where('status', StudentStatus::Active->value)->count();
        $linked = Student::whereNotNull('telegram_chat_id')->whereNull('telegram_blocked_at')->count();
        $pending = Registration::where('status', RegistrationStatus::Pending->value)->count();
        $trend = collect(range(6, 0))->map(fn ($d) => Student::whereDate('created_at', now()->subDays($d))->count())->all();

        return [
            Stat::make('Students', number_format($total))
                ->description(number_format($active).' active')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart($trend)->color('primary')
                ->url(StudentResource::getUrl()),
            Stat::make('Pending registrations', $pending)
                ->description($pending ? 'Waiting for review' : 'All reviewed')
                ->descriptionIcon($pending ? 'heroicon-m-inbox-arrow-down' : 'heroicon-m-check-circle')
                ->color($pending ? 'warning' : 'success')
                ->url(RegistrationResource::getUrl()),
            Stat::make('Open batches', Batch::openForRegistration()->count())
                ->description(Batch::where('status', 'active')->count().' running now')
                ->descriptionIcon('heroicon-m-rectangle-stack')->color('gold'),
            Stat::make('Linked to Telegram', $total ? round($linked / $total * 100).'%' : '—')
                ->description(number_format($linked).' of '.number_format($total).' students')
                ->descriptionIcon('heroicon-m-paper-airplane')->color('info'),
        ];
    }
}
