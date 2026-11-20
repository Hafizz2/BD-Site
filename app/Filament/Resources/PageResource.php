<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page header')
                ->description(fn (?Page $record) => $record?->isSystem()
                    ? "Built-in page \"{$record->key}\" — this controls its title, intro and SEO. The cards/lists on it come from their own sections in the menu."
                    : 'A free-form page served at /p/{slug}. Add it to the menu in Settings → Navigation.')
                ->schema([
                    Bilingual::text('eyebrow', 'Small label above the title', false, 100),
                    Bilingual::text('title', 'Title', true),
                    Bilingual::textarea('intro', 'Intro text', false, 600, 2),
                    TextInput::make('slug')->required()->maxLength(150)->unique(ignoreRecord: true)
                        ->disabled(fn (?Page $record) => $record?->isSystem())
                        ->dehydrateStateUsing(fn ($state, $get) => $state ?: Str::slug((string) $get('title'))),
                    SpatieMediaLibraryFileUpload::make('cover')->collection('cover')->image()->maxSize(5120)->label('Header background image (optional)'),
                    Toggle::make('is_published')->label('Published')->default(true),
                ]),
            Section::make('Content')->schema([Bilingual::rich('body', 'Body', 'pages')]),
            Section::make('SEO')->collapsed()->schema([
                TextInput::make('seo_title')->maxLength(70),
                Textarea::make('seo_description')->maxLength(300)->rows(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('key')
            ->columns([
                TextColumn::make('title')->searchable()->weight('semibold')->description(fn (Page $r) => $r->title_am),
                TextColumn::make('key')->label('Type')->formatStateUsing(fn ($state) => $state ? 'Built-in · '.$state : 'Custom')->badge()
                    ->color(fn ($state) => $state ? 'gray' : 'gold')->placeholder('Custom'),
                TextColumn::make('slug')->color('gray'),
                IconColumn::make('is_published')->boolean()->label('Published'),
                TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->recordActions([
                Action::make('open')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (Page $r) => $r->isSystem() ? (\Illuminate\Support\Facades\Route::has($r->key) ? route($r->key) : (\Illuminate\Support\Facades\Route::has($r->key.'.index') ? route($r->key.'.index') : url('/'))) : route('page', $r))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Page $r) => $r->isSystem()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
