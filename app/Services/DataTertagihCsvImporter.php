<?php

namespace App\Services;

use App\Support\NopolFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DataTertagihCsvImporter
{
    public const CHUNK_SIZE = 2500;

    public const BATCH_SIZE = 1000;

    public const SEED_BATCH_SIZE = 50000;

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
    ) {
    }

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

    public function normalizeNoPolisi(string $rawValue): string
    {
        return NopolFormatter::normalize($rawValue);
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
        $hasNopolKey = \Illuminate\Support\Facades\Schema::hasColumn(
            (new $this->modelClass)->getTable(),
            'nopol_key'
        );

        while ($processedInChunk < self::CHUNK_SIZE && ($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $stats['total_rows']++;
            $processedInChunk++;

            if (count($row) === 1 && isset($row[0]) && str_contains((string) $row[0], $delimiter === ',' ? ';' : ',')) {
                $row = str_getcsv((string) $row[0], $delimiter === ',' ? ';' : ',');
            }

            if (count($row) < 7) {
                $stats['skipped_invalid']++;
                continue;
            }

            $rawNoPolisi = $this->stripUtf8Bom(trim((string) ($row[0] ?? '')));
            if ($rawNoPolisi === '') {
                $stats['skipped_empty_nopol']++;
                continue;
            }

            $formattedNoPolisi = $this->normalizeNoPolisi($rawNoPolisi);
            if ($formattedNoPolisi === '') {
                $stats['skipped_empty_nopol']++;
                continue;
            }

            $lookupKey = strtoupper($formattedNoPolisi);

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
            $rowData = [
                'no_polisi' => $formattedNoPolisi,
                'id_lokasi_samsat' => trim((string) ($row[1] ?? '')),
                'lokasi_layanan' => trim((string) ($row[2] ?? '')),
                'id_kecamatan' => trim((string) ($row[3] ?? '')),
                'nm_kecamatan' => trim((string) ($row[4] ?? '')),
                'id_kelurahan' => trim((string) ($row[5] ?? '')),
                'nm_kelurahan' => trim((string) ($row[6] ?? '')),
                'alamat' => trim((string) ($row[7] ?? '')),
                'nama_pemilik' => trim((string) ($row[8] ?? '')),
                'jenis_roda' => trim((string) ($row[9] ?? '')),
                'is_terdata' => 0,
                'year' => $year,
                'created_at' => $now,
                'created_by' => $userId,
                'updated_at' => $now,
                'updated_by' => $userId,
            ];
            if ($hasNopolKey) {
                $rowData['nopol_key'] = NopolFormatter::matchKey($formattedNoPolisi);
            }
            $batch[] = $rowData;

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
        $modelClass = $this->modelClass;

        DB::transaction(static function () use ($batch, $modelClass) {
            $modelClass::insert($batch);
        });
    }

    /**
     * @return array{done: bool, after_id: int, seeded: int}
     */
    public function seedExistingNoPolisiKeysBatch(
        ImportDuplicateTracker $tracker,
        int $year,
        int $afterId,
        int $limit = self::SEED_BATCH_SIZE,
    ): array {
        $rows = $this->modelClass::query()
            ->where('year', $year)
            ->where('id', '>', $afterId)
            ->select(['id', 'no_polisi'])
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
            $key = strtoupper(trim((string) $row->no_polisi));
            if ($key !== '') {
                $keys[] = $key;
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

        return 'Import CSV selesai. Baris data diproses: ' . $stats['total_rows']
            . '. Data masuk: ' . $stats['inserted']
            . '. Total dilewati: ' . $skippedTotal
            . ' (duplikat di database: ' . $stats['skipped_duplicate_db']
            . ', duplikat di file CSV: ' . $stats['skipped_duplicate_file']
            . ', baris tidak valid/kolom kurang: ' . $stats['skipped_invalid']
            . ', nopol kosong: ' . $stats['skipped_empty_nopol'] . ').';
    }
}
