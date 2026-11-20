<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Services\Import\SpreadsheetReader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Reads the uploaded sheet and queues it in chunks (each chunk runs in its own transaction).
 */
class ProcessStudentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CHUNK = 200;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public int $importId) {}

    public function handle(SpreadsheetReader $reader): void
    {
        $import = Import::find($this->importId);
        if (! $import || $import->status !== ImportStatus::Pending) {
            return; // already started — never process twice
        }

        $import->forceFill(['status' => ImportStatus::Processing, 'started_at' => now()])->save();

        try {
            $data = $reader->read(Storage::disk('local')->path($import->file));
        } catch (Throwable $e) {
            $import->forceFill(['status' => ImportStatus::Failed, 'error_message' => $e->getMessage(), 'finished_at' => now()])->save();

            return;
        }

        $import->forceFill(['total_rows' => count($data['rows'])])->save();

        $jobs = collect($data['rows'])
            ->chunk(self::CHUNK)
            ->map(fn ($chunk) => new ImportStudentChunk($import->id, $chunk->values()->all()))
            ->values()
            ->all();
        $jobs[] = new FinalizeImport($import->id, $data['headers']);

        $id = $import->id;
        Bus::chain($jobs)->catch(function (Throwable $e) use ($id) {
            Import::whereKey($id)->update([
                'status' => ImportStatus::Failed->value,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'finished_at' => now(),
            ]);
        })->dispatch();
    }
}
