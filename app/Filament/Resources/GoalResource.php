<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoalResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\Goal;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class GoalResource extends Resource
{
    protected static ?string $model = Goal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Bilingual::text('title', 'Goal', true),
            Bilingual::textarea('description', 'Description', false, 1000),
            Bilingual::text('horizon', 'Time frame (e.g. "2026", "Long term")', false, 100),
            Grid::make(['default' => 1, 'md' => 4])->schema([
                Bilingual::iconSelect(),
                TextInput::make('progress')->numeric()->minValue(0)->maxValue(100)->suffix('%')->helperText('Optional progress bar'),
                TextInput::make('sort')->numeric()->default(0),
                Toggle::make('is_active')->label('Visible')->default(true)->inline(false),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort')->defaultSort('sort')
            ->columns([
                TextColumn::make('title')->searchable()->weight('semibold')->description(fn (Goal $r) => $r->title_am),
                TextColumn::make('horizon')->badge()->color('gold'),
                TextColumn::make('progress')->suffix('%')->placeholder('—'),
                ToggleColumn::make('is_active')->label('Visible'),
            ])
            ->recordActions([EditAction::make()->slideOver(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageGoals::route('/')];
    }
}
