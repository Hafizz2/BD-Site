<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Service card')->columnSpanFull()
                ->description('Shown on the home page and the Services page.')
                ->schema([
                    Bilingual::text('title', 'Title', true),
                    Bilingual::textarea('summary', 'Short summary (card text)', false, 500),
                    Bilingual::text('tag', 'Tag (e.g. "Seasonal · Intensive")', false, 100),
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        Bilingual::iconSelect(),
                        TextInput::make('slug')->helperText('URL: /services/{slug}. Leave empty to generate.')->maxLength(150)
                            ->unique(ignoreRecord: true)->dehydrateStateUsing(fn ($state, $get) => $state ?: Str::slug((string) $get('title'))),
                        TextInput::make('sort')->numeric()->default(0),
                    ])->columnSpanFull(),
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        Toggle::make('is_active')->label('Visible on website')->default(true),
                        Toggle::make('is_featured')->label('Featured (big dark card)'),
                        Select::make('program_id')->label('Linked program (shows open batches)')->relationship('program', 'name')->searchable()->preload(),
                    ])->columnSpanFull(),
                ]),
            Section::make('Detail page')->columnSpanFull()->schema([
                SpatieMediaLibraryFileUpload::make('cover')->collection('cover')->image()->imageEditor()->maxSize(4096)->label('Cover image'),
                Bilingual::rich('body', 'Full description', 'services'),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->collection('cover')->conversion('card')->circular()->label(''),
                TextColumn::make('title')->searchable()->weight('semibold')->description(fn (Service $r) => $r->title_am),
                TextColumn::make('tag')->badge()->color('gold'),
                TextColumn::make('program.name')->label('Program')->toggleable(),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                ToggleColumn::make('is_active')->label('Visible'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
