<?php

namespace App\Filament\Resources;

use App\Enums\ExpenseCategory;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('category')->options(ExpenseCategory::class)->required()->native(false),
            TextInput::make('amount')->required()->numeric()->minValue(0.01)->prefix('ETB'),
            DatePicker::make('spent_at')->required()->default(now())->native(false),
            TextInput::make('description')->required()->maxLength(255),
            Select::make('program_id')->relationship('program', 'name')->searchable()->preload(),
            Select::make('batch_id')->relationship('batch', 'name', fn ($query) => $query->with('program'))->getOptionLabelFromRecordUsing(fn ($record) => $record->label)->searchable()->preload(),
            SpatieMediaLibraryFileUpload::make('receipt')->collection('receipt')->disk('private')->visibility('private')
                ->acceptedFileTypes(['image/*', 'application/pdf'])->maxSize(8192)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('spent_at', 'desc')
            ->columns([
                TextColumn::make('spent_at')->date('d M Y')->sortable(),
                TextColumn::make('category')->badge(),
                TextColumn::make('description')->searchable()->wrap(),
                TextColumn::make('amount')->money('ETB')->sortable()->weight('bold')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('ETB')),
                TextColumn::make('program.name')->toggleable(),
                TextColumn::make('recorder.name')->label('Recorded by')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->options(ExpenseCategory::class),
                SelectFilter::make('program_id')->relationship('program', 'name'),
                Filter::make('spent_between')->schema([
                    DatePicker::make('from')->native(false), DatePicker::make('until')->native(false),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn ($q, $v) => $q->whereDate('spent_at', '>=', $v))
                    ->when($data['until'] ?? null, fn ($q, $v) => $q->whereDate('spent_at', '<=', $v))),
            ])
            ->recordActions([
                Action::make('receipt')->icon('heroicon-o-photo')->color('gray')->visible(fn (Expense $r) => $r->hasMedia('receipt'))
                    ->url(fn (Expense $r) => $r->receiptUrl())->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageExpenses::route('/')];
    }
}
