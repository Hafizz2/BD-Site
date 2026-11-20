<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use RuntimeException;

/**
 * Reads .xlsx / .xls / .csv files into a header row + data rows (raw values, so Excel dates stay serials).
 */
class SpreadsheetReader
{
    /**
     * @return array{headers: array<int,string>, rows: list<array<int,mixed>>, header_row: int}
     */
    public function read(string $path, ?int $limit = null): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Import file not found.');
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $reader = $ext === 'csv' ? new Csv : IOFactory::createReaderForFile($path);
        if ($reader instanceof Csv) {
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter($this->sniffDelimiter($path));
        }
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $sheet = $reader->load($path)->getSheet(0);
        $data = $sheet->toArray(null, true, false, false);

        // Header = first row with at least 2 non-empty cells.
        $headerIndex = 0;
        foreach ($data as $i => $row) {
            if (count(array_filter($row, fn ($c) => trim((string) $c) !== '')) >= 2) {
                $headerIndex = $i;
                break;
            }
        }

        $headers = array_map(fn ($h) => trim(preg_replace('/^\x{FEFF}/u', '', (string) $h) ?? ''), $data[$headerIndex] ?? []);
        // Drop trailing empty header columns.
        while ($headers && end($headers) === '') {
            array_pop($headers);
        }

        $rows = [];
        foreach (array_slice($data, $headerIndex + 1, null, true) as $i => $row) {
            $row = array_slice($row, 0, count($headers));
            if (count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            $rows[] = ['__row' => $i + 1] + $row;
            if ($limit && count($rows) >= $limit) {
                break;
            }
        }

        return ['headers' => $headers, 'rows' => $rows, 'header_row' => $headerIndex + 1];
    }

    private function sniffDelimiter(string $path): string
    {
        $line = (string) fgets(fopen($path, 'r'));
        $counts = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($counts);

        return (string) array_key_first($counts);
    }
}
