<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GalleryAlbumResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\GalleryAlbum;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class GalleryAlbumResource extends Resource
{
    protected static ?string $model = GalleryAlbum::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Gallery';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Album')->schema([
                Bilingual::text('title', 'Title', true),
                Bilingual::textarea('description', 'Description', false, 1000, 2),
                Grid::make(['default' => 1, 'md' => 4])->schema([
                    DatePicker::make('taken_on')->native(false),
                    Select::make('program_id')->relationship('program', 'name')->searchable()->preload(),
                    Select::make('event_id')->relationship('event', 'title')->searchable()->preload(),
                    TextInput::make('slug')->maxLength(200)->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn ($state, $get) => $state ?: Str::slug((string) $get('title')).'-'.Str::lower(Str::random(4))),
                ])->columnSpanFull(),
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    Toggle::make('is_published')->label('Published')->default(true),
                    Toggle::make('is_featured')->label('Featured on home page'),
                ])->columnSpanFull(),
            ]),
            Section::make('Privacy')
                ->description('Photos of minors are only published once Media confirms parental photo consent.')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Toggle::make('contains_minors')->label('This album contains photos of minors (under 18)')->live(),
                    Toggle::make('consent_confirmed')->label('Parental photo consent confirmed for everyone pictured')
                        ->visible(fn (Get $get) => (bool) $get('contains_minors'))
                        ->disabled(fn () => ! auth()->user()?->hasAnyRole(['super_admin', 'media'])),
                ]),
            Section::make('Photos')->schema([
                SpatieMediaLibraryFileUpload::make('photos')->collection('photos')->multiple()->reorderable()->image()
                    ->maxFiles(200)->maxSize(8192)->panelLayout('grid')->hiddenLabel()->appendFiles()
                    ->customProperties(['caption' => null]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort')->defaultSort('sort')
            ->columns([
                SpatieMediaLibraryImageColumn::make('photos')->collection('photos')->conversion('thumb')->label('')->square()->limit(3)->stacked(),
                TextColumn::make('title')->searchable(['title', 'title_am'])->weight('semibold'),
                TextColumn::make('taken_on')->date('M Y')->sortable(),
                TextColumn::make('media_count')->counts('media')->label('Photos')->badge(),
                IconColumn::make('visible')->label('Public')->state(fn (GalleryAlbum $r) => $r->isPubliclyVisible())->boolean()
                    ->tooltip(fn (GalleryAlbum $r) => $r->contains_minors && ! $r->consent_confirmed ? 'Hidden: minors without confirmed consent' : null),
                IconColumn::make('contains_minors')->label('Minors')->boolean()->trueColor('warning')->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('contains_minors')->label('Contains minors'),
                TernaryFilter::make('consent_confirmed')->label('Consent confirmed'),
            ])
            ->recordActions([
                Action::make('view')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (GalleryAlbum $r) => route('gallery.show', $r))->openUrlInNewTab()->visible(fn (GalleryAlbum $r) => $r->isPubliclyVisible()),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleryAlbums::route('/'),
            'create' => Pages\CreateGalleryAlbum::route('/create'),
            'edit' => Pages\EditGalleryAlbum::route('/{record}/edit'),
        ];
    }
}
