<?php

namespace App\Filament\Resources;

use App\Enums\DueStatus;
use App\Enums\Gender;
use App\Enums\StudentSource;
use App\Enums\StudentStatus;
use App\Exports\StudentsExport;
use App\Filament\Actions\TelegramActions;
use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Program;
use App\Models\Student;
use App\Support\PhoneNumber;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'phone', 'guardian_phone', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        $phone = fn (string $name, string $label, bool $required = false) => TextInput::make($name)->label($label)->tel()->required($required)->maxLength(20)
            ->rule(fn () => function ($attr, $value, $fail) {
                if (filled($value) && ! PhoneNumber::isValid($value)) {
                    $fail('Enter a valid Ethiopian mobile number (09…, 07…, +2519…).');
                }
            })
            ->dehydrateStateUsing(fn ($state) => PhoneNumber::normalize($state) ?? $state);

        return $schema->components([
            Section::make('Student')->columns(3)->schema([
                TextInput::make('full_name')->required()->maxLength(150)->columnSpan(2),
                Select::make('gender')->options(Gender::class)->native(false),
                $phone('phone', 'Phone', true)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at')),
                $phone('alt_phone', 'Alternative phone'),
                TextInput::make('email')->email()->maxLength(150),
                DatePicker::make('date_of_birth')->native(false)->maxDate(now()->subYears(3))->live(onBlur: true),
                TextInput::make('school')->label('School / University')->maxLength(150),
                TextInput::make('grade_or_year')->label('Grade / Year')->maxLength(50),
                TextInput::make('sub_city')->maxLength(100),
                TextInput::make('address')->maxLength(200)->columnSpan(2),
            ]),
            Section::make('Guardian & consent')->columns(3)
                ->description(fn (Get $get) => $get('date_of_birth') && \Carbon\Carbon::parse($get('date_of_birth'))->age < 18 ? '⚠ This student is under 18 — guardian consent is required.' : null)
                ->schema([
                    TextInput::make('guardian_name')->maxLength(150),
                    $phone('guardian_phone', 'Guardian phone'),
                    TextInput::make('guardian_relation')->maxLength(50),
                    Toggle::make('guardian_consent')->label('Guardian consent given')
                        ->required(fn (Get $get) => $get('date_of_birth') && \Carbon\Carbon::parse($get('date_of_birth'))->age < 18)
                        ->accepted(fn (Get $get) => $get('date_of_birth') && \Carbon\Carbon::parse($get('date_of_birth'))->age < 18),
                    Toggle::make('photo_consent')->label('Photo consent'),
                ]),
            Section::make('Status')->columns(3)->schema([
                Select::make('status')->options(StudentStatus::class)->default(StudentStatus::Active)->required()->native(false),
                Select::make('source')->options(StudentSource::class)->default(StudentSource::Manual)->disabledOn('edit')->native(false),
                Select::make('locale')->label('Bot language')->options(['am' => 'አማርኛ', 'en' => 'English'])->default('am')->native(false),
                Textarea::make('notes')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->schema([
                TextEntry::make('full_name')->weight('bold')->size('lg')->columnSpan(2),
                TextEntry::make('status')->badge(),
                TextEntry::make('source')->badge()->color('gray'),
                TextEntry::make('phone')->formatStateUsing(fn ($state) => PhoneNumber::format($state))->copyable()->icon('heroicon-o-phone'),
                TextEntry::make('alt_phone')->placeholder('—'),
                TextEntry::make('email')->placeholder('—')->copyable(),
                TextEntry::make('gender')->badge(),
                TextEntry::make('date_of_birth')->date('d M Y')->placeholder('—')
                    ->suffix(fn (Student $r) => $r->date_of_birth ? ' ('.$r->date_of_birth->age.' yrs)' : ''),
                TextEntry::make('school')->placeholder('—'),
                TextEntry::make('grade_or_year')->label('Grade / Year')->placeholder('—'),
                TextEntry::make('sub_city')->placeholder('—'),
            ]),
            Grid::make(['default' => 1, 'lg' => 2])->columnSpanFull()->schema([
                Section::make('Guardian & consent')->columns(2)->schema([
                    TextEntry::make('guardian_name')->placeholder('—'),
                    TextEntry::make('guardian_phone')->placeholder('—')->formatStateUsing(fn ($state) => PhoneNumber::format($state)),
                    TextEntry::make('guardian_relation')->placeholder('—'),
                    IconEntry::make('guardian_consent')->boolean(),
                    IconEntry::make('photo_consent')->boolean(),
                    TextEntry::make('consent_at')->dateTime()->placeholder('—'),
                ]),
                Section::make('Telegram & finance')->columns(2)->schema([
                    IconEntry::make('telegram_linked')->label('Linked to bot')->state(fn (Student $r) => filled($r->telegram_chat_id))->boolean(),
                    TextEntry::make('telegram_linked_at')->since()->placeholder('Not linked'),
                    TextEntry::make('telegram_blocked_at')->label('Blocked the bot')->since()->placeholder('No')->color('danger'),
                    TextEntry::make('balance')->label('Outstanding')->state(fn (Student $r) => (float) $r->outstandingDues()->sum(\DB::raw('amount - paid_amount')))->money('ETB')->weight('bold')
                        ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                    TextEntry::make('import.original_filename')->label('Imported from')->placeholder('—'),
                    TextEntry::make('registered_at')->dateTime('d M Y')->placeholder('—'),
                ]),
            ]),
            Section::make('Notes')->schema([TextEntry::make('notes')->hiddenLabel()->placeholder('No notes')])->collapsible()->collapsed(fn (Student $r) => blank($r->notes)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // One card per person (resources/views/filament/cards/student.blade.php).
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->with(['latestEnrollment.batch.program', 'member']))
            ->columns([
                \Filament\Tables\Columns\Layout\View::make('filament.cards.student'),
            ])
            ->contentGrid(['md' => 2, '2xl' => 3])
            ->searchable(['full_name', 'phone'])
            ->searchPlaceholder('Search name or phone')
            ->recordUrl(null)
            ->filters([
                SelectFilter::make('program')->label('Program')->options(fn () => Program::orderBy('sort')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($q, $v) => $q->whereHas('enrollments.batch', fn ($b) => $b->where('program_id', $v)))),
                SelectFilter::make('batch')->label('Batch')->relationship('batches', 'name', fn ($query) => $query->with('program'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->label)->searchable()->preload(),
                SelectFilter::make('status')->options(StudentStatus::class),
                SelectFilter::make('gender')->options(Gender::class),
                SelectFilter::make('source')->options(StudentSource::class),
                SelectFilter::make('import_id')->relationship('import', 'original_filename')->label('Import file'),
                TernaryFilter::make('telegram')->label('Linked to Telegram')
                    ->queries(true: fn ($query) => $query->whereNotNull('telegram_chat_id'), false: fn ($query) => $query->whereNull('telegram_chat_id')),
                Filter::make('unpaid')->label('Has unpaid dues')->toggle()
                    ->query(fn ($query) => $query->whereHas('dues', fn ($d) => $d->whereIn('status', DueStatus::outstanding()))),
                TrashedFilter::make(),
            ])
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    self::makeMemberAction(),
                    TelegramActions::copyLink(),
                    TelegramActions::qr(),
                    TelegramActions::message(),
                ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('export')->label('Export (filtered)')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn ($livewire) => Excel::download(new StudentsExport($livewire->getFilteredTableQuery()), 'students-'.now()->format('Ymd-His').'.xlsx')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export_selected')->label('Export selected')->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => Excel::download(new StudentsExport(Student::whereKey($records->modelKeys())), 'students-selected.xlsx')),
                    TelegramActions::bulkMessage(),
                    BulkAction::make('status')->label('Change status')->icon('heroicon-o-arrow-path')
                        ->schema([Select::make('status')->options(StudentStatus::class)->required()])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['status' => $data['status']]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['latestEnrollment.batch.program'])->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
            RelationManagers\DuesRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
            RelationManagers\NotificationLogsRelationManager::class,
        ];
    }

    /** After finishing the course and agreeing to join the jemea, a student becomes a member and picks a team. */
    public static function makeMemberAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('makeMember')
            ->label('Make member')
            ->icon('heroicon-o-user-plus')
            ->color('success')
            ->visible(fn (Student $record) => ! $record->member()->exists() && auth()->user()->hasAnyRole(['super_admin', 'admin']))
            ->modalDescription(fn (Student $record) => "{$record->full_name} will be added to the jemea members.")
            ->schema([
                Select::make('team')->options(\App\Enums\MemberTeam::class)->native(false)
                    ->helperText('Can be set later when they pick a team.'),
                TextInput::make('role_title')->label('Role / title (optional)')->maxLength(100),
                Select::make('monthly_contribution_plan_id')->label('Monthly contribution (only if they agreed to give)')
                    ->options(fn () => \App\Models\FeePlan::where('applies_to', \App\Enums\FeeAppliesTo::MemberContribution->value)->where('is_active', true)->pluck('name', 'id'))
                    ->placeholder('Does not contribute'),
            ])
            ->action(function (Student $record, array $data) {
                \App\Models\Member::create([
                    'student_id' => $record->id,
                    'full_name' => $record->full_name,
                    'phone' => $record->phone,
                    'email' => $record->email,
                    'team' => $data['team'] ?? null,
                    'role_title' => $data['role_title'] ?? null,
                    'monthly_contribution_plan_id' => $data['monthly_contribution_plan_id'] ?? null,
                    'locale' => $record->locale ?: 'am',
                    'telegram_chat_id' => $record->telegram_chat_id,
                    'telegram_linked_at' => $record->telegram_linked_at,
                    'is_active' => true,
                ]);

                \Filament\Notifications\Notification::make()->success()->title("{$record->full_name} is now a member")->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
