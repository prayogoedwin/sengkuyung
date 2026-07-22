<?php

namespace App\Services;

use App\Models\SengBayarPajak;
use App\Support\NopolFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SengBayarPajakImporter
{
    public const CHUNK_SIZE = 2500;

    public const BATCH_SIZE = 1000;

    public const SEED_BATCH_SIZE = 50000;

    public static function emptyStats(): array
    {
        return [
            'total_rows' => 0,
            'inserted' => 0,
            'skipped_duplicate_db' => 0,
            'skipped_duplicate_file' => 0,
            'skipped_invalid' => 0,
            'skipped_empty_nopol' => 0,
        ];
    }

    /**
     * Konversi XLSX ke CSV agar proses chunk ringan seperti Data Tertagih.
     */
    public function convertXlsxToCsv(string $xlsxPath, string $csvPath): void
    {
        set_time_limit(600);

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($xlsxPath);
        $sheet = $spreadsheet->getActiveSheet();

        $handle = fopen($csvPath, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Gagal membuat file CSV sementara.');
        }

        try {
            $highestRow = (int) $sheet->getHighestDataRow();
            for ($row = 1; $row <= $highestRow; $row++) {
                $values = [];
                for ($col = 'A'; $col <= 'G'; $col++) {
                    $cell = $sheet->getCell($col . $row);
                    $value = $cell->getValue();

                    if ($col === 'C' && $row > 1 && is_numeric($value)) {
                        try {
                            $value = ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
                        } catch (\Throwable) {
                            // biarkan apa adanya
                        }
                    }

                    $values[] = $value === null ? '' : (string) $value;
                }

                fputcsv($handle, $values);
            }
        } finally {
            fclose($handle);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    public function detectCsvDelimiter(string $line): string
    {
        $semicolonCount = substr_count($line, ';');
        $commaCount = substr_count($line, ',');

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    public function stripUtf8Bom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }

        return $value;
    }

    /**
     * @return array{stats: array, next_row: int, done: bool}
     */
    public function processChunk(
        string $filePath,
        string $delimiter,
        int $year,
        int $userId,
        Carbon $now,
        int $startRow,
        ImportDuplicateTracker $tracker,
        array $stats
    ): array {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('File CSV tidak dapat dibaca.');
        }

        // Skip header
        fgets($handle);

        for ($i = 0; $i < $startRow; $i++) {
            if (fgetcsv($handle, 0, $delimiter) === false) {
                fclose($handle);

                return [
                    'stats' => $stats,
                    'next_row' => $startRow,
                    'done' => true,
                ];
            }
        }

        $processedInChunk = 0;
        $batch = [];

        while ($processedInChunk < self::CHUNK_SIZE && ($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $stats['total_rows']++;
            $processedInChunk++;

            if (count($row) === 1 && isset($row[0]) && str_contains((string) $row[0], $delimiter === ',' ? ';' : ',')) {
                $row = str_getcsv((string) $row[0], $delimiter === ',' ? ';' : ',');
            }

            // Minimal: NO_POLISI + TGL_BAYAR
            if (count($row) < 3) {
                $stats['skipped_invalid']++;
                continue;
            }

            $rawNopol = $this->stripUtf8Bom(trim((string) ($row[0] ?? '')));
            if ($rawNopol === '' || strtoupper($rawNopol) === 'NO_POLISI') {
                if (strtoupper($rawNopol) === 'NO_POLISI') {
                    $stats['total_rows']--;
                    $processedInChunk--;
                } else {
                    $stats['skipped_empty_nopol']++;
                }
                continue;
            }

            $nopolNormalized = NopolFormatter::normalize($rawNopol);
            if ($nopolNormalized === '') {
                $stats['skipped_empty_nopol']++;
                continue;
            }

            $tglBayar = $this->parseDate(trim((string) ($row[2] ?? '')));
            if ($tglBayar === null) {
                $stats['skipped_invalid']++;
                continue;
            }

            $lookupKey = strtoupper($nopolNormalized) . '|' . $tglBayar;
            $existingSource = $tracker->getSource($lookupKey);
            if ($existingSource !== null) {
                if ($existingSource === 'db') {
                    $stats['skipped_duplicate_db']++;
                } else {
                    $stats['skipped_duplicate_file']++;
                }
                continue;
            }

            $tracker->markFile($lookupKey);

            $nopolLamaRaw = trim((string) ($row[1] ?? ''));
            $batch[] = [
                'nopol' => $rawNopol,
                'nopol_' => $nopolNormalized,
                'nopol_lama' => $nopolLamaRaw !== '' ? $nopolLamaRaw : null,
                'tgl_bayar' => $tglBayar,
                'pkb_provinsi_jalan' => $this->parseAmount($row[3] ?? null),
                'pkb_provinsi_tunggakan' => $this->parseAmount($row[4] ?? null),
                'pkb_opsen_jalan' => $this->parseAmount($row[5] ?? null),
                'pkb_opsen_tunggakan' => $this->parseAmount($row[6] ?? null),
                'year' => $year,
                'created_at' => $now,
                'created_by' => $userId,
                'updated_at' => $now,
                'updated_by' => $userId,
            ];

            if (count($batch) >= self::BATCH_SIZE) {
                $this->insertBatch($batch);
                $stats['inserted'] += count($batch);
                $batch = [];
            }
        }

        $done = $processedInChunk < self::CHUNK_SIZE;

        if ($batch !== []) {
            $this->insertBatch($batch);
            $stats['inserted'] += count($batch);
        }

        fclose($handle);

        return [
            'stats' => $stats,
            'next_row' => $startRow + $processedInChunk,
            'done' => $done,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     */
    private function insertBatch(array $batch): void
    {
        DB::transaction(static function () use ($batch) {
            SengBayarPajak::insert($batch);
        });
    }

    /**
     * @return array{done: bool, after_id: int, seeded: int}
     */
    public function seedExistingKeysBatch(
        ImportDuplicateTracker $tracker,
        int $year,
        int $afterId,
        int $limit = self::SEED_BATCH_SIZE,
    ): array {
        $rows = SengBayarPajak::query()
            ->where('year', $year)
            ->where('id', '>', $afterId)
            ->select(['id', 'nopol_', 'tgl_bayar'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return ['done' => true, 'after_id' => $afterId, 'seeded' => 0];
        }

        $keys = [];
        $lastId = $afterId;

        foreach ($rows as $row) {
            $lastId = (int) $row->id;
            $nopol = strtoupper(trim((string) $row->nopol_));
            $tgl = $row->tgl_bayar ? Carbon::parse($row->tgl_bayar)->format('Y-m-d') : '';
            if ($nopol !== '' && $tgl !== '') {
                $keys[] = $nopol . '|' . $tgl;
            }
        }

        $tracker->markDbKeys($keys);

        return [
            'done' => $rows->count() < $limit,
            'after_id' => $lastId,
            'seeded' => count($keys),
        ];
    }

    public function buildSummaryMessage(array $stats): string
    {
        $skippedTotal = $stats['skipped_duplicate_db']
            + $stats['skipped_duplicate_file']
            + $stats['skipped_invalid']
            + $stats['skipped_empty_nopol'];

        return 'Import selesai. Baris diproses: ' . $stats['total_rows']
            . '. Data masuk: ' . $stats['inserted']
            . '. Total dilewati: ' . $skippedTotal
            . ' (duplikat DB: ' . $stats['skipped_duplicate_db']
            . ', duplikat file: ' . $stats['skipped_duplicate_file']
            . ', tidak valid: ' . $stats['skipped_invalid']
            . ', nopol kosong: ' . $stats['skipped_empty_nopol'] . ').';
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseAmount(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $raw = str_replace([',', ' '], '', $raw);
        if (!is_numeric($raw)) {
            return null;
        }

        return (int) round((float) $raw);
    }
}
