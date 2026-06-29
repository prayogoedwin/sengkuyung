<?php

namespace App\Http\Controllers;
use App\Models\WilayahSamsat;
use App\Models\SengSaamsat;
use App\Models\SengPendataanKendaraan;
use App\Models\DataTertagih;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Support\ApiCacheManager;
use App\Support\PendataanWilayahFilter;
use App\Support\VerifikasiStatusGroups;

use Illuminate\Http\Request;

class PelaporanController extends Controller
{
    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraan::class;
    }

    protected function dataTertagihModelClass(): string
    {
        return DataTertagih::class;
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

    /**
     * @return list<array{id: int, label: string}>
     */
    private function rekapStatusColumnDefs(): array
    {
        return [
            ['id' => 1, 'label' => 'DIMILIKI'],
            ['id' => 2, 'label' => 'GANTI KEPIMILIKAN'],
            ['id' => 3, 'label' => 'RUSAK BERAT'],
            ['id' => 4, 'label' => 'HILANG'],
            ['id' => 5, 'label' => 'MENINGGAL DUNIA TANPA AHLI WARIS'],
            ['id' => 6, 'label' => 'MENUTUP USAHA/ PAILIT'],
            ['id' => 7, 'label' => 'DICABUT REGISTRASINYA'],
            ['id' => 10, 'label' => 'TIDAK DIKETAHUI ALAMAT/ KEDUDUKAN TERAKHIRNYA'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveRekapLayout(?User $user, Request $request): array
    {
        $isAdminProv = $user && $user->hasAnyRole(['super-admin', 'superadmin', 'admin', 'adminprov']);
        $hasKecamatanFilter = $request->filled('kecamatan_samsat') || $request->filled('district_id');
        $hasKabkotaFilter = $request->filled('kabkota_id');

        if ($isAdminProv && ! $hasKabkotaFilter) {
            return [
                'variant' => 'provinsi',
                'title' => 'DOWNLOAD REKAP ADMIN PROVINSI',
                'wilayahColumn' => 'UPPD',
                'mapLevel' => 'kabkota',
            ];
        }

        return [
            'variant' => 'scoped',
            'title' => 'DOWNLOAD REKAP ADMIN UPPD, KABKOTA, KECAMATAN',
            'wilayahColumn' => 'KECAMATAN',
            'mapLevel' => $hasKecamatanFilter ? 'kelurahan' : 'kecamatan',
        ];
    }

    private function rekapReportTitle(Request $request, array $layout): string
    {
        $title = $layout['title'];
        if ($request->tanggal_start && $request->tanggal_end) {
            $tanggalStart = Carbon::parse($request->tanggal_start)->translatedFormat('d F Y');
            $tanggalEnd = Carbon::parse($request->tanggal_end)->translatedFormat('d F Y');
            $title .= " — Periode: {$tanggalStart} s.d. {$tanggalEnd}";
        }

        return mb_strtoupper($title, 'UTF-8');
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     */
    private function applyDataTertagihRekapFiltersOnQuery($query, Request $request, string $tableAlias = 'dt'): void
    {
        if ($request->tanggal_start && $request->tanggal_end) {
            $query->whereBetween("{$tableAlias}.created_at", [$request->tanggal_start, $request->tanggal_end]);
        } else {
            $query->where("{$tableAlias}.year", (int) now()->year);
        }

        if ($request->kabkota_id) {
            $lokasiKabkota = SengSaamsat::lokasiFilterVariantsByKabkota((string) $request->kabkota_id);
            if ($lokasiKabkota !== []) {
                $query->whereIn("{$tableAlias}.id_lokasi_samsat", $lokasiKabkota);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $lokasiFilter = PendataanWilayahFilter::resolveLokasiSamsatFilterValue(auth()->user(), $request->lokasi_samsat);
        if ($lokasiFilter !== '') {
            $query->whereIn("{$tableAlias}.id_lokasi_samsat", SengSaamsat::lokasiFilterVariants($lokasiFilter));
        }

        $kecamatanFilter = $request->kecamatan_samsat ?: $request->district_id;
        if ($kecamatanFilter) {
            $query->whereIn("{$tableAlias}.id_kecamatan", $this->codeVariants((string) $kecamatanFilter));
        }

        if ($request->kelurahan_samsat) {
            $query->whereIn("{$tableAlias}.id_kelurahan", $this->codeVariants((string) $request->kelurahan_samsat));
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    private function applyPendataanRekapFilters($query, Request $request): void
    {
        if ($request->status_verifikasi_id) {
            $query->where('status_verifikasi', $request->status_verifikasi_id);
        }

        if ($request->kabkota_id) {
            $query->where('kota_dagri', $request->kabkota_id);
        }

        $this->applyPelaporanWilayahFilters($query, $request);

        if ($request->kelurahan_samsat) {
            $query->whereIn('desa', $this->codeVariants((string) $request->kelurahan_samsat));
        }

        if ($request->tanggal_start && $request->tanggal_end) {
            $query->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        } else {
            $query->whereYear('created_at', now()->year);
        }
    }

    private function tertagihGroupExpression(array $layout): string
    {
        if ($layout['variant'] === 'provinsi') {
            return "COALESCE(NULLIF(ss.kabkota, ''), '-')";
        }

        if ($layout['mapLevel'] === 'kelurahan') {
            return "COALESCE(NULLIF(dt.id_kelurahan, ''), '-')";
        }

        return "COALESCE(NULLIF(dt.id_kecamatan, ''), '-')";
    }

    private function pendataanGroupExpression(array $layout): string
    {
        if ($layout['variant'] === 'provinsi') {
            return "COALESCE(NULLIF(kota_dagri, ''), '-')";
        }

        if ($layout['mapLevel'] === 'kelurahan') {
            return "COALESCE(NULLIF(desa, ''), NULLIF(desa_name, ''), '-')";
        }

        return "COALESCE(NULLIF(kec, ''), NULLIF(kec_dagri, ''), NULLIF(kec_name, ''), '-')";
    }

    /**
     * @return array<string, array{potensi: int, belum: int, sudah: int}>
     */
    private function aggregateTertagihRekapStats(Request $request, array $layout): array
    {
        $modelClass = $this->dataTertagihModelClass();
        $table = (new $modelClass())->getTable();
        $groupExpr = $this->tertagihGroupExpression($layout);

        $query = DB::table("{$table} as dt")
            ->leftJoin('seng_samsat as ss', function ($join) {
                $join->on('ss.id', '=', 'dt.id_lokasi_samsat')
                    ->orOn('ss.id_wilayah_samsat', '=', 'dt.id_lokasi_samsat');
            });

        $this->applyDataTertagihRekapFiltersOnQuery($query, $request, 'dt');

        $rows = $query
            ->selectRaw("{$groupExpr} as group_code")
            ->selectRaw('COUNT(*) as potensi')
            ->selectRaw('SUM(CASE WHEN dt.is_terdata = 0 THEN 1 ELSE 0 END) as belum')
            ->selectRaw('SUM(CASE WHEN dt.is_terdata = 1 THEN 1 ELSE 0 END) as sudah')
            ->groupBy('group_code')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $code = (string) ($row->group_code ?? '-');
            $out[$code] = [
                'potensi' => (int) ($row->potensi ?? 0),
                'belum' => (int) ($row->belum ?? 0),
                'sudah' => (int) ($row->sudah ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array<string, array{obyek: int, pkb: float}>>
     */
    private function aggregatePendataanRekapStats(Request $request, array $layout): array
    {
        $groupExpr = $this->pendataanGroupExpression($layout);
        $verifikasiIds = VerifikasiStatusGroups::verifikasiIds();
        $statusDefs = $this->rekapStatusColumnDefs();

        $query = $this->pendataanModelClass()::query();
        $this->applyPendataanRekapFilters($query, $request);
        if ($verifikasiIds !== []) {
            $query->whereIn('status_verifikasi', $verifikasiIds);
        }

        $selects = ["{$groupExpr} as group_code"];
        foreach ($statusDefs as $def) {
            $id = (int) $def['id'];
            $selects[] = "SUM(CASE WHEN status = {$id} THEN 1 ELSE 0 END) as status_{$id}_obyek";
            $selects[] = "SUM(CASE WHEN status = {$id} THEN COALESCE(pkb_pokok, 0) ELSE 0 END) as status_{$id}_pkb";
        }

        $rows = $query
            ->selectRaw(implode(', ', $selects))
            ->groupBy('group_code')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $code = (string) ($row->group_code ?? '-');
            $statuses = [];
            foreach ($statusDefs as $def) {
                $id = (int) $def['id'];
                $statuses[(string) $id] = [
                    'obyek' => (int) ($row->{"status_{$id}_obyek"} ?? 0),
                    'pkb' => (float) ($row->{"status_{$id}_pkb"} ?? 0),
                ];
            }
            $out[$code] = $statuses;
        }

        return $out;
    }

    private function mapRekapWilayahName(array $layout, string $groupCode): string
    {
        if ($groupCode === '-' || $groupCode === '') {
            return '-';
        }

        return $this->mapWilayahName((string) $layout['mapLevel'], $groupCode);
    }

    /**
     * @return array{layout: array<string, mixed>, title: string, rows: list<array<string, mixed>>, totals: array<string, mixed>}
     */
    private function buildRekapExportPayload(Request $request): array
    {
        $user = auth()->user();
        $layout = $this->resolveRekapLayout($user, $request);
        $title = $this->rekapReportTitle($request, $layout);
        $statusDefs = $this->rekapStatusColumnDefs();

        $tertagihStats = $this->aggregateTertagihRekapStats($request, $layout);
        $pendataanStats = $this->aggregatePendataanRekapStats($request, $layout);

        $allCodes = collect(array_keys($tertagihStats))
            ->merge(array_keys($pendataanStats))
            ->unique()
            ->sort()
            ->values();

        $rows = [];
        $totals = [
            'potensi' => 0,
            'belum' => 0,
            'sudah' => 0,
            'statuses' => [],
        ];
        foreach ($statusDefs as $def) {
            $totals['statuses'][(string) $def['id']] = ['obyek' => 0, 'pkb' => 0.0];
        }

        foreach ($allCodes as $code) {
            $code = (string) $code;
            $t = $tertagihStats[$code] ?? ['potensi' => 0, 'belum' => 0, 'sudah' => 0];
            $p = $pendataanStats[$code] ?? [];
            $statuses = [];
            foreach ($statusDefs as $def) {
                $id = (string) $def['id'];
                $statuses[$id] = $p[$id] ?? ['obyek' => 0, 'pkb' => 0.0];
                $totals['statuses'][$id]['obyek'] += $statuses[$id]['obyek'];
                $totals['statuses'][$id]['pkb'] += $statuses[$id]['pkb'];
            }

            $rows[] = [
                'wilayah' => $this->mapRekapWilayahName($layout, $code),
                'potensi' => $t['potensi'],
                'belum' => $t['belum'],
                'sudah' => $t['sudah'],
                'statuses' => $statuses,
            ];

            $totals['potensi'] += $t['potensi'];
            $totals['belum'] += $t['belum'];
            $totals['sudah'] += $t['sudah'];
        }

        return compact('layout', 'title', 'rows', 'totals');
    }

    /**
     * @return list<string|int|float>
     */
    private function mapRekapDataRow(array $row, int $no, array $statusDefs): array
    {
        $line = [
            $no,
            $row['wilayah'],
            $row['potensi'],
            $row['belum'],
            $row['sudah'],
        ];
        foreach ($statusDefs as $def) {
            $id = (string) $def['id'];
            $stat = $row['statuses'][$id] ?? ['obyek' => 0, 'pkb' => 0];
            $line[] = $stat['obyek'];
            $line[] = $stat['pkb'];
        }

        return $line;
    }

    /**
     * @return list<list<string>>
     */
    private function rekapHeaderMatrix(array $layout): array
    {
        $statusDefs = $this->rekapStatusColumnDefs();
        $statusLabels = array_column($statusDefs, 'label');

        $row4 = ['No', $layout['wilayahColumn'], 'JUMLAH POTENSI KENDARAAN', 'JUMLAH KENDARAAN BELUM TERDATA', 'JUMLAH KENDARAAN SUDAH TERDATA', 'STATUS KENDARAAN TERVERIFIKASI'];
        while (count($row4) < 5 + count($statusLabels) * 2) {
            $row4[] = '';
        }

        $row5 = ['', '', '', '', ''];
        foreach ($statusLabels as $label) {
            $row5[] = $label;
            $row5[] = '';
        }

        $row6 = ['', '', '', '', ''];
        foreach ($statusDefs as $_def) {
            $row6[] = 'OBYEK';
            $row6[] = 'NILAI PKB';
        }

        return [$row4, $row5, $row6];
    }

    private function writeRekapExcelSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $payload): void
    {
        $layout = $payload['layout'];
        $statusDefs = $this->rekapStatusColumnDefs();
        $lastColIndex = 5 + count($statusDefs) * 2;
        $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);

        $sheet->setCellValue('A2', $payload['title']);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = $this->rekapHeaderMatrix($layout);
        $sheet->fromArray($headers[0], null, 'A4');
        $sheet->fromArray($headers[1], null, 'A5');
        $sheet->fromArray($headers[2], null, 'A6');

        $sheet->mergeCells("F4:{$lastCol}4");
        $sheet->getStyle("A4:{$lastCol}6")->getFont()->setBold(true);
        $sheet->getStyle("A4:{$lastCol}6")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9D9D9');

        $col = 6;
        foreach ($statusDefs as $def) {
            $start = Coordinate::stringFromColumnIndex($col);
            $end = Coordinate::stringFromColumnIndex($col + 1);
            $sheet->mergeCells("{$start}5:{$end}5");
            $col += 2;
        }

        $rowNumber = 7;
        $no = 1;
        foreach ($payload['rows'] as $row) {
            $sheet->fromArray([$this->mapRekapDataRow($row, $no++, $statusDefs)], null, 'A' . $rowNumber);
            $rowNumber++;
        }

        $sheet->fromArray([$this->mapRekapDataRow([
            'wilayah' => 'JUMLAH',
            'potensi' => $payload['totals']['potensi'],
            'belum' => $payload['totals']['belum'],
            'sudah' => $payload['totals']['sudah'],
            'statuses' => $payload['totals']['statuses'],
        ], 0, $statusDefs)], null, 'A' . $rowNumber);

        for ($i = 1; $i <= $lastColIndex; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    private function buildRekapPdfHtml(array $payload): string
    {
        $layout = $payload['layout'];
        $statusDefs = $this->rekapStatusColumnDefs();
        $title = htmlspecialchars($payload['title'], ENT_QUOTES, 'UTF-8');

        $head = '<tr><th>No</th><th>' . htmlspecialchars($layout['wilayahColumn'], ENT_QUOTES, 'UTF-8') . '</th>'
            . '<th>JUMLAH POTENSI KENDARAAN</th><th>JUMLAH KENDARAAN BELUM TERDATA</th><th>JUMLAH KENDARAAN SUDAH TERDATA</th>';
        foreach ($statusDefs as $def) {
            $label = htmlspecialchars($def['label'], ENT_QUOTES, 'UTF-8');
            $head .= "<th colspan=\"2\">{$label}</th>";
        }
        $head .= '</tr><tr><th></th><th></th><th></th><th></th><th></th>';
        foreach ($statusDefs as $_def) {
            $head .= '<th>OBYEK</th><th>NILAI PKB</th>';
        }
        $head .= '</tr>';

        $body = '';
        $no = 1;
        foreach ($payload['rows'] as $row) {
            $cells = '';
            foreach ($this->mapRekapDataRow($row, $no++, $statusDefs) as $cell) {
                $cells .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $body .= "<tr>{$cells}</tr>";
        }
        $totalCells = '';
        foreach ($this->mapRekapDataRow([
            'wilayah' => 'JUMLAH',
            'potensi' => $payload['totals']['potensi'],
            'belum' => $payload['totals']['belum'],
            'sudah' => $payload['totals']['sudah'],
            'statuses' => $payload['totals']['statuses'],
        ], 0, $statusDefs) as $cell) {
            $totalCells .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        $body .= "<tr>{$totalCells}</tr>";

        return "<html><head><style>
            body{font-family:Arial,sans-serif;font-size:7px;}
            h2{font-size:11px;text-align:center;}
            table{width:100%;border-collapse:collapse;}
            th,td{border:1px solid #000;padding:2px;text-align:left;vertical-align:top;}
            th{background:#d9d9d9;}
        </style></head><body>
            <h2>{$title}</h2>
            <table><thead>{$head}</thead><tbody>{$body}</tbody></table>
        </body></html>";
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
        $isLokasiSamsatLocked = $userLokasiSamsat !== ''
            && ($isKecamatan || $isKelurahan);
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
        $payload = $this->buildRekapExportPayload($request);
        $statusDefs = $this->rekapStatusColumnDefs();
        $headers = $this->rekapHeaderMatrix($payload['layout']);

        $filename = $this->exportFilenamePrefix() . 'rekap_pelaporan_' . date('YmdHis') . '.csv';
        $responseHeaders = [
            'Content-Type' => 'text/csv',
            "Content-Disposition" => "attachment; filename=$filename",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function () use ($payload, $headers, $statusDefs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['']);
            fputcsv($file, [$payload['title']]);
            fputcsv($file, ['']);
            fputcsv($file, $headers[0]);
            fputcsv($file, $headers[1]);
            fputcsv($file, $headers[2]);

            $no = 1;
            foreach ($payload['rows'] as $row) {
                fputcsv($file, $this->mapRekapDataRow($row, $no++, $statusDefs));
            }
            fputcsv($file, $this->mapRekapDataRow([
                'wilayah' => 'JUMLAH',
                'potensi' => $payload['totals']['potensi'],
                'belum' => $payload['totals']['belum'],
                'sudah' => $payload['totals']['sudah'],
                'statuses' => $payload['totals']['statuses'],
            ], 0, $statusDefs));

            fclose($file);
        };

        return response()->stream($callback, 200, $responseHeaders);
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
        $payload = $this->buildRekapExportPayload($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $this->writeRekapExcelSheet($sheet, $payload);

        $filename = $this->exportFilenamePrefix() . 'rekap_pelaporan_' . date('YmdHis') . '.xlsx';

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
        $payload = $this->buildRekapExportPayload($request);
        $html = $this->buildRekapPdfHtml($payload);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $fileName = $this->exportFilenamePrefix() . 'rekap_pelaporan_' . date('YmdHis') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"$fileName\"");
    }

}
