<?php

namespace App\Http\Controllers;

use App\Models\SengBayarPajak;
use App\Services\ImportDuplicateTracker;
use App\Services\SengBayarPajakImporter;
use App\Services\XlsxStreamToCsvConverter;
use App\Support\ApiCacheManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class SengBayarPajakController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            abort_unless(
                $user && $user->hasAnyRole(['super-admin', 'superadmin', 'admin', 'adminprov'], 'web'),
                403,
                'Akses hanya untuk super admin dan admin prov.'
            );

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $currentYear = (int) date('Y');
            $year = $request->filled('year') ? (int) $request->year : $currentYear;
            $nopol = trim((string) $request->input('nopol', ''));

            $query = SengBayarPajak::query()->where('year', $year);

            if ($nopol !== '') {
                $query->where(function ($q) use ($nopol) {
                    $q->where('nopol', 'like', '%' . $nopol . '%')
                        ->orWhere('nopol_', 'like', '%' . $nopol . '%');
                });
            }

            $query->orderBy('id', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('tgl_bayar_fmt', function ($row) {
                    return $row->tgl_bayar ? $row->tgl_bayar->format('Y-m-d') : '';
                })
                ->addColumn('pkb_provinsi_jalan_fmt', fn ($row) => number_format((int) ($row->pkb_provinsi_jalan ?? 0), 0, ',', '.'))
                ->addColumn('pkb_provinsi_tunggakan_fmt', fn ($row) => number_format((int) ($row->pkb_provinsi_tunggakan ?? 0), 0, ',', '.'))
                ->addColumn('pkb_opsen_jalan_fmt', fn ($row) => number_format((int) ($row->pkb_opsen_jalan ?? 0), 0, ',', '.'))
                ->addColumn('pkb_opsen_tunggakan_fmt', fn ($row) => number_format((int) ($row->pkb_opsen_tunggakan ?? 0), 0, ',', '.'))
                ->make(true);
        }

        $defaultYear = (int) date('Y');
        $years = SengBayarPajak::query()
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (!in_array($defaultYear, $years, true)) {
            array_unshift($years, $defaultYear);
        }

        $years = array_values(array_unique($years));

        return view('backend.bayar-pajak.index', compact('defaultYear', 'years'));
    }

    public function importUpload(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'excel_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:102400',
        ]);

        $file = $request->file('excel_file');
        $realPath = $file->getRealPath();
        if ($realPath === false) {
            return response()->json(['success' => false, 'message' => 'File tidak dapat dibaca.'], 422);
        }

        $year = (int) $request->year;
        $importId = (string) Str::uuid();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
        $importer = new SengBayarPajakImporter();

        Storage::disk('local')->makeDirectory('imports/bayar-pajak');

        $csvRelative = 'imports/bayar-pajak/' . $importId . '.csv';
        $trackerPath = 'imports/bayar-pajak/' . $importId . '.sqlite';
        $needsConvert = in_array($ext, ['xlsx', 'xls'], true);

        try {
            if ($ext === 'xls') {
                return response()->json([
                    'success' => false,
                    'message' => 'Format .xls lama tidak didukung untuk file besar. Simpan sebagai .xlsx atau .csv lalu unggah ulang.',
                ], 422);
            }

            if ($needsConvert) {
                $file->storeAs('imports/bayar-pajak', $importId . '.' . $ext, 'local');
            } else {
                $file->storeAs('imports/bayar-pajak', $importId . '.csv', 'local');
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan file: ' . $e->getMessage(),
            ], 500);
        }

        try {
            ImportDuplicateTracker::create(Storage::disk('local')->path($trackerPath));
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($csvRelative);
            if ($needsConvert) {
                Storage::disk('local')->delete('imports/bayar-pajak/' . $importId . '.' . $ext);
            }

            return response()->json([
                'success' => false,
                'message' => 'Pelacak duplikat tidak dapat dibuat. Pastikan ekstensi PHP PDO SQLite aktif.',
            ], 500);
        }

        $state = [
            'path' => $csvRelative,
            'tracker_path' => $trackerPath,
            'year' => $year,
            'user_id' => Auth::id(),
            'delimiter' => ',',
            'next_row' => 0,
            'seed_after_id' => 0,
            'db_seeded' => false,
            'stats' => SengBayarPajakImporter::emptyStats(),
            'created_at' => Carbon::now()->toIso8601String(),
            'phase' => $needsConvert ? 'extract' : 'import',
            'xlsx_path' => $needsConvert ? ('imports/bayar-pajak/' . $importId . '.' . $ext) : null,
            'work_dir' => $needsConvert ? ('imports/bayar-pajak/' . $importId . '-work') : null,
            'convert' => [
                'sheet_path' => null,
                'shared_strings_path' => null,
                'index_path' => null,
                'byte_offset' => 0,
                'rows_done' => 0,
                'date_col' => '',
            ],
        ];

        if (!$needsConvert) {
            $csvFullPath = Storage::disk('local')->path($csvRelative);
            $headerHandle = fopen($csvFullPath, 'r');
            $headerLine = $headerHandle !== false ? (fgets($headerHandle) ?: '') : '';
            if (is_resource($headerHandle)) {
                fclose($headerHandle);
            }

            if ($headerLine === '') {
                $this->cleanupImportFiles($state);
                Cache::forget('bayar-pajak-import:' . $importId);

                return response()->json(['success' => false, 'message' => 'File kosong / header tidak ditemukan.'], 422);
            }

            $state['delimiter'] = $importer->detectCsvDelimiter($headerLine);
        }

        Cache::put('bayar-pajak-import:' . $importId, $state, now()->addHours(6));

        return response()->json([
            'success' => true,
            'import_id' => $importId,
            'message' => $needsConvert
                ? 'File berhasil diunggah. Menyiapkan konversi Excel...'
                : 'File berhasil diunggah. Memulai proses import...',
        ]);
    }

    public function importChunk(Request $request): JsonResponse
    {
        $request->validate([
            'import_id' => 'required|uuid',
        ]);

        $importer = new SengBayarPajakImporter();
        $importId = (string) $request->import_id;
        $cacheKey = 'bayar-pajak-import:' . $importId;
        $state = Cache::get($cacheKey);

        if (!is_array($state) || !isset($state['path'])) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi import tidak ditemukan atau sudah kedaluwarsa. Silakan unggah ulang.',
            ], 404);
        }

        if ((int) ($state['user_id'] ?? 0) !== (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Sesi import tidak valid.'], 403);
        }

        $trackerPath = (string) ($state['tracker_path'] ?? '');
        if ($trackerPath === '') {
            Cache::forget($cacheKey);

            return response()->json(['success' => false, 'message' => 'Sesi import tidak valid.'], 422);
        }

        $trackerFullPath = Storage::disk('local')->path($trackerPath);
        if (!is_file($trackerFullPath)) {
            $this->cleanupImportFiles($state);
            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,
                'message' => 'Data pelacak duplikat tidak ditemukan. Silakan unggah ulang.',
            ], 404);
        }

        set_time_limit(300);

        $phase = (string) ($state['phase'] ?? 'import');

        if (in_array($phase, ['extract', 'convert'], true)) {
            try {
                return $this->handleConvertPhase($cacheKey, $state, $phase);
            } catch (\Throwable $e) {
                $this->cleanupImportFiles($state);
                Cache::forget($cacheKey);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengonversi Excel: ' . $e->getMessage(),
                ], 500);
            }
        }

        $fullPath = Storage::disk('local')->path($state['path']);
        if (!is_file($fullPath)) {
            $this->cleanupImportFiles($state);
            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,
                'message' => 'File import tidak ditemukan. Silakan unggah ulang.',
            ], 404);
        }

        $now = Carbon::parse($state['created_at'] ?? Carbon::now());
        $tracker = ImportDuplicateTracker::open($trackerFullPath);

        if (!($state['db_seeded'] ?? false)) {
            $seedResult = $importer->seedExistingKeysBatch(
                $tracker,
                (int) $state['year'],
                (int) ($state['seed_after_id'] ?? 0),
            );

            $state['seed_after_id'] = $seedResult['after_id'];
            $state['db_seeded'] = $seedResult['done'];

            if (!$state['db_seeded']) {
                Cache::put($cacheKey, $state, now()->addHours(6));

                return response()->json([
                    'success' => true,
                    'done' => false,
                    'seeding' => true,
                    'stats' => $state['stats'] ?? SengBayarPajakImporter::emptyStats(),
                    'message' => 'Menyiapkan indeks duplikat database...',
                ]);
            }
        }

        $result = $importer->processChunk(
            $fullPath,
            (string) $state['delimiter'],
            (int) $state['year'],
            (int) $state['user_id'],
            $now,
            (int) $state['next_row'],
            $tracker,
            $state['stats'] ?? SengBayarPajakImporter::emptyStats(),
        );

        $state['next_row'] = $result['next_row'];
        $state['stats'] = $result['stats'];

        if ($result['done']) {
            $this->cleanupImportFiles($state);
            Cache::forget($cacheKey);
            ApiCacheManager::forgetByPrefix('admin:bayar-pajak:');

            return response()->json([
                'success' => true,
                'done' => true,
                'stats' => $state['stats'],
                'message' => $importer->buildSummaryMessage($state['stats']),
            ]);
        }

        Cache::put($cacheKey, $state, now()->addHours(6));

        return response()->json([
            'success' => true,
            'done' => false,
            'seeding' => false,
            'stats' => $state['stats'],
            'message' => 'Memproses chunk... masuk: ' . ($state['stats']['inserted'] ?? 0),
        ]);
    }

    private function handleConvertPhase(string $cacheKey, array $state, string $phase): JsonResponse
    {
        $converter = new XlsxStreamToCsvConverter();
        $workDirRelative = (string) ($state['work_dir'] ?? '');
        $xlsxRelative = (string) ($state['xlsx_path'] ?? '');
        $csvRelative = (string) ($state['path'] ?? '');

        if ($workDirRelative === '' || $xlsxRelative === '' || $csvRelative === '') {
            throw new \RuntimeException('State konversi tidak lengkap.');
        }

        $workDir = Storage::disk('local')->path($workDirRelative);
        $xlsxFullPath = Storage::disk('local')->path($xlsxRelative);
        $csvFullPath = Storage::disk('local')->path($csvRelative);

        if ($phase === 'extract') {
            if (!is_file($xlsxFullPath)) {
                throw new \RuntimeException('File Excel sumber tidak ditemukan.');
            }

            $extracted = $converter->extract($xlsxFullPath, $workDir);
            $indexPath = $workDir . '/shared_strings.idx';
            $converter->buildSharedStringIndex($extracted['shared_strings_path'], $indexPath);

            $state['phase'] = 'convert';
            $state['convert'] = [
                'sheet_path' => $extracted['sheet_path'],
                'shared_strings_path' => $extracted['shared_strings_path'],
                'index_path' => $indexPath,
                'byte_offset' => 0,
                'rows_done' => 0,
                'date_col' => '',
            ];

            Cache::put($cacheKey, $state, now()->addHours(6));

            return response()->json([
                'success' => true,
                'done' => false,
                'converting' => true,
                'stats' => $state['stats'] ?? SengBayarPajakImporter::emptyStats(),
                'message' => 'Excel diekstrak. Mulai konversi ke CSV...',
            ]);
        }

        $convert = $state['convert'] ?? [];
        $result = $converter->convertChunk([
            'sheet_path' => (string) ($convert['sheet_path'] ?? ''),
            'shared_strings_path' => (string) ($convert['shared_strings_path'] ?? ''),
            'index_path' => (string) ($convert['index_path'] ?? ''),
            'csv_path' => $csvFullPath,
            'byte_offset' => (int) ($convert['byte_offset'] ?? 0),
            'rows_done' => (int) ($convert['rows_done'] ?? 0),
            'date_col' => (string) ($convert['date_col'] ?? ''),
        ]);

        $state['convert']['byte_offset'] = $result['byte_offset'];
        $state['convert']['rows_done'] = $result['rows_done'];
        $state['convert']['date_col'] = $result['date_col'];

        if (!$result['done']) {
            Cache::put($cacheKey, $state, now()->addHours(6));

            return response()->json([
                'success' => true,
                'done' => false,
                'converting' => true,
                'stats' => $state['stats'] ?? SengBayarPajakImporter::emptyStats(),
                'message' => 'Mengonversi Excel... baris: ' . number_format($result['rows_done'], 0, ',', '.'),
            ]);
        }

        if (!is_file($csvFullPath) || filesize($csvFullPath) === 0) {
            throw new \RuntimeException('File CSV hasil konversi kosong.');
        }

        $headerHandle = fopen($csvFullPath, 'r');
        $headerLine = $headerHandle !== false ? (fgets($headerHandle) ?: '') : '';
        if (is_resource($headerHandle)) {
            fclose($headerHandle);
        }

        if ($headerLine === '') {
            throw new \RuntimeException('Header CSV tidak ditemukan setelah konversi.');
        }

        $importer = new SengBayarPajakImporter();
        $state['delimiter'] = $importer->detectCsvDelimiter($headerLine);
        $state['phase'] = 'import';
        $state['convert'] = null;

        $converter->cleanupWorkDir($workDir);
        if ($xlsxRelative !== '') {
            Storage::disk('local')->delete($xlsxRelative);
        }
        $state['xlsx_path'] = null;
        $state['work_dir'] = null;

        Cache::put($cacheKey, $state, now()->addHours(6));

        return response()->json([
            'success' => true,
            'done' => false,
            'converting' => false,
            'stats' => $state['stats'] ?? SengBayarPajakImporter::emptyStats(),
            'message' => 'Konversi selesai (' . number_format($result['rows_done'], 0, ',', '.') . ' baris). Memulai import...',
        ]);
    }

    private function cleanupImportFiles(array $state): void
    {
        if (!empty($state['path'])) {
            Storage::disk('local')->delete($state['path']);
        }
        if (!empty($state['tracker_path'])) {
            Storage::disk('local')->delete($state['tracker_path']);
        }
        if (!empty($state['xlsx_path'])) {
            Storage::disk('local')->delete($state['xlsx_path']);
        }
        if (!empty($state['work_dir'])) {
            (new XlsxStreamToCsvConverter())->cleanupWorkDir(
                Storage::disk('local')->path((string) $state['work_dir'])
            );
        }
    }
}
