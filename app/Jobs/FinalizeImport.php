<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Writes the downloadable error report and marks the import complete. */
class FinalizeImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $importId, public array $headers) {}

    public function handle(): void
    {
        $import = Import::findOrFail($this->importId);
        $failures = $import->failures ?? [];
        $path = null;

        if ($failures) {
            $path = "imports/reports/{$import->ulid}-errors.xlsx";
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Problems');
            $sheet->fromArray(array_merge(['Sheet row', 'Level', 'Reason'], $this->headers), null, 'A1');
            $r = 2;
            usort($failures, fn ($a, $b) => [$a['row'] ?? 0, $a['level']] <=> [$b['row'] ?? 0, $b['level']]);
            foreach ($failures as $f) {
                $sheet->fromArray(array_merge([$f['row'] ?? '', $f['level'] ?? 'error', $f['reason'] ?? ''], $f['values'] ?? []), null, 'A'.$r++, true);
            }
            $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);
            $sheet->freezePane('A2');
            foreach (['A', 'B', 'C'] as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            Storage::disk('local')->makeDirectory('imports/reports');
            (new Xlsx($book))->save(Storage::disk('local')->path($path));
        }

        $import->forceFill([
            'status' => ImportStatus::Completed,
            'error_report_file' => $path,
            'finished_at' => now(),
        ])->save();
    }
}
