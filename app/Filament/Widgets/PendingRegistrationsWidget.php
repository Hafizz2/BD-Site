<?php

namespace App\Filament\Widgets;

use App\Enums\RegistrationStatus;
use App\Filament\Resources\RegistrationResource;
use App\Models\Registration;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingRegistrationsWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 2];

    protected static ?string $heading = 'Registrations waiting for review';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Registration::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Registration::query()->with('batch.program')->where('status', RegistrationStatus::Pending->value)->latest())
            ->paginated([5])
            ->columns([
                TextColumn::make('full_name')->weight('semibold')->description(fn (Registration $r) => $r->batch?->label),
                TextColumn::make('phone'),
                TextColumn::make('created_at')->since(),
            ])
            ->recordActions([
                RegistrationResource::updateStatusAction(),
                Action::make('open')->icon('heroicon-o-eye')->url(fn (Registration $r) => RegistrationResource::getUrl('view', ['record' => $r])),
            ])
            ->emptyStateHeading('All caught up')->emptyStateIcon('heroicon-o-check-badge');
    }
}
