<?php

namespace App\Filament\Resources;

use App\Enums\DueStatus;
use App\Exports\DuesExport;
use App\Filament\Resources\DueResource\Pages;
use App\Models\Due;
use App\Models\Member;
use App\Models\Student;
use App\Services\Finance\DuesGenerator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class DueResource extends Resource
{
    protected static ?string $model = Due::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            MorphToSelect::make('payable')->label('Owed by')->types([
                MorphToSelect\Type::make(Student::class)->titleAttribute('full_name'),
                MorphToSelect\Type::make(Member::class)->titleAttribute('full_name'),
            ])->searchable()->required()->columnSpanFull()->hiddenOn('edit'),
            Select::make('fee_plan_id')->relationship('feePlan', 'name')->required()->searchable()->preload()->hiddenOn('edit'),
            TextInput::make('period')->required()->default(now()->format('Y-m'))->maxLength(30)->helperText('e.g. 2026-11 or batch-5')->hiddenOn('edit'),
            TextInput::make('description')->maxLength(150),
            TextInput::make('amount')->required()->numeric()->minValue(0)->prefix('ETB'),
            DatePicker::make('due_date')->required()->native(false),
            Select::make('status')->options(DueStatus::class)->default(DueStatus::Unpaid)->required()->native(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['payable', 'feePlan']))
            ->columns([
                TextColumn::make('payable.full_name')->label('Owed by')->searchable(query: fn (Builder $query, string $search) => $query->whereHasMorph('payable', [Student::class, Member::class], fn ($p) => $p->where('full_name', 'like', "%{$search}%")))
                    ->weight('semibold')->description(fn (Due $r) => $r->payable_type === 'member' ? 'Member' : 'Student'),
                TextColumn::make('label')->label('For')->state(fn (Due $r) => $r->label)->wrap(),
                TextColumn::make('amount')->money('ETB')->sortable(),
                TextColumn::make('paid_amount')->money('ETB')->label('Paid'),
                TextColumn::make('due_date')->date('d M Y')->sortable()->color(fn (Due $r) => $r->due_date->isPast() && in_array($r->status->value, DueStatus::outstanding(), true) ? 'danger' : null),
                TextColumn::make('status')->badge(),
                TextColumn::make('overdue_reminders_count')->label('Reminders')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(DueStatus::class)->multiple(),
                SelectFilter::make('fee_plan_id')->relationship('feePlan', 'name')->label('Fee plan'),
                SelectFilter::make('payable_type')->options(['student' => 'Students', 'member' => 'Members'])->label('Type'),
                Filter::make('outstanding')->toggle()->query(fn ($query) => $query->whereIn('status', DueStatus::outstanding())),
                Filter::make('reminders_exhausted')->label('Reminders exhausted (follow up!)')->toggle()
                    ->query(fn ($query) => $query->where('status', DueStatus::Overdue->value)->where('overdue_reminders_count', '>=', 3)),
            ])
            ->headerActions([
                Action::make('generate')->label('Generate dues now')->icon('heroicon-o-bolt')->color('gold')->requiresConfirmation()
                    ->modalDescription('Creates this month\'s dues for all active monthly plans and flags overdue dues. Safe to run again.')
                    ->visible(fn () => auth()->user()->can('create', Due::class))
                    ->action(function () {
                        $n = app(DuesGenerator::class)->generateMonthly();
                        $o = app(DuesGenerator::class)->markOverdue();
                        Notification::make()->success()->title("{$n} dues created, {$o} flagged overdue")->send();
                    }),
                Action::make('export')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn ($livewire) => Excel::download(new DuesExport($livewire->getFilteredTableQuery()), 'dues-'.now()->format('Ymd').'.xlsx')),
            ])
            ->recordActions([
                Action::make('waive')->icon('heroicon-o-hand-raised')->color('gray')
                    ->visible(fn (Due $r) => in_array($r->status->value, DueStatus::outstanding(), true) && auth()->user()->can('update', $r))
                    ->schema([Textarea::make('reason')->required()->maxLength(250)])
                    ->action(fn (Due $r, array $data) => $r->forceFill(['status' => DueStatus::Waived, 'waived_by' => auth()->id(), 'waive_reason' => $data['reason']])->save()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('waive')->label('Waive selected')->icon('heroicon-o-hand-raised')->color('warning')
                        ->schema([Textarea::make('reason')->required()->maxLength(250)])
                        ->action(function (Collection $records, array $data) {
                            $records->filter(fn (Due $d) => in_array($d->status->value, DueStatus::outstanding(), true))
                                ->each(fn (Due $d) => $d->forceFill(['status' => DueStatus::Waived, 'waived_by' => auth()->id(), 'waive_reason' => $data['reason']])->save());
                            Notification::make()->success()->title('Dues waived')->send();
                        })->deselectRecordsAfterCompletion(),
                    BulkAction::make('export')->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => Excel::download(new DuesExport(Due::whereKey($records->modelKeys())), 'dues-selected.xlsx')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageDues::route('/')];
    }
}
