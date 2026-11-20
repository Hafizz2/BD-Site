<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event')->schema([
                Bilingual::text('title', 'Title', true),
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    DateTimePicker::make('starts_at')->required()->seconds(false)->native(false),
                    DateTimePicker::make('ends_at')->seconds(false)->native(false)->after('starts_at'),
                ])->columnSpanFull(),
                Bilingual::text('location', 'Location'),
                Bilingual::textarea('summary', 'Summary', false, 500, 2),
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    TextInput::make('registration_url')->url()->label('External registration link')->maxLength(255),
                    Select::make('program_id')->relationship('program', 'name')->searchable()->preload(),
                    TextInput::make('slug')->maxLength(200)->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn ($state, $get) => $state ?: Str::slug((string) $get('title')).'-'.Str::lower(Str::random(4))),
                ])->columnSpanFull(),
                Grid::make(2)->schema([
                    Toggle::make('is_published')->label('Published')->default(true),
                    Toggle::make('is_featured')->label('Featured'),
                ])->columnSpanFull(),
            ]),
            Section::make('Details')->schema([
                SpatieMediaLibraryFileUpload::make('cover')->collection('cover')->image()->imageEditor()->maxSize(5120),
                Bilingual::rich('description', 'Description', 'events'),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('starts_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->collection('cover')->conversion('card')->label('')->square(),
                TextColumn::make('title')->searchable(['title', 'title_am'])->weight('semibold')->wrap()->description(fn (Event $r) => $r->location),
                TextColumn::make('starts_at')->dateTime('D d M Y · H:i')->sortable()
                    ->color(fn (Event $r) => $r->starts_at->isFuture() ? 'success' : 'gray'),
                TextColumn::make('program.name')->toggleable(),
                IconColumn::make('is_published')->boolean()->label('Published'),
            ])
            ->filters([
                TernaryFilter::make('upcoming')->queries(
                    true: fn ($query) => $query->where('starts_at', '>=', now()),
                    false: fn ($query) => $query->where('starts_at', '<', now()),
                ),
            ])
            ->recordActions([
                Action::make('view')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->url(fn (Event $r) => route('events.show', $r))->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
