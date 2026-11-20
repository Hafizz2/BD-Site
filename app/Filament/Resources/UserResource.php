<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Admin users';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true)->maxLength(150),
            TextInput::make('phone')->tel()->maxLength(20),
            TextInput::make('password')->password()->revealable()->minLength(8)->maxLength(100)
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave empty to keep the current password.' : null),
            Select::make('roles')->relationship('roles', 'name')->multiple()->preload()->required()
                ->getOptionLabelFromRecordUsing(fn ($record) => str($record->name)->replace('_', ' ')->title()),
            Toggle::make('is_active')->default(true)->inline(false)->helperText('Inactive users cannot log in.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->weight('semibold'),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('roles.name')->badge()->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->title()),
                TextColumn::make('member.full_name')->label('Member')->placeholder('—'),
                TextColumn::make('last_login_at')->since()->placeholder('Never'),
                ToggleColumn::make('is_active')->label('Active')->disabled(fn (User $r) => $r->id === auth()->id()),
            ])
            ->filters([SelectFilter::make('roles')->relationship('roles', 'name')])
            ->recordActions([EditAction::make(), DeleteAction::make()->hidden(fn (User $r) => $r->id === auth()->id())]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
