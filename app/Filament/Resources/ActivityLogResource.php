<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?string $modelLabel = 'activity';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        $flatten = fn ($v) => collect((array) $v)->map(fn ($x) => is_scalar($x) || $x === null ? (string) $x : json_encode($x, JSON_UNESCAPED_UNICODE))->all();

        return $schema->columns(3)->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('causer.name')->label('By')->placeholder('System'),
            TextEntry::make('event')->badge(),
            TextEntry::make('log_name')->label('Model'),
            TextEntry::make('subject_id')->label('Record ID'),
            TextEntry::make('description'),
            KeyValueEntry::make('old')->label('Before')->state(fn (Activity $r) => $flatten($r->properties['old'] ?? []))->columnSpanFull(),
            KeyValueEntry::make('new')->label('After')->state(fn (Activity $r) => $flatten($r->properties['attributes'] ?? $r->properties->except('old')->all()))->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->dateTime('d M Y H:i:s')->sortable(),
                TextColumn::make('causer.name')->label('By')->placeholder('System')->searchable(),
                TextColumn::make('event')->badge()->color(fn ($state) => match ($state) {
                    'created' => 'success', 'updated' => 'info', 'deleted' => 'danger', 'approved' => 'success', 'rejected' => 'danger', default => 'gray',
                }),
                TextColumn::make('log_name')->label('Model')->searchable(),
                TextColumn::make('subject_id')->label('ID'),
                TextColumn::make('description')->limit(40)->weight('semibold'),
            ])
            ->filters([
                SelectFilter::make('log_name')->label('Model')->options(fn () => Activity::query()->distinct()->pluck('log_name', 'log_name')->filter()),
                SelectFilter::make('event')->options(['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                SelectFilter::make('causer_id')->label('User')->options(fn () => \App\Models\User::orderBy('name')->pluck('name', 'id')),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageActivityLogs::route('/')];
    }
}
