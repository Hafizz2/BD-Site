<?php

use App\Enums\DuplicateRule;
use App\Enums\ImportStatus;
use App\Jobs\ProcessStudentImport;
use App\Models\Batch;
use App\Models\Import;
use App\Models\Student;
use App\Services\Import\HeaderMapper;
use App\Services\Import\SpreadsheetReader;
use Illuminate\Support\Facades\Storage;

it('imports a messy Google Forms export end to end', function () {
    Storage::fake('local');
    Storage::disk('local')->put('imports/sample.csv', implode("\n", [
        'Timestamp,ሙሉ ስም / Full name,ስልክ ቁጥር / Phone,ጾታ,Date of birth',
        '11/15/2026 14:32:10,  abdulhafiz   JEMAL ,0911 22 33 44,ወንድ,14/05/2003',
        '11/15/2026 15:00:00,Hanan Kemal,+251-922-334455,ሴት,2004-01-02',
        '11/15/2026 15:10:00,Duplicate Person,911223344,ወንድ,',   // same phone as row 1
        '11/15/2026 15:20:00,Bad Phone,12345,ሴት,',
        '11/15/2026 15:30:00,,0933445566,ሴት,',                    // missing name
    ]));

    $path = Storage::disk('local')->path('imports/sample.csv');
    $sheet = app(SpreadsheetReader::class)->read($path);
    $mapping = array_filter(app(HeaderMapper::class)->guess($sheet['headers']), fn ($v) => $v !== null);

    expect($mapping)->toMatchArray(['full_name' => 1, 'phone' => 2, 'gender' => 3, 'date_of_birth' => 4, 'registered_at' => 0]);

    $batch = Batch::factory()->create();
    $import = Import::create([
        'file' => 'imports/sample.csv', 'original_filename' => 'sample.csv', 'batch_id' => $batch->id,
        'column_mapping' => $mapping, 'duplicate_rule' => DuplicateRule::Skip, 'status' => ImportStatus::Pending,
    ]);

    ProcessStudentImport::dispatch($import->id); // sync queue in tests

    $import->refresh();
    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->created_count)->toBe(2)
        ->and($import->skipped_count)->toBe(1)
        ->and($import->failed_count)->toBe(2)
        ->and($import->error_report_file)->not->toBeNull()
        ->and(Student::where('phone', '+251911223344')->value('full_name'))->toBe('Abdulhafiz Jemal')
        ->and($batch->enrollments()->count())->toBe(2);

    Storage::disk('local')->assertExists($import->error_report_file);
});
