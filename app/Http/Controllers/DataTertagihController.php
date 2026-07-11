<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesChunkedTertagihImport;
use App\Models\DataTertagih;
use App\Services\ForceDeletePendataanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\ApiCacheManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Yajra\DataTables\Facades\DataTables;

class DataTertagihController extends Controller
{
    use HandlesChunkedTertagihImport;

    private const TEMPLATE_HEADERS = [
        'no_polisi',
        'id_lokasi_samsat',
        'lokasi_layanan',
        'id_kecamatan',
        'nm_kecamatan',
        'id_kelurahan',
        'nm_kelurahan',
        'alamat',
        'nama_pemilik',
        'jenis_roda',
    ];

    private const TEMPLATE_EXAMPLE_ROWS = [
        ['H-1048-AA', '1', 'SEMARANG I', '103', 'GENUK', '103005', 'BANJARDOWO', 'JL. BANJARDOWO RAYA NO. 12', 'BUDI SANTOSO', '4'],
        ['H-8042-UA', '1', 'SEMARANG I', '101', 'SEMARANG TENGAH', '101012', 'KARANG KIDUL', 'JL. MENTRI SUPENO GG. 3', 'SITI AMINAH', '2'],
        ['H-7054-BA', '1', 'SEMARANG I', '104', 'SEMARANG TIMUR', '104008', 'REJOSARI', 'JL. REJOSARI TENGAH NO. 8', 'AGUS WIBOWO', '4'],
        ['H-2513-WP', '1', 'SEMARANG I', '104', 'SEMARANG TIMUR', '104006', 'BUGANGAN', 'JL. BUGANGAN BARU RT 02 RW 01', 'DEWI LESTARI', '2'],
        ['H-1071-SF', '1', 'SEMARANG I', '102', 'SEMARANG UTARA', '102008', 'TANJUNGMAS', 'JL. TAWANG STASIUN SELATAN', 'RUDI HARTONO', '4'],
        ['H-3322-PH', '1', 'SEMARANG I', '102', 'SEMARANG UTARA', '102004', 'PURWOSARI', 'JL. PURWOSARI RAYA NO. 4', 'ANI PRATIWI', '2'],
        ['H-8455-BL', '1', 'SEMARANG I', '106', 'BANYUMANIK', '106004', 'TLGOSARI', 'JL. TELAGASARI UTAMA BLOK C2', 'JOKO SUSILO', '4'],
        ['H-9012-KQ', '1', 'SEMARANG I', '105', 'GAJAHMUNGKUR', '105002', 'PETOMPON', 'JL. PETOMPON DALAM NO. 15', 'RATNA SARI', '2'],
        ['H-3345-VX', '1', 'SEMARANG I', '107', 'CANDISARI', '107006', 'KARANGANYAR GUNUNG', 'JL. KARANGANYAR GUNUNG TIMUR', 'BAMBANG SETIAWAN', '4'],
        ['H-6678-RN', '1', 'SEMARANG I', '108', 'MIJEN', '108003', 'JATIBARANG', 'JL. JATIBARANG RAYA KM. 3', 'LINA MARLINA', '2'],
    ];

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $currentYear = (int) date('Y');
            $year = $request->filled('year') ? (int) $request->year : $currentYear;
            $isTerdata = $request->filled('is_terdata') ? (string) $request->is_terdata : 'all';
            $noPolisi = trim((string) $request->input('no_polisi', ''));
            $isSuperAdmin = $this->isSuperAdmin();
            $queryParams = $request->query();
            ksort($queryParams);
            $cacheHash = md5(json_encode($queryParams));
            $cacheKey = "admin:data-tertagih:index:{$year}:{$isTerdata}:" . md5($noPolisi) . ':sa-' . ($isSuperAdmin ? '1' : '0') . ":{$cacheHash}";

