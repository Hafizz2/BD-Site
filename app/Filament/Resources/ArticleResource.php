<?php

namespace App\Filament\Resources;

use App\Enums\ArticleStatus;
use App\Filament\Resources\ArticleResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\Article;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use UnitEnum;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Article')->schema([
                    Bilingual::text('title', 'Title', true),
                    Bilingual::textarea('excerpt', 'Excerpt (shown on cards & as the intro)', false, 500, 2),
                    Bilingual::rich('body', 'Body', 'articles'),
                ]),
                Section::make('SEO')->collapsed()->schema([
                    TextInput::make('seo_title')->maxLength(70)->helperText('Defaults to the title.'),
                    Textarea::make('seo_description')->maxLength(300)->rows(2)->helperText('Defaults to the excerpt.'),
                ]),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Publishing')->schema([
                    Select::make('status')->options(ArticleStatus::class)->default(ArticleStatus::Draft)->required()->native(false),
                    DateTimePicker::make('published_at')->label('Publish date')->helperText('Future date = scheduled.')->native(false),
                    Toggle::make('is_featured')->label('Featured (big story on Articles page)'),
                    Select::make('article_category_id')->label('Category')->relationship('category', 'name')->searchable()->preload()
                        ->createOptionForm([TextInput::make('name')->required(), TextInput::make('name_am')->label('Name · አማርኛ')]),
                    Select::make('author_id')->label('Author (team member)')->relationship('author', 'full_name')->searchable()->preload(),
                    TextInput::make('author_name')->label('…or author name')->maxLength(120),
                    TextInput::make('slug')->maxLength(200)->unique(ignoreRecord: true)->helperText('URL: /articles/{slug}')
                        ->dehydrateStateUsing(fn ($state, $get) => $state ?: Str::slug((string) $get('title')).'-'.Str::lower(Str::random(4))),
                ]),
                Section::make('Cover image')->schema([
                    SpatieMediaLibraryFileUpload::make('cover')->collection('cover')->image()->imageEditor()->maxSize(5120)->hiddenLabel(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->collection('cover')->conversion('card')->label('')->square(),
                TextColumn::make('title')->searchable(['title', 'title_am'])->weight('semibold')->wrap()->description(fn (Article $r) => $r->category?->name),
                TextColumn::make('status')->badge(),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                TextColumn::make('published_at')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('views')->numeric()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ArticleStatus::class),
                SelectFilter::make('article_category_id')->label('Category')->relationship('category', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (Article $r) => route('articles.show', $r))->openUrlInNewTab()
                    ->visible(fn (Article $r) => $r->status === ArticleStatus::Published),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make(), ForceDeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
