<?php

namespace App\Http\Controllers;
use App\Models\WilayahSamsat;
use App\Models\SengSaamsat;
use App\Models\SengPendataanKendaraan;
use App\Models\SengStatus;
use App\Models\SengStatusVerifikasi;
use App\Models\SengStatusFile;
use App\Models\SengWilayah;
use App\Models\SengWilayahKec;
use App\Models\SengWilayahKel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Support\ApiCacheManager;
use App\Support\PendataanWilayahFilter;

use Illuminate\Http\Request;

class PelaporanController extends Controller
{
    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraan::class;
    }

    protected function pelaporanViewName(): string
    {
        return 'backend.pelaporan.index';
    }

    protected function pelaporanRouteCsv(): string
    {
        return 'pelaporan.csv';
    }

    protected function pelaporanRouteExcel(): string
    {
        return 'pelaporan.excel';
    }

    protected function pelaporanRoutePdf(): string
    {
        return 'pelaporan.pdf';
    }

    protected function exportFilenamePrefix(): string
    {
        return '';
    }

    /**
     * @return list<string>
     */
    private function jurnalColumnHeaders(): array
    {
        return [
            'No.',
            'No.POLISI',
            'NAMA',
            'ALAMAT',
            'KELURAHAN',
            'KECAMATAN',
            'STATUS PENDATAAN',
            'NOMOR HANDPHONE',
            'STATUS VERIFIKASI',
            'NAMA PETUGAS',
        ];
    }

    private function jurnalReportTitle(Request $request): string
    {
        $title = 'DOWNLOAD JURNAL UPPD, KABKOTA, KECAMATAN';

        if ($request->tanggal_start && $request->tanggal_end) {
            $tanggalStart = Carbon::parse($request->tanggal_start)->translatedFormat('d F Y');
            $tanggalEnd = Carbon::parse($request->tanggal_end)->translatedFormat('d F Y');
            $title .= " — Periode: {$tanggalStart} s.d. {$tanggalEnd}";
        }

        return mb_strtoupper($title, 'UTF-8');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildJurnalQuery(Request $request)
    {
        $user = auth()->user();
        $userRole = $user->role ?? null;
        $verifikasis = $this->pendataanModelClass()::query()->with('createdByUser');

        if ($userRole == 4) {
            $scopedKabkota = PendataanWilayahFilter::resolveScopedUserKabkotaId($user) ?? $user->kota;
            $verifikasis->where(function ($query) use ($scopedKabkota) {
                $query->where('kota', $scopedKabkota)
                    ->orWhere('kota_dagri', $scopedKabkota);
            });
        } elseif ($userRole == 7) {
            $verifikasis->where('created_by', $user->id);
        }

        if ($request->status_verifikasi_id) {
            $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
        }

        if ($request->kabkota_id) {
            $kabkotaId = (string) $request->kabkota_id;
            $verifikasis->where(function ($query) use ($kabkotaId) {
                $query->where('kota', $kabkotaId)
                    ->orWhere('kota_dagri', $kabkotaId);
            });
        }

        $this->applyPelaporanWilayahFilters($verifikasis, $request);

        if ($request->kelurahan_samsat) {
            $verifikasis->where('desa', $request->kelurahan_samsat);
        }

        if ($request->tanggal_start && $request->tanggal_end) {
            $verifikasis->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }

        return $verifikasis->orderBy('id');
    }

    /**
     * @return list<string|int>
     */
    private function mapJurnalRow(object $row, int $no): array
    {
        return [
            $no,
            (string) ($row->nopol ?? '-'),
            (string) ($row->nama ?? '-'),
            (string) ($row->alamat ?? '-'),
            (string) ($row->desa_name ?? '-'),
            (string) ($row->kec_name ?? '-'),
            (string) ($row->status_name ?? '-'),
            (string) ($row->nohp ?? '-'),
            (string) ($row->status_verifikasi_name ?? '-'),
            (string) ($row->createdByUser?->name ?? '-'),
        ];
    }

    private function codeVariants($value): array
    {
        $v = trim((string) $value);
        if ($v === '') {
            return [];
        }

        $out = [$v];
        if (ctype_digit($v)) {
            $stripped = ltrim($v, '0');
            $stripped = $stripped === '' ? '0' : $stripped;
            $out[] = $stripped;
            $out[] = (string) (int) $v;
        }

        return array_values(array_unique($out));
    }

    private function resolveKelurahanNameById(?string $kelurahanId): ?string
    {
        if (empty($kelurahanId)) {
            return null;
        }

        $cacheKey = 'admin:master:pelaporan:kelurahan-name-by-id:' . (string) $kelurahanId;

        return ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () use ($kelurahanId) {
            $row = DB::table('wilayah_samsat_kel')
                ->select('kelurahan')
                ->where('id_kelurahan', (string) $kelurahanId)
                ->first();

            return isset($row->kelurahan) ? (string) $row->kelurahan : null;
        });
    }

    private function resolveKecamatanDagriVariants(?string $kecamatanValue): array
    {
        $value = trim((string) $kecamatanValue);
        if ($value === '') {
            return [];
        }

        $variants = $this->codeVariants($value);

        $cacheKey = 'admin:master:pelaporan:kecamatan-dagri-by-id:' . $value;
        $kodeDagri = ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () use ($value) {
            $row = DB::table('wilayah_samsat_kec')
                ->select('kode_dagri')
                ->where('id_kecamatan', (string) $value)
                ->first();

            return isset($row->kode_dagri) ? (string) $row->kode_dagri : null;
        });

        if (!empty($kodeDagri)) {
            $variants = array_merge($variants, $this->codeVariants($kodeDagri));
        }

        return array_values(array_unique(array_filter($variants, static fn ($v) => $v !== '')));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    private function applyPelaporanWilayahFilters($query, Request $request): void
    {
        $kecamatanFilter = $request->kecamatan_samsat ?: $request->district_id;
        $lokasiFilter = PendataanWilayahFilter::resolveLokasiSamsatFilterValue(auth()->user(), $request->lokasi_samsat);

        if ($lokasiFilter !== '') {
            PendataanWilayahFilter::applyLokasiSamsatFilter($query, $lokasiFilter);
        }

        if ($kecamatanFilter) {
            PendataanWilayahFilter::applyKecamatanFilter($query, (string) $kecamatanFilter);
        }
    }

    private function resolveWilayahContext(Request $request): array
    {
        if ($request->filled('kelurahan_samsat')) {
            return ['level' => 'petugas', 'label' => 'Petugas'];
        }
        if ($request->filled('kecamatan_samsat') || $request->filled('district_id')) {
            return ['level' => 'kelurahan', 'label' => 'Kelurahan'];
        }
        if ($request->filled('lokasi_samsat')) {
            return ['level' => 'kecamatan', 'label' => 'Kecamatan'];
        }
        if ($request->filled('kabkota_id')) {
            return ['level' => 'samsat', 'label' => 'Samsat'];
        }
        return ['level' => 'kabkota', 'label' => 'Kabkota'];
    }

    private function mapWilayahName(string $level, ?string $code): string
    {
        $code = trim((string) $code);
        if ($code === '' || $code === '-') {
            return '-';
        }

        if ($level === 'samsat') {
            $samsat = SengSaamsat::query()
                ->select('lokasi', 'lokasi_singkat')
                ->where('id_wilayah_samsat', $code)
                ->orWhere('id', $code)
                ->first();

            return $samsat?->lokasi ?: $samsat?->lokasi_singkat ?: $code;
        }

        if ($level === 'petugas') {
            $petugas = User::query()
                ->select('name')
                ->where('id', $code)
                ->first();

            return $petugas?->name ?: $code;
        }

        if ($level === 'kecamatan') {
            $kec = SengWilayahKec::query()
                ->select('kecamatan')
                ->where('kode_dagri', $code)
                ->orWhere('id_kecamatan', $code)
                ->first();
            if ($kec?->kecamatan) {
                return (string) $kec->kecamatan;
            }

            $kecFromPendataan = $this->pendataanModelClass()::query()
                ->select('kec_name')
                ->where(function ($q) use ($code) {
                    $q->where('kec', $code)
                        ->orWhere('kec_dagri', $code);
                })
                ->whereNotNull('kec_name')
                ->where('kec_name', '!=', '')
                ->value('kec_name');
            if (!empty($kecFromPendataan)) {
                return (string) $kecFromPendataan;
            }
        }

        if ($level === 'kelurahan') {
            $kel = SengWilayahKel::query()
                ->select('kelurahan')
                ->where('id_kelurahan', $code)
                ->first();
            if ($kel?->kelurahan) {
                return (string) $kel->kelurahan;
            }

            $kelFromPendataan = $this->pendataanModelClass()::query()
                ->select('desa_name')
                ->where(function ($q) use ($code) {
                    $q->where('desa', $code)
                        ->orWhere('kel_dagri', $code);
                })
                ->whereNotNull('desa_name')
                ->where('desa_name', '!=', '')
                ->value('desa_name');
            if (!empty($kelFromPendataan)) {
                return (string) $kelFromPendataan;
            }
        }

        $wilayah = SengWilayah::query()
            ->select('nama')
            ->where('id', $code)
            ->first();

        return $wilayah?->nama ?: $code;
    }

    private function resolveKabkotaFromLokasiSamsat(?string $lokasiSamsatId): ?string
    {
        if (empty($lokasiSamsatId)) {
            return null;
        }

        $cacheKey = 'admin:master:pelaporan:kabkota-by-lokasisamsat:' . (string) $lokasiSamsatId;

        return ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () use ($lokasiSamsatId) {
            $samsat = SengSaamsat::query()
                ->select('kabkota')
                ->where('id_wilayah_samsat', (string) $lokasiSamsatId)
                ->orWhere('id', (string) $lokasiSamsatId)
                ->first();

            if ($samsat?->kabkota) {
                return (string) $samsat->kabkota;
            }

            $wilayahSamsat = WilayahSamsat::query()
                ->select('kabkota')
                ->where('id', (string) $lokasiSamsatId)
                ->first();

            return $wilayahSamsat?->kabkota ? (string) $wilayahSamsat->kabkota : null;
        });
    }

    protected function applyUppdScope(Request $request, $user): void
    {
        if (!$user || !$user->hasRole('uppd')) {
            return;
        }

        $lokasiSamsat = (string) ($user->lokasi_samsat ?? '');
        $kabkotaBySamsat = $this->resolveKabkotaFromLokasiSamsat($lokasiSamsat);
        $kabkotaScoped = (string) ($user->kota ?: $kabkotaBySamsat ?: '');

        if ($kabkotaScoped !== '' && ! $request->filled('kabkota_id')) {
            $request->merge(['kabkota_id' => $kabkotaScoped]);
        }
        if ($lokasiSamsat !== '' && ! $request->filled('lokasi_samsat')) {
            $request->merge(['lokasi_samsat' => $lokasiSamsat]);
        }
    }

    private function applyKecamatanScope(Request $request, $user): void
    {
        if (!$user || !$user->hasRole('kecamatan')) {
            return;
        }

        $lokasiSamsat = (string) ($user->lokasi_samsat ?? '');
        $kabkotaBySamsat = $this->resolveKabkotaFromLokasiSamsat($lokasiSamsat);
        $kabkotaScoped = (string) ($user->kota ?: $kabkotaBySamsat ?: '');
        $kecamatanScoped = (string) ($user->kecamatan_samsat ?: $user->kecamatan ?: '');

        if ($kabkotaScoped !== '') {
            $request->merge(['kabkota_id' => $kabkotaScoped]);
        }
        if ($lokasiSamsat !== '') {
            $request->merge(['lokasi_samsat' => $lokasiSamsat]);
        }
        if ($kecamatanScoped !== '') {
            $request->merge(['kecamatan_samsat' => $kecamatanScoped]);
        }

        // Role kecamatan hanya boleh rekap.
        $request->merge(['tipe' => 2]);
    }

    private function applyKelurahanScope(Request $request, $user): void
    {
        if (!$user || !$user->hasRole('kelurahan')) {
            return;
        }

        $lokasiSamsat = (string) ($user->lokasi_samsat ?? '');
        $kabkotaBySamsat = $this->resolveKabkotaFromLokasiSamsat($lokasiSamsat);
        $kabkotaScoped = (string) ($user->kota ?: $kabkotaBySamsat ?: '');
        $kecamatanScoped = (string) ($user->kecamatan_samsat ?: $user->kecamatan ?: '');
        $kelurahanScoped = (string) ($user->kelurahan_samsat ?: $user->kelurahan ?: '');

        if ($kabkotaScoped !== '') {
            $request->merge(['kabkota_id' => $kabkotaScoped]);
        }
        if ($lokasiSamsat !== '') {
            $request->merge(['lokasi_samsat' => $lokasiSamsat]);
        }
        if ($kecamatanScoped !== '') {
            $request->merge(['kecamatan_samsat' => $kecamatanScoped]);
        }
        if ($kelurahanScoped !== '') {
            $request->merge(['kelurahan_samsat' => $kelurahanScoped]);
        }

        // Role kelurahan hanya boleh rekap.
        $request->merge(['tipe' => 2]);
    }

    public function index(Request $request)
    {
        $user = User::findOrFail(auth()->id());
        $isKabkota = $user->hasRole('kabkota');
        $isUppd = $user->hasRole('uppd');
        $isKecamatan = $user->hasRole('kecamatan');
        $isKelurahan = $user->hasRole('kelurahan');
        $selectedKabkotaId = null;
        $userLokasiSamsat = SengSaamsat::resolveDropdownLokasiId($user->lokasi_samsat ?? null) ?? '';
        $selectedKecamatanSamsatId = (string) ($user->kecamatan_samsat ?: $user->kecamatan ?: '');
        $selectedKelurahanSamsatId = (string) ($user->kelurahan_samsat ?: $user->kelurahan ?: '');
        $kabkotaBySamsat = $this->resolveKabkotaFromLokasiSamsat($userLokasiSamsat);
        $scopedKabkotaId = (string) ($user->kota ?: $kabkotaBySamsat ?: '');
        $isScopedKabkota = $isKabkota || $isUppd || $isKecamatan || $isKelurahan;
        $isLokasiSamsatLocked = $isUppd || $isKecamatan || $isKelurahan;
        $isKecamatanSamsatLocked = $isKecamatan || $isKelurahan;
        $isKelurahanSamsatLocked = $isKelurahan;
        $isRekapOnlyRole = $isKecamatan || $isKelurahan;

        if ($isScopedKabkota && !empty($scopedKabkotaId)) {
            $selectedKabkotaId = $scopedKabkotaId;
            $kabkotas = ApiCacheManager::remember('admin:master:kabkota:pelaporan-scope:' . (string) $scopedKabkotaId, ApiCacheManager::masterTtl(), static function () use ($scopedKabkotaId) {
                return SengWilayah::query()
                    ->where('id_up', 33)
                    ->where('id', $scopedKabkotaId)
                    ->get();
            });
        } else {
            $kabkotas = ApiCacheManager::remember('admin:master:kabkota:all', ApiCacheManager::masterTtl(), static function () {
                return SengWilayah::query()
                    ->where('id_up', 33)
                    ->get();
            });
        }

        return view($this->pelaporanViewName(), compact(
            'kabkotas',
            'isKabkota',
            'isScopedKabkota',
            'selectedKabkotaId',
            'userLokasiSamsat',
            'selectedKecamatanSamsatId',
            'selectedKelurahanSamsatId',
            'isLokasiSamsatLocked',
            'isKecamatanSamsatLocked',
            'isKelurahanSamsatLocked',
            'isRekapOnlyRole'
        ) + [
            'pelaporanRouteCsv' => $this->pelaporanRouteCsv(),
            'pelaporanRouteExcel' => $this->pelaporanRouteExcel(),
            'pelaporanRoutePdf' => $this->pelaporanRoutePdf(),
        ]);
    }

    public function pelaporanCsv(Request $request){
        $user = auth()->user();
        $this->applyUppdScope($request, $user);
        $this->applyKecamatanScope($request, $user);
        $this->applyKelurahanScope($request, $user);
        if ($user && $user->hasRole('kabkota') && !empty($user->kota)) {
            $request->merge(['kabkota_id' => $user->kota]);
        }

        $tipe = $request->tipe;
        if ($tipe == 1) {
            return $this->jurnalCsv($request);  // Kirim request ke fungsi
        } elseif ($tipe == 2) {
            return $this->rekapCsv($request);  // Kirim request ke fungsi
        }
    
        return response()->json(['message' => 'Tipe tidak valid'], 400);
    }

    public function pelaporanExcel(Request $request)
    {
        $user = auth()->user();
        $this->applyUppdScope($request, $user);
        $this->applyKecamatanScope($request, $user);
        $this->applyKelurahanScope($request, $user);
        if ($user && $user->hasRole('kabkota') && !empty($user->kota)) {
            $request->merge(['kabkota_id' => $user->kota]);
        }

        $tipe = $request->tipe;
        if ($tipe == 1) {
            return $this->jurnalExcel($request);
        } elseif ($tipe == 2) {
            return $this->rekapExcel($request);
        }

        return response()->json(['message' => 'Tipe tidak valid'], 400);
    }

    public function jurnalCsv(Request $request)
    {
        $verifikasis = $this->buildJurnalQuery($request);
        $judul = $this->jurnalReportTitle($request);
        $headers = $this->jurnalColumnHeaders();

        $filename = $this->exportFilenamePrefix() . 'jurnal_pelaporan_' . date('YmdHis') . '.csv';
        $responseHeaders = [
            'Content-Type' => 'text/csv',
            "Content-Disposition" => "attachment; filename=$filename",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function () use ($verifikasis, $judul, $headers) {
            ob_clean();
            flush();

            $file = fopen('php://output', 'w');
            fputcsv($file, ['']);
            fputcsv($file, [$judul]);
            fputcsv($file, ['']);
            fputcsv($file, $headers);

            $no = 1;
            foreach ($verifikasis->cursor() as $verifikasi) {
                fputcsv($file, $this->mapJurnalRow($verifikasi, $no++));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }

    public function rekapCsv(Request $request)
    {
        $kotajudul = '';
        $kotajudul_id = '';
        if ($request->kabkota_id) {
            $wilayah = SengWilayah::where('id', $request->kabkota_id)->first();
            $kotajudul = $wilayah ? $wilayah->nama : '';
            $kotajudul = ' '.$kotajudul;
            $kotajudul_id = '_'.$request->kabkota_id;
        }

        $kecjudul = '';
        $kecjudul_id = '';
        $kecamatanFilter = $request->kecamatan_samsat ?: $request->district_id;
        if ($kecamatanFilter) {
            $wilayah = SengWilayah::where('id', $kecamatanFilter)->first();
            $kecjudul = $wilayah ? $wilayah->nama : '';
            $kecjudul = ' Kec. '.$kecjudul;
            $kecjudul_id = '_'.$kecamatanFilter;
        }

        $periode = '';
        if ($request->tanggal_start && $request->tanggal_end) {
            $tanggalStart = Carbon::parse($request->tanggal_start)->translatedFormat('d F Y');
            $tanggalEnd = Carbon::parse($request->tanggal_end)->translatedFormat('d F Y');
            $periode = " Periode: $tanggalStart s.d. $tanggalEnd";
        }
        
        $judul = mb_strtoupper('REKAP PELAPORAN ' . $kotajudul . ' ' . $kecjudul . ' ' . $periode, 'UTF-8');

        $fileName = $this->exportFilenamePrefix() . "rekap_pelaporan_" . date('YmdHis') . ".csv";
        $filePath = storage_path('app/' . $fileName);
        $file = fopen($filePath, 'w');

        fputcsv($file, ['']); // baris kosong
        fputcsv($file, [$judul]); // header utama
        fputcsv($file, ['']); // baris kosong lagi

        $rekapPayload = $this->getRekapData($request);
        $rekapData = $rekapPayload['rows'];
        $wilayahLabel = $rekapPayload['wilayahLabel'];

        // Header CSV
        fputcsv($file, [
            'NO', 'WILAYAH (' . strtoupper($wilayahLabel) . ')', 'DIMILIKI', 'GANTI KEPEMILIKAN', 'RUSAK BERAT', 'HILANG',
            'MENINGGAL DUNIA TANPA AHLI WARIS', 'MENUTUP USAHA / PAILIT', 
            'DICABUT REGISTRASINYA', 'TERKENA BENCANA ALAM', 
            'TIDAK MEMPUNYAI KEKAYAAN LAGI', 'TIDAK DIKETAHUI ALAMAT'
        ]);

        // Tulis data ke CSV
        $no = 1;
        foreach ($rekapData as $row) {
            fputcsv($file, [
                $no++, 
                $row->wilayah,
                $row->DIMILIKI,
                $row->GANTI_KEPEMILIKAN,
                $row->RUSAK_BERAT,
                $row->HILANG,
                $row->MENINGGAL_DUNIA,
                $row->MENUTUP_USAHA,
                $row->DICABUT_REGISTRASI,
                $row->BENCANA_ALAM,
                $row->TIDAK_PUNYA_KEKAYAAN,
                $row->TIDAK_DIKEATAHUI_ALAMAT
            ]);
        }

        fclose($file);

        // Kirim response sebagai file download dan hapus file setelah dikirim
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function jurnalExcel(Request $request)
    {
        $verifikasis = $this->buildJurnalQuery($request);
        $judul = $this->jurnalReportTitle($request);
        $columnHeaders = $this->jurnalColumnHeaders();
        $lastCol = 'J';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A2', $judul);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->fromArray($columnHeaders, null, 'A4');
        $sheet->getStyle("A4:{$lastCol}4")->getFont()->setBold(true);
        $sheet->getStyle("A4:{$lastCol}4")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9D9D9');

        $rowNumber = 5;
        $no = 1;
        foreach ($verifikasis->cursor() as $verifikasi) {
            $sheet->fromArray([$this->mapJurnalRow($verifikasi, $no++)], null, 'A' . $rowNumber);
            $rowNumber++;
        }

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = $this->exportFilenamePrefix() . 'jurnal_pelaporan_' . date('YmdHis') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function rekapExcel(Request $request)
    {
        $rekapPayload = $this->getRekapData($request);
        $rekapData = $rekapPayload['rows'];
        $wilayahLabel = $rekapPayload['wilayahLabel'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'NO', 'WILAYAH (' . strtoupper($wilayahLabel) . ')', 'DIMILIKI', 'GANTI KEPEMILIKAN', 'RUSAK BERAT', 'HILANG',
            'MENINGGAL DUNIA TANPA AHLI WARIS', 'MENUTUP USAHA / PAILIT',
            'DICABUT REGISTRASINYA', 'TERKENA BENCANA ALAM',
            'TIDAK MEMPUNYAI KEKAYAAN LAGI', 'TIDAK DIKETAHUI ALAMAT',
        ], null, 'A1');

        $rowNumber = 2;
        $no = 1;
        foreach ($rekapData as $row) {
            $sheet->fromArray([[
                $no++,
                $row->wilayah,
                $row->DIMILIKI,
                $row->GANTI_KEPEMILIKAN,
                $row->RUSAK_BERAT,
                $row->HILANG,
                $row->MENINGGAL_DUNIA,
                $row->MENUTUP_USAHA,
                $row->DICABUT_REGISTRASI,
                $row->BENCANA_ALAM,
                $row->TIDAK_PUNYA_KEKAYAAN,
                $row->TIDAK_DIKEATAHUI_ALAMAT,
            ]], null, 'A' . $rowNumber);
            $rowNumber++;
        }

        $filename = $this->exportFilenamePrefix() . "rekap_pelaporan_" . date('YmdHis') . ".xlsx";
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }


    public function pelaporanPdf(Request $request){
        $user = auth()->user();
        $this->applyUppdScope($request, $user);
        $this->applyKecamatanScope($request, $user);
        $this->applyKelurahanScope($request, $user);
        if ($user && $user->hasRole('kabkota') && !empty($user->kota)) {
            $request->merge(['kabkota_id' => $user->kota]);
        }

        $tipe = $request->tipe;
        if ($tipe == 1) {
            return $this->jurnalPdf($request);  // Kirim request ke fungsi
        } elseif ($tipe == 2) {
            return $this->rekapPdf($request);  // Kirim request ke fungsi
        }
    
        return response()->json(['message' => 'Tipe tidak valid'], 400);
    }

    public function jurnalPdf(Request $request)
    {
        $verifikasis = $this->buildJurnalQuery($request);
        $judul = htmlspecialchars($this->jurnalReportTitle($request), ENT_QUOTES, 'UTF-8');
        $columnHeaders = $this->jurnalColumnHeaders();

        $headerCells = '';
        foreach ($columnHeaders as $header) {
            $headerCells .= '<th>' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</th>';
        }

        $bodyRows = '';
        $no = 1;
        foreach ($verifikasis->cursor() as $verifikasi) {
            $cells = '';
            foreach ($this->mapJurnalRow($verifikasi, $no++) as $cell) {
                $cells .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $bodyRows .= '<tr>' . $cells . '</tr>';
        }

        $html = "<html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; font-size: 9px; }
                h2 { font-size: 12px; text-align: center; margin-bottom: 16px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #000; padding: 4px; text-align: left; vertical-align: top; }
                th { background-color: #d9d9d9; font-weight: bold; }
            </style>
        </head>
        <body>
            <h2>{$judul}</h2>
            <table>
                <thead><tr>{$headerCells}</tr></thead>
                <tbody>{$bodyRows}</tbody>
            </table>
        </body>
        </html>";

        $dompdf = new Dompdf();
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf->setOptions($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = $this->exportFilenamePrefix() . 'jurnal_pelaporan_' . date('YmdHis') . '.pdf';

        return response()->streamDownload(
            fn () => print($dompdf->output()),
            $filename
        );
    }

    public function rekapPdf(Request $request)
    {
        $kotajudul = '';
        $kecjudul = '';
        $periode = '';

        if ($request->kabkota_id) {
            $wilayah = SengWilayah::find($request->kabkota_id);
            $kotajudul = $wilayah ? ' ' . $wilayah->nama : '';
        }

        if ($request->district_id) {
            $wilayah = SengWilayah::find($request->district_id);
            $kecjudul = $wilayah ? ' Kec. ' . $wilayah->nama : '';
        }

        if ($request->tanggal_start && $request->tanggal_end) {
            $periode = " Periode: {$request->tanggal_start} s.d. {$request->tanggal_end}";
        }

        $judul = mb_strtoupper('REKAP PELAPORAN ' . $kotajudul . $kecjudul . $periode, 'UTF-8');

        $rekapPayload = $this->getRekapData($request);
        $rekapData = $rekapPayload['rows'];
        $wilayahLabel = $rekapPayload['wilayahLabel'];

        // Mulai HTML untuk PDF
        $html = '
            <style>
                body { font-size: 10px; }
                table { font-size: 9px; }
                h1, h2, h3 { font-size: 12px; }
            </style>
            <h3 style="text-align:center;">' . $judul . '</h3>
            <table border="1" cellpadding="5" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>WILAYAH (' . strtoupper($wilayahLabel) . ')</th>
                        <th>DIMILIKI</th>
                        <th>GANTI KEPEMILIKAN</th>
                        <th>RUSAK BERAT</th>
                        <th>HILANG</th>
                        <th>MENINGGAL DUNIA TANPA AHLI WARIS</th>
                        <th>MENUTUP USAHA / PAILIT</th>
                        <th>DICABUT REGISTRASINYA</th>
                        <th>TERKENA BENCANA ALAM</th>
                        <th>TIDAK MEMPUNYAI KEKAYAAN LAGI</th>
                        <th>TIDAK DIKETAHUI ALAMAT</th>
                    </tr>
                </thead>
                <tbody>';

        $no = 1;
        foreach ($rekapData as $row) {
            $html .= '
                <tr>
                    <td>' . $no++ . '</td>
                    <td>' . $row->wilayah . '</td>
                    <td>' . $row->DIMILIKI . '</td>
                    <td>' . $row->GANTI_KEPEMILIKAN . '</td>
                    <td>' . $row->RUSAK_BERAT . '</td>
                    <td>' . $row->HILANG . '</td>
                    <td>' . $row->MENINGGAL_DUNIA . '</td>
                    <td>' . $row->MENUTUP_USAHA . '</td>
                    <td>' . $row->DICABUT_REGISTRASI . '</td>
                    <td>' . $row->BENCANA_ALAM . '</td>
                    <td>' . $row->TIDAK_PUNYA_KEKAYAAN . '</td>
                    <td>' . $row->TIDAK_DIKEATAHUI_ALAMAT . '</td>
                </tr>';
        }

        $html .= '</tbody></table>';

        // Dompdf setup
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Download langsung PDF
        $fileName = $this->exportFilenamePrefix() . 'rekap_pelaporan_' . date('YmdHis') . '.pdf';
        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"$fileName\"");
    }

    private function getRekapData(Request $request): array
    {
        $baseQuery = $this->pendataanModelClass()::query();

        if ($request->status_verifikasi_id) {
            $baseQuery->where('status_verifikasi', $request->status_verifikasi_id);
        }

        if ($request->kabkota_id) {
            $baseQuery->where('kota_dagri', $request->kabkota_id);
        }

        $this->applyPelaporanWilayahFilters($baseQuery, $request);

        if ($request->kelurahan_samsat) {
            $baseQuery->whereIn('desa', $this->codeVariants($request->kelurahan_samsat));
        }

        if ($request->tanggal_start && $request->tanggal_end) {
            $baseQuery->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }

        $context = $this->resolveWilayahContext($request);
        $level = $context['level'];
        $wilayahLabel = $context['label'];

        if ($level === 'petugas') {
            $groupExpr = "COALESCE(NULLIF(created_by, ''), '-')";
        } elseif ($level === 'kelurahan') {
            $groupExpr = "COALESCE(NULLIF(desa_name, ''), NULLIF(desa, ''), '-')";
        } elseif ($level === 'kecamatan') {
            $groupExpr = "COALESCE(NULLIF(kec_name, ''), NULLIF(kec_dagri, ''), '-')";
        } elseif ($level === 'samsat') {
            $groupExpr = "COALESCE(NULLIF(kota, ''), '-')";
        } else {
            $groupExpr = "COALESCE(NULLIF(kota_name, ''), NULLIF(kota_dagri, ''), '-')";
        }

        $sub = $baseQuery
            ->selectRaw("$groupExpr AS group_code")
            ->selectRaw('status');

        $rows = DB::query()
            ->fromSub($sub, 'rekap_source')
            ->select('group_code')
            ->selectRaw("SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS DIMILIKI")
            ->selectRaw("SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS GANTI_KEPEMILIKAN")
            ->selectRaw("SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) AS RUSAK_BERAT")
            ->selectRaw("SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) AS HILANG")
            ->selectRaw("SUM(CASE WHEN status = 5 THEN 1 ELSE 0 END) AS MENINGGAL_DUNIA")
            ->selectRaw("SUM(CASE WHEN status = 6 THEN 1 ELSE 0 END) AS MENUTUP_USAHA")
            ->selectRaw("SUM(CASE WHEN status = 7 THEN 1 ELSE 0 END) AS DICABUT_REGISTRASI")
            ->selectRaw("SUM(CASE WHEN status = 8 THEN 1 ELSE 0 END) AS BENCANA_ALAM")
            ->selectRaw("SUM(CASE WHEN status = 9 THEN 1 ELSE 0 END) AS TIDAK_PUNYA_KEKAYAAN")
            ->selectRaw("SUM(CASE WHEN status = 10 THEN 1 ELSE 0 END) AS TIDAK_DIKEATAHUI_ALAMAT")
            ->groupBy('group_code')
            ->orderBy('group_code')
            ->get();

        $rows = $rows->map(function ($row) use ($level) {
            $row->wilayah = $this->mapWilayahName($level, isset($row->group_code) ? (string) $row->group_code : null);
            return $row;
        })->sortBy('wilayah')->values();

        return [
            'rows' => $rows,
            'wilayahLabel' => $wilayahLabel,
        ];
    }

}
