<?php

namespace App\Filament\Resources;

use App\Enums\BatchSeason;
use App\Enums\BatchStatus;
use App\Filament\Resources\BatchResource\Pages;
use App\Filament\Resources\BatchResource\RelationManagers;
use App\Models\Batch;
use App\Support\RegistrationForm;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Programs';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function formSchema(bool $withProgram = true): array
    {
        return [
            Grid::make(['default' => 1, 'md' => 3])->schema(array_values(array_filter([
                $withProgram ? Select::make('program_id')->relationship('program', 'name')->required()->searchable()->preload() : null,
                TextInput::make('name')->required()->maxLength(100)->placeholder('Batch 5'),
                Select::make('season')->options(BatchSeason::class)->default(BatchSeason::Regular)->required()->native(false),
                Select::make('status')->options(BatchStatus::class)->default(BatchStatus::Planned)->required()->native(false),
                DatePicker::make('start_date')->native(false),
                DatePicker::make('end_date')->native(false)->afterOrEqual('start_date'),
                TextInput::make('capacity')->numeric()->minValue(1)->helperText('Leave empty for unlimited'),
                TextInput::make('location')->maxLength(150),
                Toggle::make('registration_open')->label('Registration link open')->helperText('People can register only through this batch\'s link, and only while this is on.')->inline(false),
            ])))->columnSpanFull(),
            Grid::make(['default' => 1, 'md' => 2])->schema([
                TextInput::make('schedule_note')->label('Schedule (e.g. "Saturdays 2–5 PM")')->maxLength(150),
                TextInput::make('schedule_note_am')->label('Schedule · አማርኛ')->maxLength(150),
            ])->columnSpanFull(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                \Filament\Schemas\Components\Tabs\Tab::make('Batch details')->key('details')->icon('heroicon-o-rectangle-stack')
                    ->schema(self::formSchema()),
                \Filament\Schemas\Components\Tabs\Tab::make('Registration form')->key('form')->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Section::make('Form header')
                            ->description('What people see at the top of the form — like Google Forms.')
                            ->schema(self::formHeaderSchema()),
                        Section::make('Questions')
                            ->description('Edit any question, hide what you don\'t need, move them up/down, or add your own. Name and phone are always asked.')
                            ->headerActions([self::previewFormAction()])
                            ->schema([self::formFieldsRepeater()]),
                    ]),
            ]),
        ]);
    }

    /** Cover image, title, opening note and thank-you message (stored in batches.form_settings). */
    public static function formHeaderSchema(): array
    {
        return [
            \Filament\Forms\Components\FileUpload::make('form_settings.cover')->label('Cover image (optional)')
                ->image()->imageEditor()->imageEditorAspectRatios(['16:5', '16:9'])
                ->disk('public')->directory('form-covers')->visibility('public')->maxSize(4096)
                ->helperText('A wide picture shown at the top of the form. Best size: 1600 × 500.'),
            Grid::make(['default' => 1, 'md' => 2])->schema([
                TextInput::make('form_settings.title')->label('Form title')->maxLength(150)
                    ->placeholder(fn ($get) => 'Leave empty to use "Program — Batch name"'),
                TextInput::make('form_settings.title_am')->label('Form title · አማርኛ')->maxLength(150),
                \Filament\Forms\Components\Textarea::make('form_settings.description')->label('Opening note')->rows(4)->maxLength(3000)
                    ->placeholder('Welcome! Please fill in this form to register for …'),
                \Filament\Forms\Components\Textarea::make('form_settings.description_am')->label('Opening note · አማርኛ')->rows(4)->maxLength(3000),
                \Filament\Forms\Components\Textarea::make('form_settings.thanks')->label('Thank-you message (after submitting)')->rows(2)->maxLength(1000)
                    ->placeholder('Leave empty to use the default message from Settings → Registration'),
                \Filament\Forms\Components\Textarea::make('form_settings.thanks_am')->label('Thank-you message · አማርኛ')->rows(2)->maxLength(1000),
            ]),
        ];
    }

    /** Open the public form in a new tab (works even while registration is closed). */
    public static function previewFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('previewForm')->label('Preview form')->icon('heroicon-o-eye')->color('gray')
            ->visible(fn (?Batch $record) => $record?->exists)
            ->url(fn (?Batch $record) => $record ? route('register.batch', ['batch' => $record, 'preview' => 1]) : null)
            ->openUrlInNewTab()
            ->tooltip('Save first to see your latest changes');
    }

    /** Per-batch registration form editor (see App\Support\RegistrationForm). */
    public static function formFieldsRepeater(): \Filament\Forms\Components\Repeater
    {
        $isLocked = fn ($get) => in_array($get('key'), RegistrationForm::LOCKED, true);
        $isBuiltin = fn ($get) => array_key_exists((string) $get('key'), RegistrationForm::BUILTIN);

        return \Filament\Forms\Components\Repeater::make('form_fields')
            ->hiddenLabel()
            ->default(RegistrationForm::defaults())
            ->afterStateHydrated(function (\Filament\Forms\Components\Repeater $component, $state) {
                // Always list every standard field (missing ones are added as hidden).
                $component->state(RegistrationForm::complete(blank($state) ? null : array_values((array) $state)));
            })
            ->schema([
                \Filament\Forms\Components\Hidden::make('key')->default(fn () => RegistrationForm::newQuestion()['key']),
                Grid::make(['default' => 1, 'md' => 4])->schema([
                    Select::make('type')->label('Type')->options(RegistrationForm::CUSTOM_TYPES)->default('text')
                        ->required()->native(false)->live()
                        ->visible(fn ($get) => ! $isBuiltin($get))->columnSpanFull(),
                    TextInput::make('label')->label(fn ($get) => $get('type') === 'section' ? 'Title' : 'Question')->required()->maxLength(200)->columnSpan(2),
                    TextInput::make('label_am')->label('አማርኛ')->maxLength(200)->columnSpan(2),
                    \Filament\Forms\Components\TagsInput::make('options')->label('Choices (press Enter after each)')
                        ->visible(fn ($get) => ! $isBuiltin($get) && in_array($get('type'), ['select', 'checkboxes'], true))
                        ->required(fn ($get) => in_array($get('type'), ['select', 'checkboxes'], true))
                        ->columnSpan(3),
                    \Filament\Forms\Components\Textarea::make('help')->rows(2)->maxLength(1000)->columnSpan(2)
                        ->label(fn ($get) => $get('type') === 'section' ? 'Description' : 'Hint under the question (optional)'),
                    Toggle::make('enabled')->label('Show on form')->default(true)->inline(false)
                        ->disabled($isLocked)->dehydrated(),
                    Toggle::make('required')->label('Required')->inline(false)
                        ->disabled($isLocked)->dehydrated()
                        ->visible(fn ($get) => $get('type') !== 'section')
                        ->helperText(fn ($get) => $isLocked($get) ? 'Always required' : null),
                ]),
            ])
            ->itemLabel(function (array $state) {
                $builtin = array_key_exists($state['key'] ?? '', RegistrationForm::BUILTIN);
                $label = ($state['label'] ?? null) ?: 'New question';

                return $label
                    .($builtin ? '' : ' · '.(RegistrationForm::CUSTOM_TYPES[$state['type'] ?? 'text'] ?? 'question'))
                    .(($state['enabled'] ?? true) ? '' : ' — hidden')
                    .(($state['required'] ?? false) ? ' *' : '');
            })
            ->addActionLabel('Add a question')
            ->reorderableWithButtons()
            ->collapsible()
            ->collapsed()
            ->deletable(fn () => true)
            ->hintActions([
                \Filament\Actions\Action::make('copyForm')->label('Copy form from another batch')->icon('heroicon-o-document-duplicate')
                    ->schema([
                        Select::make('from')->label('Batch')->required()->searchable()
                            ->options(fn () => Batch::with('program')->whereNotNull('form_fields')->latest('start_date')->get()->mapWithKeys(fn ($b) => [$b->id => $b->label])),
                    ])
                    ->action(fn (array $data, \Filament\Forms\Components\Repeater $component) => $component->state(RegistrationForm::all(Batch::find($data['from'])))),
                \Filament\Actions\Action::make('resetForm')->label('Reset to default')->icon('heroicon-o-arrow-path')->color('gray')
                    ->requiresConfirmation()->modalDescription('Removes your custom questions and restores the standard fields.')
                    ->action(fn (\Filament\Forms\Components\Repeater $component) => $component->state(RegistrationForm::defaults())),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->weight('semibold')->description(fn (Batch $r) => $r->program?->name),
                TextColumn::make('season')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('start_date')->date('d M Y')->sortable(),
                TextColumn::make('enrollments_count')->counts(['enrollments' => fn ($query) => $query->whereIn('status', ['approved', 'completed'])])->label('Students')
                    ->formatStateUsing(fn ($state, Batch $r) => $r->capacity ? "{$state} / {$r->capacity}" : $state),
                TextColumn::make('registrations_count')->counts('registrations')->label('Registrations')->badge()->color('warning')
                    ->url(fn (Batch $r) => BatchResource::getUrl('view', ['record' => $r]))
                    ->tooltip('Open the batch to see and update its registrations'),
                ToggleColumn::make('registration_open')->label('Reg. open'),
            ])
            ->filters([
                SelectFilter::make('program_id')->relationship('program', 'name')->label('Program'),
                SelectFilter::make('status')->options(BatchStatus::class),
                SelectFilter::make('season')->options(BatchSeason::class),
            ])
            ->recordActions([self::registrationLinkAction(), ViewAction::make(), EditAction::make()]);
    }

    /** The batch's own registration link, to share on Telegram / the website or send to people individually. */
    public static function registrationLinkAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('registrationLink')
            ->label('Registration link')
            ->icon('heroicon-o-link')
            ->color('gold')
            ->modalHeading(fn (Batch $record) => 'Registration link — '.$record->label)
            ->modalDescription(fn (Batch $record) => $record->registration_open
                ? 'Share this link with the people you want to register. It works while "Registration link open" is on.'
                : '⚠ Registration is closed for this batch. Turn on "Registration link open" before sharing the link.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(function (Batch $record) {
                $link = e(route('register.batch', $record));

                return new \Illuminate\Support\HtmlString('<div style="display:flex;gap:.5rem;align-items:center"><input readonly value="'.$link.'" onclick="this.select()" style="flex:1;padding:.6rem .8rem;border:1px solid #d6d3d1;border-radius:.6rem;font-family:monospace;font-size:.85rem"><button type="button" onclick="navigator.clipboard.writeText(\''.$link.'\');this.innerText=\'Copied ✓\'" style="padding:.6rem 1rem;border-radius:.6rem;background:#0b3d2a;color:#fff;font-weight:600">Copy</button></div>');
            });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RegistrationsRelationManager::class,
            RelationManagers\EnrollmentsRelationManager::class,
            RelationManagers\FeePlansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatches::route('/'),
            'create' => Pages\CreateBatch::route('/create'),
            'view' => Pages\ViewBatch::route('/{record}'),
            'edit' => Pages\EditBatch::route('/{record}/edit'),
        ];
    }
}
