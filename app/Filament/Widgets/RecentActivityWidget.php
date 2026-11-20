<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Spatie\Activitylog\Models\Activity;

class RecentActivityWidget extends TableWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 2];

    protected static ?string $heading = 'Recent activity';

    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->with('causer')->latest())
            ->paginated([6])
            ->columns([
                TextColumn::make('created_at')->since(),
                TextColumn::make('causer.name')->label('By')->placeholder('System'),
                TextColumn::make('event')->badge()->color(fn ($state) => match ($state) { 'created' => 'success', 'deleted', 'rejected' => 'danger', 'approved' => 'success', default => 'gray' }),
                TextColumn::make('log_name')->label('What'),
            ]);
    }
}
