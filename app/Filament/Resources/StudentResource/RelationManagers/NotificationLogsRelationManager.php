<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'notificationLogs';

    protected static ?string $title = 'Notifications';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-paper-airplane';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime('d M Y H:i')->label('When')->weight('semibold'),
                TextColumn::make('type')->badge(),
                TextColumn::make('announcement.title')->label('Announcement')->placeholder('—')->limit(40),
                TextColumn::make('status')->badge(),
                TextColumn::make('error')->limit(60)->placeholder('—')->color('danger'),
            ]);
    }
}
