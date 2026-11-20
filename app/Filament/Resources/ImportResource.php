<?php

namespace App\Filament\Resources;

use App\Enums\ImportStatus;
use App\Filament\Pages\ImportStudents;
use App\Filament\Resources\ImportResource\Pages;
use App\Filament\Resources\StudentResource;
use App\Models\Import;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Import history';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function downloadReportAction(): Action
    {
        return Action::make('report')->label('Error report')->icon('heroicon-o-arrow-down-tray')->color('danger')
            ->visible(fn (Import $r) => filled($r->error_report_file))
            ->action(fn (Import $r) => Storage::disk('local')->download($r->error_report_file, 'import-'.$r->id.'-problems.xlsx'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->schema([
                TextEntry::make('original_filename')->label('File')->weight('bold')->columnSpan(2),
                TextEntry::make('status')->badge(),
                TextEntry::make('progress')->state(fn (Import $r) => $r->progress().'%')->label('Progress'),
                TextEntry::make('batch.label')->label('Target batch')->placeholder('No batch'),
                TextEntry::make('duplicate_rule')->badge()->color('gray'),
                TextEntry::make('importer.name')->label('Imported by'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('total_rows')->label('Rows'),
                TextEntry::make('created_count')->label('Created')->color('success')->weight('bold'),
                TextEntry::make('updated_count')->label('Updated')->color('info')->weight('bold'),
                TextEntry::make('skipped_count')->label('Skipped (existing)')->color('gray')->weight('bold'),
                TextEntry::make('failed_count')->label('Failed')->color('danger')->weight('bold'),
                TextEntry::make('error_message')->columnSpanFull()->color('danger')->visible(fn (Import $r) => filled($r->error_message)),
            ]),
            Section::make('Problems')->visible(fn (Import $r) => ! empty($r->failures))->collapsible()->schema([
                RepeatableEntry::make('failures')->hiddenLabel()->columns(3)->schema([
                    TextEntry::make('row')->label('Sheet row'),
                    TextEntry::make('level')->badge()->color(fn ($state) => $state === 'error' ? 'danger' : 'warning'),
                    TextEntry::make('reason'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->poll(fn () => Import::whereIn('status', [ImportStatus::Pending->value, ImportStatus::Processing->value])->exists() ? '3s' : null)
            ->columns([
                TextColumn::make('created_at')->dateTime('d M Y H:i')->label('When'),
                TextColumn::make('original_filename')->label('File')->weight('semibold')->description(fn (Import $r) => $r->batch?->label),
                TextColumn::make('status')->badge(),
                TextColumn::make('progress')->state(fn (Import $r) => $r->progress().'%'),
                TextColumn::make('created_count')->label('New')->color('success'),
                TextColumn::make('updated_count')->label('Upd.')->color('info'),
                TextColumn::make('skipped_count')->label('Skip'),
                TextColumn::make('failed_count')->label('Fail')->color('danger'),
                TextColumn::make('importer.name')->label('By')->toggleable(),
            ])
            ->headerActions([
                Action::make('new')->label('Import students')->icon('heroicon-o-arrow-up-tray')->url(ImportStudents::getUrl()),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('students')->icon('heroicon-o-user-group')->color('gray')
                    ->url(fn (Import $r) => StudentResource::getUrl('index', ['tableFilters' => ['import_id' => ['value' => $r->id]]])),
                self::downloadReportAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImports::route('/'),
            'view' => Pages\ViewImport::route('/{record}'),
        ];
    }
}
