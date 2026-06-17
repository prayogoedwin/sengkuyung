<?php

namespace App\Http\Controllers\Concerns;

use App\Services\DataTertagihCsvImporter;
use App\Services\ImportDuplicateTracker;
use App\Support\ApiCacheManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesChunkedTertagihImport
{
    abstract protected function importStorageDir(): string;

    abstract protected function importCachePrefix(): string;

    abstract protected function importApiCachePrefix(): string;

    abstract protected function importCsvModelClass(): string;

    protected function makeCsvImporter(): DataTertagihCsvImporter
    {
        return new DataTertagihCsvImporter($this->importCsvModelClass());
    }

    protected function importCacheKey(string $importId): string
    {
        return $this->importCachePrefix() . $importId;
    }

    public function importUpload(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'csv_file' => 'required|file|mimes:csv,txt|max:51200',
        ]);

        $importer = $this->makeCsvImporter();

        $file = $request->file('csv_file');
        $realPath = $file->getRealPath();
        if ($realPath === false) {
            return response()->json(['success' => false, 'message' => 'File CSV tidak dapat dibaca.'], 422);
        }

        $headerHandle = fopen($realPath, 'r');
        $headerLine = $headerHandle !== false ? (fgets($headerHandle) ?: '') : '';
        if (is_resource($headerHandle)) {
            fclose($headerHandle);
        }

        if ($headerLine === '') {
            return response()->json(['success' => false, 'message' => 'File CSV kosong.'], 422);
        }

        $year = (int) $request->year;
        $importId = (string) Str::uuid();
        $storedPath = $file->storeAs($this->importStorageDir(), $importId . '.csv', 'local');
        $trackerPath = $this->importStorageDir() . '/' . $importId . '.sqlite';
        $tracker = ImportDuplicateTracker::create(Storage::disk('local')->path($trackerPath));
        $importer->seedExistingNoPolisiKeys($tracker, $year);

        Cache::put($this->importCacheKey($importId), [
            'path' => $storedPath,
            'tracker_path' => $trackerPath,
            'year' => $year,
            'user_id' => Auth::id(),
            'delimiter' => $importer->detectCsvDelimiter($headerLine),
            'next_row' => 0,
            'stats' => DataTertagihCsvImporter::emptyStats(),
            'created_at' => Carbon::now()->toIso8601String(),
        ], now()->addHours(3));

        return response()->json([
            'success' => true,
            'import_id' => $importId,
            'message' => 'File berhasil diunggah. Memulai proses import...',
        ]);
    }

    public function importChunk(Request $request): JsonResponse
    {
        $request->validate([
            'import_id' => 'required|uuid',
        ]);

        $importer = $this->makeCsvImporter();
        $importId = (string) $request->import_id;
        $cacheKey = $this->importCacheKey($importId);
        $state = Cache::get($cacheKey);

        if (!is_array($state) || !isset($state['path'])) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi import tidak ditemukan atau sudah kedaluwarsa. Silakan unggah ulang file CSV.',
            ], 404);
        }

        if ((int) ($state['user_id'] ?? 0) !== (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Sesi import tidak valid.'], 403);
        }

        $fullPath = Storage::disk('local')->path($state['path']);
        if (!is_file($fullPath)) {
            $this->cleanupImportFiles($state);
            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,
                'message' => 'File import tidak ditemukan. Silakan unggah ulang file CSV.',
            ], 404);
        }

        $trackerPath = (string) ($state['tracker_path'] ?? '');
        if ($trackerPath === '') {
            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,
                'message' => 'Sesi import tidak valid. Silakan unggah ulang file CSV.',
            ], 422);
        }

        $trackerFullPath = Storage::disk('local')->path($trackerPath);
        if (!is_file($trackerFullPath)) {
            $this->cleanupImportFiles($state);
            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,
                'message' => 'Data pelacak duplikat tidak ditemukan. Silakan unggah ulang file CSV.',
            ], 404);
        }

        set_time_limit(120);

        $now = Carbon::parse($state['created_at'] ?? Carbon::now());
        $tracker = ImportDuplicateTracker::open($trackerFullPath);

        $result = $importer->processChunk(
            $fullPath,
            (string) $state['delimiter'],
            (int) $state['year'],
            (int) $state['user_id'],
            $now,
            (int) $state['next_row'],
            $tracker,
            $state['stats'] ?? DataTertagihCsvImporter::emptyStats(),
        );

        $state['next_row'] = $result['next_row'];
        $state['stats'] = $result['stats'];

        if ($result['done']) {
            $this->cleanupImportFiles($state);
            Cache::forget($cacheKey);
            ApiCacheManager::forgetByPrefix($this->importApiCachePrefix());

            return response()->json([
                'success' => true,
                'done' => true,
                'stats' => $result['stats'],
                'message' => $importer->buildSummaryMessage($result['stats']),
            ]);
        }

        Cache::put($cacheKey, $state, now()->addHours(3));

        return response()->json([
            'success' => true,
            'done' => false,
            'stats' => $result['stats'],
            'progress' => [
                'processed_rows' => $result['stats']['total_rows'],
                'inserted' => $result['stats']['inserted'],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function cleanupImportFiles(array $state): void
    {
        if (!empty($state['path'])) {
            Storage::disk('local')->delete((string) $state['path']);
        }

        if (!empty($state['tracker_path'])) {
            Storage::disk('local')->delete((string) $state['tracker_path']);
        }
    }
}
