<?php

namespace App\Http\Controllers;

use App\Models\SengBayarPajak;
use App\Services\ImportDuplicateTracker;
use App\Services\SengBayarPajakImporter;
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
        $csvFullPath = Storage::disk('local')->path($csvRelative);
        $trackerPath = 'imports/bayar-pajak/' . $importId . '.sqlite';

        try {
            if (in_array($ext, ['xlsx', 'xls'], true)) {
                $xlsxRelative = $file->storeAs('imports/bayar-pajak', $importId . '.' . $ext, 'local');
                $xlsxFullPath = Storage::disk('local')->path($xlsxRelative);
                $importer->convertXlsxToCsv($xlsxFullPath, $csvFullPath);
                Storage::disk('local')->delete($xlsxRelative);
            } else {
                $file->storeAs('imports/bayar-pajak', $importId . '.csv', 'local');
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file Excel: ' . $e->getMessage(),
            ], 500);
        }

        if (!is_file($csvFullPath)) {
            return response()->json(['success' => false, 'message' => 'File CSV hasil konversi tidak ditemukan.'], 500);
        }

        $headerHandle = fopen($csvFullPath, 'r');
        $headerLine = $headerHandle !== false ? (fgets($headerHandle) ?: '') : '';
        if (is_resource($headerHandle)) {
            fclose($headerHandle);
        }

        if ($headerLine === '') {
            Storage::disk('local')->delete($csvRelative);

            return response()->json(['success' => false, 'message' => 'File kosong / header tidak ditemukan.'], 422);
        }

        try {
            ImportDuplicateTracker::create(Storage::disk('local')->path($trackerPath));
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($csvRelative);

            return response()->json([
                'success' => false,
                'message' => 'Pelacak duplikat tidak dapat dibuat. Pastikan ekstensi PHP PDO SQLite aktif.',
            ], 500);
        }

        Cache::put('bayar-pajak-import:' . $importId, [
            'path' => $csvRelative,
            'tracker_path' => $trackerPath,
            'year' => $year,
            'user_id' => Auth::id(),
            'delimiter' => $importer->detectCsvDelimiter($headerLine),
            'next_row' => 0,
            'seed_after_id' => 0,
            'db_seeded' => false,
            'stats' => SengBayarPajakImporter::emptyStats(),
            'created_at' => Carbon::now()->toIso8601String(),
        ], now()->addHours(6));

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

        $fullPath = Storage::disk('local')->path($state['path']);
        if (!is_file($fullPath)) {
            $this->cleanupImportFiles($state);
            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,
                'message' => 'File import tidak ditemukan. Silakan unggah ulang.',
            ], 404);
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

    private function cleanupImportFiles(array $state): void
    {
        if (!empty($state['path'])) {
            Storage::disk('local')->delete($state['path']);
        }
        if (!empty($state['tracker_path'])) {
            Storage::disk('local')->delete($state['tracker_path']);
        }
    }
}