            $payload = ApiCacheManager::remember($cacheKey, ApiCacheManager::dataTtl(), static function () use ($year, $request, $isSuperAdmin) {
                $query = DataTertagih::query()->where('year', $year);

                if ($request->filled('is_terdata')) {
                    $query->where('is_terdata', (int) $request->is_terdata);
                }

                if ($request->filled('no_polisi')) {
                    $query->where('no_polisi', 'like', '%' . $request->no_polisi . '%');
                }

                $query->orderBy('id', 'desc');

                return DataTables::of($query)
                    ->addIndexColumn()
                    ->addColumn('alamat', function ($row) {
                        return (string) ($row->alamat ?? '');
                    })
                    ->addColumn('nama_pemilik', function ($row) {
                        return (string) ($row->nama_pemilik ?? '');
                    })
                    ->addColumn('jenis_roda', function ($row) {
                        return (string) ($row->jenis_roda ?? '');
                    })
                    ->addColumn('status_terdata', function ($row) {
                        return (int) $row->is_terdata === 1 ? 'Terdata' : 'Belum Terdata';
                    })
                    ->addColumn('actions', function ($row) use ($isSuperAdmin) {
                        if (!$isSuperAdmin) {
                            return '';
                        }

                        return '<button type="button" class="btn btn-sm btn-danger" onclick="forceDeleteTertagih(' . (int) $row->id . ')">Force Delete</button>';
                    })
                    ->rawColumns(['actions'])
                    ->make(true)
                    ->getData(true);
            });

            return response()->json($payload);
        }

        $defaultYear = (int) date('Y');
        $years = ApiCacheManager::remember('admin:data-tertagih:years', ApiCacheManager::dataTtl(), static function () {
            return DataTertagih::select('year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->toArray();
        });

        if (!in_array($defaultYear, $years, true)) {
            array_unshift($years, $defaultYear);
        }

        $years = array_values(array_unique($years));
        $isSuperAdmin = $this->isSuperAdmin();

        return view('backend.data-tertagih.index', compact('defaultYear', 'years', 'isSuperAdmin'));
    }

    protected function isSuperAdmin(): bool
    {
        $user = Auth::user();

        return $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'));
    }

    protected function ensureSuperAdmin(): void
    {
        abort_unless($this->isSuperAdmin(), 403, 'Akses hanya untuk superadmin.');
    }

    protected function isD2dForceDelete(): bool
    {
        return false;
    }

    protected function forceDeleteRouteName(): string
    {
        return 'data-tertagih.force-destroy';
    }

    protected function importStorageDir(): string
    {
        return 'imports/tertagih';
    }

    protected function importCachePrefix(): string
    {
        return 'data-tertagih-import:';
    }

    protected function importApiCachePrefix(): string
    {
        return 'admin:data-tertagih:';
    }

    protected function importCsvModelClass(): string
    {
        return DataTertagih::class;
    }

    public function downloadTemplate(string $format, string $type)
    {
        $isExample = $type === 'contoh';
        $filename = $isExample
            ? 'data-tertagih-contoh-10-row.' . $format
            : 'data-tertagih-format-kosong.' . $format;

        if ($format === 'csv') {
            return $this->downloadCsvTemplate($filename, $isExample);
        }

        if ($format === 'xlsx') {
            return $this->downloadExcelTemplate($filename, $isExample);
        }

        abort(404);
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'is_terdata' => 'required|in:0,1',
        ]);

        $data = DataTertagih::findOrFail($id);
        $data->is_terdata = (int) $request->is_terdata;
        $data->updated_at = Carbon::now();
        $data->updated_by = Auth::id();
        $data->save();
        ApiCacheManager::forgetByPrefix('admin:data-tertagih:');

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui.',
        ]);
    }

    public function destroy(int $id)
    {
        $data = DataTertagih::findOrFail($id);
        $data->delete();
        ApiCacheManager::forgetByPrefix('admin:data-tertagih:');

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus.',
        ]);
    }

    public function forceDestroy(int $id, ForceDeletePendataanService $service)
    {
        $this->ensureSuperAdmin();

        $result = $service->forceDeleteFromTertagih($id, $this->isD2dForceDelete());

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Force delete berhasil. Tertagih: %d, Pendataan: %d (sudah diarsipkan ke tabel _del).',
                $result['tertagih'],
                $result['pendataan']
            ),
            'result' => $result,
        ]);
    }

    private function downloadCsvTemplate(string $filename, bool $isExample)
    {
        $rows = $isExample ? self::TEMPLATE_EXAMPLE_ROWS : [];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::TEMPLATE_HEADERS);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function downloadExcelTemplate(string $filename, bool $isExample)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(self::TEMPLATE_HEADERS, null, 'A1');

        if ($isExample) {
            $sheet->fromArray(self::TEMPLATE_EXAMPLE_ROWS, null, 'A2');
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

}
