<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StatResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\Stat;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class StatResource extends Resource
{
    protected static ?string $model = Stat::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 9;

    protected static ?string $modelLabel = 'hero counter';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Bilingual::text('label', 'Label (e.g. "Students")', true, 60),
            Grid::make(['default' => 1, 'md' => 4])->schema([
                Select::make('auto_source')->label('Value source')->options(['' => 'Manual number'] + Stat::AUTO_SOURCES)->live()
                    ->helperText('Automatic values update from the database.'),
                TextInput::make('value')->numeric()->minValue(0)->default(0)->visible(fn (Get $get) => blank($get('auto_source'))),
                TextInput::make('suffix')->maxLength(10)->placeholder('+'),
                TextInput::make('sort')->numeric()->default(0),
            ])->columnSpanFull(),
            Toggle::make('is_active')->label('Visible')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort')->defaultSort('sort')
            ->description('The animated counters in the home page hero (first 4 are shown).')
            ->columns([
                TextColumn::make('label')->description(fn (Stat $r) => $r->label_am),
                TextColumn::make('shown')->label('Shown value')->state(fn (Stat $r) => number_format($r->resolvedValue()).$r->suffix)->weight('bold'),
                TextColumn::make('auto_source')->label('Source')->formatStateUsing(fn ($state) => Stat::AUTO_SOURCES[$state] ?? 'Manual')->badge(),
                ToggleColumn::make('is_active')->label('Visible'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageStats::route('/')];
    }
}
