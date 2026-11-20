<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'FAQ';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Bilingual::text('question', 'Question', true),
            Bilingual::textarea('answer', 'Answer', true, 3000, 5),
            TextInput::make('category')->maxLength(100),
            Toggle::make('is_published')->label('Published')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort')->defaultSort('sort')
            ->columns([
                TextColumn::make('question')->searchable()->wrap()->weight('semibold')->description(fn (Faq $r) => $r->question_am),
                TextColumn::make('category')->badge(),
                ToggleColumn::make('is_published')->label('Published'),
            ])
            ->recordActions([EditAction::make()->slideOver(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageFaqs::route('/')];
    }
}
