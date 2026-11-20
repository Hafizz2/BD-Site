<?php

namespace App\Filament\Pages;

use App\Enums\DuplicateRule;
use App\Enums\ImportStatus;
use App\Filament\Resources\ImportResource;
use App\Jobs\ProcessStudentImport;
use App\Models\Batch;
use App\Models\Import;
use App\Models\ImportTemplate;
use App\Services\Import\HeaderMapper;
use App\Services\Import\SpreadsheetReader;
use App\Services\Import\StudentFields;
use App\Services\Import\StudentRowImporter;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Import students from a Google Forms export: upload → match columns → preview → queued import.
 */
class ImportStudents extends Page
{
    protected string $view = 'filament.pages.import-students';

    protected static bool $shouldRegisterNavigation = false; // reached from Imports → "Import students"

    protected static ?string $title = 'Import students';

    public ?array $data = [];

    /** @var array<int,string> column index => header text */
    public array $headers = [];

    /** First rows of the sheet, for the preview. */
    public array $sampleRows = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', Import::class) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(['duplicate_rule' => DuplicateRule::Skip->value]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Wizard::make([
                Step::make('Upload')
                    ->description('Excel or CSV from Google Forms')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Spreadsheet (.xlsx, .xls or .csv, max 10 MB)')
                            ->disk('local')->directory('imports')->visibility('private')
                            ->storeFileNamesIn('original_filename')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel', 'text/csv', 'text/plain', 'application/csv',
                            ])
                            ->maxSize(10240)->required(),
                        Grid::make(3)->schema([
                            Select::make('batch_id')->label('Enroll into batch')
                                ->options(fn () => Batch::with('program')->latest('start_date')->get()->mapWithKeys(fn ($b) => [$b->id => $b->label]))
                                ->placeholder('No batch')->searchable(),
                            Select::make('duplicate_rule')->label('If the phone already exists')
                                ->options([
                                    DuplicateRule::Skip->value => 'Skip the row',
                                    DuplicateRule::UpdateEmpty->value => 'Fill empty fields only',
                                    DuplicateRule::Overwrite->value => 'Overwrite the student',
                                ])->required()->native(false),
                            Select::make('template_id')->label('Saved column template')
                                ->options(fn () => ImportTemplate::where('type', 'students')->pluck('name', 'id'))
                                ->placeholder('Guess automatically'),
                        ]),
                    ])
                    ->afterValidation(fn () => $this->readFile()),

                Step::make('Match columns')
                    ->description('Which sheet column holds each field')
                    ->schema([
                        Grid::make(3)->schema(collect(StudentFields::LABELS)->map(fn ($label, $field) => Select::make("mapping.{$field}")
                            ->label($label)
                            ->options(fn () => $this->headers)
                            ->placeholder('— ignore —')
                            ->required(in_array($field, ['full_name', 'phone'], true))
                        )->values()->all()),
                        TextInput::make('save_template_as')->label('Save this matching as a template (optional)')
                            ->placeholder('e.g. Summer training form')->maxLength(100),
                    ]),

                Step::make('Preview')
                    ->description('Check the first rows')
                    ->schema([
                        View::make('filament.pages.import-preview')->viewData(fn () => ['rows' => $this->previewRows()]),
                    ]),
            ])->submitAction(new HtmlString(Blade::render('<x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">Start import</x-filament::button>'))),
        ]);
    }

    /** Read the headers + first rows of the uploaded file and guess the column matching. */
    public function readFile(): void
    {
        $file = collect($this->data['file'] ?? [])->first();

        try {
            $sheet = app(SpreadsheetReader::class)->read(is_string($file) ? storage_path('app/private/'.$file) : $file->getRealPath(), 20);
        } catch (Throwable $e) {
            Notification::make()->danger()->title('Could not read the file')->body($e->getMessage())->send();

            throw \Illuminate\Validation\ValidationException::withMessages(['data.file' => 'Could not read this file.']);
        }

        $this->headers = collect($sheet['headers'])->map(fn ($h, $i) => $h !== '' ? $h : 'Column '.($i + 1))->all();
        $this->sampleRows = $sheet['rows'];

        $mapping = app(HeaderMapper::class)->guess($sheet['headers']);

        // A saved template matches by header text, so it survives re-ordered columns.
        if ($template = ImportTemplate::find($this->data['template_id'] ?? null)) {
            foreach ($template->column_mapping as $field => $header) {
                $index = array_search($header, $this->headers, true);
                $mapping[$field] = $index === false ? null : $index;
            }
        }

        $this->data['mapping'] = $mapping;
    }

    /** @return list<array{row:int,values:array,errors:list<string>,warnings:list<string>}> */
    public function previewRows(): array
    {
        $import = new Import(['batch_id' => $this->data['batch_id'] ?? null]);
        $importer = new StudentRowImporter($import);
        $mapping = $this->data['mapping'] ?? [];

        return collect($this->sampleRows)->map(function ($row) use ($importer, $mapping) {
            [$attrs, $errors, $warnings] = $importer->normalize(StudentRowImporter::extract($row, $mapping));

            return ['row' => $row['__row'], 'values' => $attrs, 'errors' => $errors, 'warnings' => $warnings];
        })->all();
    }

    public function import(): void
    {
        $state = $this->form->getState();
        $mapping = collect($state['mapping'] ?? [])->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v) => (int) $v)->all();

        if (filled($state['save_template_as'] ?? null)) {
            ImportTemplate::updateOrCreate(
                ['name' => $state['save_template_as'], 'type' => 'students'],
                ['column_mapping' => collect($mapping)->map(fn ($i) => $this->headers[$i] ?? null)->filter()->all(), 'created_by' => auth()->id()],
            );
        }

        $import = Import::create([
            'type' => 'students',
            'file' => $state['file'],
            'original_filename' => $state['original_filename'] ?? basename($state['file']),
            'batch_id' => $state['batch_id'] ?? null,
            'column_mapping' => $mapping,
            'duplicate_rule' => $state['duplicate_rule'],
            'status' => ImportStatus::Pending,
            'imported_by' => auth()->id(),
        ]);

        ProcessStudentImport::dispatch($import->id);

        Notification::make()->success()->title('Import started')
            ->body('It runs in the background. Refresh this page to see the results.')->send();

        $this->redirect(ImportResource::getUrl('view', ['record' => $import]));
    }
}
