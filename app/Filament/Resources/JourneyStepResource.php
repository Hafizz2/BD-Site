<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JourneyStepResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\JourneyStep;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class JourneyStepResource extends Resource
{
    protected static ?string $model = JourneyStep::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'journey step';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Bilingual::text('title', 'Step', true, 100),
            Bilingual::textarea('description', 'Description', false, 500, 2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort')->defaultSort('sort')
            ->description('"Every journey starts with a step" — the steps shown on the home and About pages. Drag to reorder.')
            ->columns([
                TextColumn::make('sort')->label('#')->width('3rem'),
                TextColumn::make('title')->weight('semibold')->description(fn (JourneyStep $r) => $r->title_am),
                TextColumn::make('description')->limit(80)->wrap(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageJourneySteps::route('/')];
    }
}
