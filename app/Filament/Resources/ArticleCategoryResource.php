<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleCategoryResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\ArticleCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ArticleCategoryResource extends Resource
{
    protected static ?string $model = ArticleCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'article category';

    protected static ?string $navigationLabel = 'Article categories';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Bilingual::text('name', 'Name', true, 100),
            TextInput::make('slug')->maxLength(120)->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn ($state, $get) => $state ?: Str::slug((string) $get('name'))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort')->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->weight('semibold')->description(fn (ArticleCategory $r) => $r->name_am),
                TextColumn::make('slug')->color('gray'),
                TextColumn::make('articles_count')->counts('articles')->label('Articles')->badge(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageArticleCategories::route('/')];
    }
}
