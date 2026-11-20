<?php

namespace App\Filament\Resources;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Filament\Resources\NotificationLogResource\Pages;
use App\Models\NotificationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Delivery log';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['notifiable', 'announcement']))
            ->columns([
                TextColumn::make('created_at')->dateTime('d M H:i')->label('When')->sortable(),
                TextColumn::make('notifiable.full_name')->label('Recipient')->weight('semibold')->placeholder('—'),
                TextColumn::make('type')->badge(),
                TextColumn::make('announcement.title')->label('Announcement')->limit(30)->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('attempts'),
                TextColumn::make('error')->limit(50)->color('danger')->placeholder('—')->tooltip(fn (NotificationLog $r) => $r->error),
            ])
            ->filters([
                SelectFilter::make('status')->options(NotificationStatus::class),
                SelectFilter::make('type')->options(NotificationType::class),
                SelectFilter::make('announcement_id')->relationship('announcement', 'title')->label('Announcement'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageNotificationLogs::route('/')];
    }
}
