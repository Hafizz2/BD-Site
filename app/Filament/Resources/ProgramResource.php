<?php

namespace App\Filament\Resources;

use App\Enums\ProgramType;
use App\Filament\Resources\ProgramResource\Pages;
use App\Filament\Resources\ProgramResource\RelationManagers\BatchesRelationManager;
use App\Filament\Support\Bilingual;
use App\Models\Program;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Programs';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Program')->schema([
                Bilingual::text('name', 'Name', true),
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    Select::make('type')->options(ProgramType::class)->required()->native(false),
                    Bilingual::iconSelect(),
                    TextInput::make('slug')->maxLength(150)->unique(ignoreRecord: true)->helperText('URL: /programs/{slug}')
                        ->dehydrateStateUsing(fn ($state, $get) => $state ?: Str::slug((string) $get('name'))),
                ])->columnSpanFull(),
                Bilingual::textarea('summary', 'Summary', false, 500, 2),
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    Toggle::make('is_active')->default(true)->label('Active'),
                    Toggle::make('show_on_website')->default(true)->label('Show on website'),
                    TextInput::make('sort')->numeric()->default(0),
                ])->columnSpanFull(),
            ]),
            Section::make('Website page')->collapsible()->schema([
                SpatieMediaLibraryFileUpload::make('cover')->collection('cover')->image()->imageEditor()->maxSize(5120),
                Bilingual::rich('description', 'Description', 'programs'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort')->defaultSort('sort')
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->collection('cover')->conversion('card')->label('')->square(),
                TextColumn::make('name')->searchable(['name', 'name_am'])->weight('semibold')->description(fn (Program $r) => $r->name_am),
                TextColumn::make('type')->badge(),
                TextColumn::make('batches_count')->counts('batches')->label('Batches')->badge()->color('gray'),
                TextColumn::make('open_batches_count')->counts('openBatches')->label('Open')->badge()->color('success'),
                ToggleColumn::make('is_active')->label('Active'),
                ToggleColumn::make('show_on_website')->label('Website'),
            ])
            ->filters([SelectFilter::make('type')->options(ProgramType::class)])
            ->recordActions([
                Action::make('site')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->label('')->url(fn (Program $r) => route('programs.show', $r))->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [BatchesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }
}
