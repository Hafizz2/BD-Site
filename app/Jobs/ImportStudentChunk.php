<?php

namespace App\Jobs;

use App\Models\Import;
use App\Services\Import\RowFailedException;
use App\Services\Import\StudentRowImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportStudentChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    /** @param list<array<int|string,mixed>> $rows each row carries its sheet row number in '__row' */
    public function __construct(public int $importId, public array $rows) {}

    public function handle(): void
    {
        $import = Import::with('batch.program')->findOrFail($this->importId);
        $importer = new StudentRowImporter($import);
        $mapping = $import->column_mapping ?? [];

        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $failures = [];

        DB::transaction(function () use ($importer, $mapping, &$counts, &$failures) {
            foreach ($this->rows as $row) {
                $rowNumber = $row['__row'] ?? null;
                $raw = StudentRowImporter::extract($row, $mapping);

                try {
                    // Savepoint per row: one bad row never poisons the chunk.
                    $outcome = DB::transaction(fn () => $importer->import($raw));
                    $counts[$outcome['result']]++;
                    foreach ($outcome['warnings'] as $w) {
                        $failures[] = ['row' => $rowNumber, 'level' => 'warning', 'reason' => $w, 'values' => $this->printable($row)];
                    }
                } catch (RowFailedException $e) {
                    $counts['failed']++;
                    $failures[] = ['row' => $rowNumber, 'level' => 'error', 'reason' => $e->getMessage(), 'values' => $this->printable($row)];
                } catch (Throwable $e) {
                    report($e);
                    $counts['failed']++;
                    $failures[] = ['row' => $rowNumber, 'level' => 'error', 'reason' => 'Unexpected error: '.mb_substr($e->getMessage(), 0, 200), 'values' => $this->printable($row)];
                }
            }
        });

        // Merge results atomically.
        DB::transaction(function () use ($counts, $failures) {
            $import = Import::whereKey($this->importId)->lockForUpdate()->first();
            $import->forceFill([
                'processed_rows' => $import->processed_rows + count($this->rows),
                'created_count' => $import->created_count + $counts['created'],
                'updated_count' => $import->updated_count + $counts['updated'],
                'skipped_count' => $import->skipped_count + $counts['skipped'],
                'failed_count' => $import->failed_count + $counts['failed'],
                'failures' => array_merge($import->failures ?? [], $failures),
            ])->save();
        });
    }

    private function printable(array $row): array
    {
        unset($row['__row']);

        return array_map(fn ($v) => is_scalar($v) || $v === null ? $v : (string) json_encode($v), array_values($row));
    }
}
