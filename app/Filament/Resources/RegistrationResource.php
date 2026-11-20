<?php

namespace App\Filament\Resources;

use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Filament\Resources\RegistrationResource\Pages;
use App\Models\Batch;
use App\Models\Registration;
use App\Support\RegistrationForm;
use App\Models\Student;
use App\Services\RegistrationService;
use App\Support\PhoneNumber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class RegistrationResource extends Resource
{
    protected static ?string $model = Registration::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getNavigationBadge(): ?string
    {
        $n = Registration::where('status', RegistrationStatus::Pending->value)->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                Select::make('batch_id')->relationship('batch', 'name', fn ($query) => $query->with('program'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->label)->required()->searchable()->preload()->columnSpan(2),
                Select::make('status')->options(RegistrationStatus::class)->disabled()->native(false),
                TextInput::make('full_name')->required()->maxLength(150),
                Select::make('gender')->options(Gender::class)->native(false),
                DatePicker::make('date_of_birth')->native(false),
                TextInput::make('phone')->required()->tel()->dehydrateStateUsing(fn ($state) => PhoneNumber::normalize($state) ?? $state),
                TextInput::make('alt_phone')->tel(),
                TextInput::make('email')->email(),
                TextInput::make('school'),
                TextInput::make('grade_or_year'),
                Select::make('sub_city')->options(array_combine(RegistrationForm::SUB_CITIES, RegistrationForm::SUB_CITIES))->native(false),
                TextInput::make('address'),
                TextInput::make('guardian_name'),
                TextInput::make('guardian_phone')->tel(),
                TextInput::make('guardian_relation'),
                Toggle::make('is_minor'),
                Toggle::make('guardian_consent'),
                Toggle::make('photo_consent'),
                Textarea::make('notes')->columnSpanFull(),
            ]),
            Section::make('Answers to this batch\'s questions')->columns(2)->columnSpanFull()
                ->visible(fn ($get) => RegistrationForm::custom(Batch::find($get('batch_id')), false) !== [])
                ->schema(fn ($get) => self::answerFields(Batch::find($get('batch_id')))),
            Section::make('Call log')->columns(3)->columnSpanFull()->schema([
                \Filament\Forms\Components\DateTimePicker::make('called_at')->label('Last called')->native(false),
                TextInput::make('call_note')->label('Call note')->maxLength(500)->columnSpan(2),
            ]),
        ]);
    }

    /** Edit inputs for a batch's custom questions (state: answers.{key} => value). */
    public static function answerFields(?Batch $batch): array
    {
        return collect(RegistrationForm::custom($batch, false))->map(function (array $f) {
            $name = 'answers.'.$f['key'];
            $options = array_combine($f['options'], $f['options']) ?: [];

            return (match ($f['type']) {
                'textarea' => Textarea::make($name)->rows(3),
                'number' => TextInput::make($name)->numeric(),
                'date' => DatePicker::make($name)->native(false),
                'yes_no' => \Filament\Forms\Components\Radio::make($name)->options(['yes' => 'Yes', 'no' => 'No'])->inline(),
                'select' => Select::make($name)->options($options)->native(false),
                'checkboxes' => \Filament\Forms\Components\CheckboxList::make($name)->options($options)->columns(2),
                default => TextInput::make($name)->maxLength(500),
            })->label($f['label'] ?: $f['key']);
        })->all();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(4)
                ->description(fn (Registration $r) => ($s = $r->matchingStudent()) && $r->student_id !== $s->id
                    ? "⚠ Duplicate: a student with this phone already exists ({$s->full_name}). Approving will enroll that existing student instead of creating a new one."
                    : null)
                ->schema([
                    TextEntry::make('full_name')->weight('bold')->size('lg')->columnSpan(2),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('created_at')->label('Submitted')->dateTime('d M Y H:i'),
                    TextEntry::make('batch.label')->label('Batch')->columnSpan(2),
                    TextEntry::make('phone')->formatStateUsing(fn ($state) => PhoneNumber::format($state))->copyable()
                        ->icon('heroicon-o-phone')->color('primary')->url(fn (Registration $r) => 'tel:'.$r->phone),
                    TextEntry::make('alt_phone')->placeholder('—')->formatStateUsing(fn ($state) => PhoneNumber::format($state))
                        ->url(fn (Registration $r) => $r->alt_phone ? 'tel:'.$r->alt_phone : null),
                    TextEntry::make('gender')->badge(),
                    TextEntry::make('date_of_birth')->date('d M Y')->placeholder('—'),
                    IconEntry::make('is_minor')->boolean()->label('Under 18'),
                    TextEntry::make('email')->placeholder('—'),
                    TextEntry::make('school')->placeholder('—'),
                    TextEntry::make('grade_or_year')->placeholder('—'),
                    TextEntry::make('sub_city')->placeholder('—'),
                    TextEntry::make('address')->placeholder('—'),
                ]),
            Section::make('Answers')->columns(2)
                ->visible(fn (Registration $r) => filled($r->answers))
                ->schema(fn (Registration $record) => collect($record->answers ?? [])->map(fn ($item, $key) => TextEntry::make('answer_'.$key)
                    ->label($item['q'] ?? $key)
                    ->state(RegistrationForm::display($item['a'] ?? $item))
                    ->weight('medium'))->values()->all()),
            Section::make('Call log')->columns(3)->schema([
                TextEntry::make('called_at')->label('Last called')->dateTime('d M Y H:i')->placeholder('Not called yet'),
                TextEntry::make('call_note')->label('Note')->placeholder('—')->columnSpan(2),
            ]),
            Section::make('Guardian & consent')->columns(4)
                ->visible(fn (Registration $r) => $r->guardian_name || $r->guardian_phone || $r->guardian_consent || $r->photo_consent)
                ->schema([
                TextEntry::make('guardian_name')->placeholder('—'),
                TextEntry::make('guardian_phone')->placeholder('—')->formatStateUsing(fn ($state) => PhoneNumber::format($state))
                    ->url(fn (Registration $r) => $r->guardian_phone ? 'tel:'.$r->guardian_phone : null),
                TextEntry::make('guardian_relation')->placeholder('—'),
                IconEntry::make('guardian_consent')->boolean(),
                IconEntry::make('photo_consent')->boolean(),
            ]),
            Section::make('Other')->columns(4)->schema([
                IconEntry::make('telegram_chat_id')->label('Connected Telegram')->state(fn (Registration $r) => filled($r->telegram_chat_id))->boolean(),
                TextEntry::make('notes')->columnSpan(3)->placeholder('—'),
            ]),
            Section::make('Review')->columns(3)->visible(fn (Registration $r) => $r->reviewed_at !== null)->schema([
                TextEntry::make('reviewer.name')->label('Reviewed by'),
                TextEntry::make('reviewed_at')->dateTime(),
                TextEntry::make('rejection_reason')->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('batch.program')->addSelect([
                'existing_student_id' => Student::select('id')->whereColumn('students.phone', 'registrations.phone')->whereNull('deleted_at')->limit(1),
            ]))
            // One card per registration (see resources/views/filament/cards/registration.blade.php).
            ->columns([
                \Filament\Tables\Columns\Layout\View::make('filament.cards.registration'),
            ])
            ->contentGrid(['md' => 2, '2xl' => 3])
            ->searchable(['full_name', 'phone'])
            ->searchPlaceholder('Search name or phone')
            ->recordUrl(null)
            ->filters([
                SelectFilter::make('batch_id')->label('Batch')
                    ->options(fn () => \App\Models\Batch::with('program')->latest('start_date')->get()->mapWithKeys(fn ($b) => [$b->id => $b->label]))
                    ->searchable()
                    ->placeholder('All batches'),
            ])
            ->deferFilters(false) // apply instantly, no extra button
            ->headerActions([
                Action::make('export')->label('Download Excel')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->tooltip('Downloads the list as you see it (current tab, batch and search)')
                    ->action(fn ($livewire) => \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Exports\RegistrationsExport($livewire->getFilteredTableQuery()),
                        'registrations-'.now()->format('Ymd-His').'.xlsx'
                    )),
            ])
            ->recordActions([
                self::logCallAction(),
                self::updateStatusAction(),
                EditAction::make()->label('Edit')->iconButton()->tooltip('Edit details & answers'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkStatus')->label('Update status of selected')->icon('heroicon-o-pencil-square')
                        ->schema(self::statusFormSchema())
                        ->action(function (Collection $records, array $data) {
                            $status = RegistrationStatus::from($data['status']);
                            foreach ($records as $r) {
                                if ($r->status !== $status) {
                                    app(RegistrationService::class)->setStatus($r, $status, auth()->user(), $data['note'] ?? null);
                                }
                            }
                            Notification::make()->success()->title(count($records).' registrations → '.$status->getLabel())->send();
                        })->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** The status choices, explained in plain words. */
    public static function statusFormSchema(): array
    {
        return [
            \Filament\Forms\Components\Radio::make('status')->label('New status')->required()
                ->options(collect(RegistrationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()]))
                ->descriptions([
                    RegistrationStatus::Pending->value => 'Just registered — not reviewed yet.',
                    RegistrationStatus::Interview->value => 'Invited to an interview.',
                    RegistrationStatus::Approved->value => 'Passed — they become a student of this batch.',
                    RegistrationStatus::Waitlisted->value => 'Batch is full or later — keep them waiting.',
                    RegistrationStatus::Rejected->value => 'Not accepted this time.',
                ])
                ->live(),
            Textarea::make('note')->rows(2)->maxLength(250)
                ->label(fn ($get) => match ($get('status')) {
                    RegistrationStatus::Interview->value => 'Interview date & place (optional)',
                    RegistrationStatus::Rejected->value => 'Reason (optional)',
                    default => 'Note (optional)',
                })
                ->helperText('Sent to them on Telegram if they are linked.')
                ->visible(fn ($get) => in_array($get('status'), [RegistrationStatus::Interview->value, RegistrationStatus::Rejected->value], true)),
        ];
    }

    /** One button to move a registration along: New → Interview → Accepted / Waiting list / Not accepted. */
    public static function updateStatusAction(): Action
    {
        return Action::make('updateStatus')->label('Update status')->icon('heroicon-o-pencil-square')->color('primary')
            ->button()
            ->visible(fn (Registration $r) => auth()->user()->can('update', $r))
            ->modalHeading(fn (Registration $r) => $r->full_name.' — '.$r->batch?->label)
            ->modalDescription(fn (Registration $r) => ($s = $r->matchingStudent()) && $r->student_id !== $s->id
                ? "⚠ {$s->full_name} is already a student with this phone. Accepting will add that student to this batch."
                : null)
            ->fillForm(fn (Registration $r) => ['status' => $r->status?->value])
            ->schema(self::statusFormSchema())
            ->modalSubmitActionLabel('Save')
            ->action(function (Registration $r, array $data) {
                $status = RegistrationStatus::from($data['status']);
                if ($status !== $r->status) {
                    app(RegistrationService::class)->setStatus($r, $status, auth()->user(), $data['note'] ?? null);
                }
                Notification::make()->success()->title($r->full_name.' → '.$status->getLabel())
                    ->body($status === RegistrationStatus::Approved ? 'They are now a student of this batch.' : null)->send();
            });
    }

    /** Record that we phoned the applicant (time + short note). */
    public static function logCallAction(): Action
    {
        return Action::make('logCall')->label('Call')->icon('heroicon-o-phone')->color('gray')
            ->button()
            ->visible(fn (Registration $r) => auth()->user()->can('update', $r))
            ->modalHeading(fn (Registration $r) => 'Call '.$r->full_name)
            ->modalDescription(fn (Registration $r) => new \Illuminate\Support\HtmlString(
                '<a href="tel:'.e($r->phone).'" style="font-size:1.4rem;font-weight:700;color:#0b3d2a">📞 '.e(PhoneNumber::format($r->phone)).'</a>'
                .($r->alt_phone ? '<br>Other phone: <a href="tel:'.e($r->alt_phone).'">'.e(PhoneNumber::format($r->alt_phone)).'</a>' : '')
                .'<br><small>Tap the number to call, then save what happened.</small>'
            ))
            ->schema([
                \Filament\Forms\Components\Radio::make('outcome')->label('What happened?')->required()->inline()->options([
                    'Talked' => 'Talked', 'No answer' => 'No answer', 'Busy / call back' => 'Call back later', 'Wrong number' => 'Wrong number',
                ]),
                Textarea::make('note')->label('Note (optional)')->rows(2)->maxLength(400),
            ])
            ->modalSubmitActionLabel('Save')
            ->action(function (Registration $r, array $data) {
                $r->forceFill([
                    'called_at' => now(),
                    'call_note' => trim($data['outcome'].(filled($data['note'] ?? null) ? ' — '.$data['note'] : '')),
                ])->save();
                Notification::make()->success()->title('Call saved')->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistrations::route('/'),
            'view' => Pages\ViewRegistration::route('/{record}'),
            'edit' => Pages\EditRegistration::route('/{record}/edit'),
        ];
    }
}
