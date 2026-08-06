<?php

namespace App\Http\Controllers;

use App\Models\DataTertagih;
use App\Models\SengPendataanKendaraan;
use App\Models\SengSaamsat;
use App\Models\SengStatusVerifikasi;
use App\Models\SengWilayah;
use App\Models\SengWilayahKec;
use App\Models\SengWilayahKel;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Halaman uji filter wilayah untuk rekap visual.
 * Kode ini sengaja berdiri sendiri (tidak mewarisi / memanggil RekapVisualController).
 */
class RekapVisualFilterController extends Controller
{
    protected function pendataanTable(): string
    {
        return (new SengPendataanKendaraan())->getTable();
    }

    protected function tertagihTable(): string
    {
        return (new DataTertagih())->getTable();
    }

    protected function viewName(): string
    {
        return 'backend.rekap-visual-filter.index';
    }

    protected function routeIndex(): string
    {
        return 'rekap-visual-filter.index';
    }

    protected function routeStats(): string
    {
        return 'rekap-visual-filter.stats';
    }

    protected function routeBreakdown(): string
    {
        return 'rekap-visual-filter.breakdown';
    }

    protected function routeOptions(): string
    {
        return 'rekap-visual-filter.options';
    }

    protected function routeSibling(): string
    {
        return 'rekap-visual-filter-d2d.index';
    }

    protected function pageTitle(): string
    {
        return 'REKAP VISUAL FILTER REGULER';
    }

    protected function channelLabel(): string
    {
        return 'Reguler';
    }

    protected function isD2d(): bool
    {
        return false;
    }

    protected function cacheNamespace(): string
    {
        return 'rvf:standalone:v1:reguler:';
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();

        $year = $this->resolveYear($request);
        $kabkotas = SengWilayah::query()
            ->where('id_up', 33)
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return view($this->viewName(), [
            'year' => $year,
            'pageTitle' => $this->pageTitle(),
            'channelLabel' => $this->channelLabel(),
            'isD2d' => $this->isD2d(),
            'routeIndex' => $this->routeIndex(),
            'routeSibling' => $this->routeSibling(),
            'statsUrl' => route($this->routeStats()),
            'breakdownUrl' => route($this->routeBreakdown()),
            'optionsUrl' => route($this->routeOptions()),
            'kabkotas' => $kabkotas,
            'refreshedAt' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    public function options(Request $request)
    {
        $this->authorizeAccess();

        $level = strtolower(trim((string) $request->input('level', '')));
        $kabkotaId = trim((string) $request->input('kabkota_id', ''));
        $kecamatanId = trim((string) $request->input('kecamatan_id', ''));

        if ($level === 'kecamatan') {
            if ($kabkotaId === '') {
                return response()->json(['success' => false, 'message' => 'kabkota_id wajib.'], 422);
            }

            $lokasiIds = SengSaamsat::lokasiFilterVariantsByKabkota($kabkotaId);
            if ($lokasiIds === []) {
                return response()->json(['success' => true, 'items' => []]);
            }

            $items = SengWilayahKec::query()
                ->whereIn('id_lokasi_samsat', $lokasiIds)
                ->orderBy('kecamatan')
                ->get(['id_kecamatan', 'kecamatan'])
                ->unique('id_kecamatan')
                ->values()
                ->map(fn ($row) => [
                    'id' => (string) $row->id_kecamatan,
                    'nama' => (string) $row->kecamatan,
                ]);

            return response()->json(['success' => true, 'items' => $items]);
        }

        if ($level === 'kelurahan') {
            if ($kecamatanId === '') {
                return response()->json(['success' => false, 'message' => 'kecamatan_id wajib.'], 422);
            }

            $items = SengWilayahKel::query()
                ->whereIn('id_kecamatan', $this->codeVariants($kecamatanId))
                ->orderBy('kelurahan')
                ->get(['id_kelurahan', 'kelurahan'])
                ->unique('id_kelurahan')
                ->values()
                ->map(fn ($row) => [
                    'id' => (string) $row->id_kelurahan,
                    'nama' => (string) $row->kelurahan,
                ]);

            return response()->json(['success' => true, 'items' => $items]);
        }

        return response()->json(['success' => false, 'message' => 'level tidak dikenal.'], 422);
    }

    public function stats(Request $request)
    {
        $this->authorizeAccess();
        @set_time_limit(300);

        $filters = $this->resolveFilters($request);
        $cacheKey = $this->cacheNamespace() . 'stats:' . $this->filterCacheSuffix($filters);

        $payload = Cache::remember($cacheKey, 1800, function () use ($filters) {
            return $this->computeStats($filters);
        });

        return response()->json([
            'year' => $filters['year'],
            'filters' => [
                'kabkota_id' => $filters['kabkota_id'],
                'kecamatan_id' => $filters['kecamatan_id'],
                'kelurahan_id' => $filters['kelurahan_id'],
            ],
            'stats' => $payload['stats'],
            'bayar' => $payload['bayar'],
            'refreshedAt' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    public function breakdown(Request $request)
    {
        $this->authorizeAccess();
        @set_time_limit(300);

        $filters = $this->resolveFilters($request);
        $cacheKey = $this->cacheNamespace() . 'breakdown:' . $this->filterCacheSuffix($filters);

        $payload = Cache::remember($cacheKey, 1800, function () use ($filters) {
            return $this->computeBreakdown($filters);
        });

        return response()->json([
            'year' => $filters['year'],
            'filters' => [
                'kabkota_id' => $filters['kabkota_id'],
                'kecamatan_id' => $filters['kecamatan_id'],
                'kelurahan_id' => $filters['kelurahan_id'],
            ],
            'level' => $payload['level'],
            'rows' => $payload['rows'],
            'refreshedAt' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['super-admin', 'superadmin', 'admin', 'adminprov', 'uptd', 'uppd'], 'web'),
            403,
            'Akses terbatas.'
        );
    }

    protected function resolveYear(Request $request): int
    {
        return $request->filled('year') ? (int) $request->year : (int) date('Y');
    }

    /**
     * @return array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string}
     */
    protected function resolveFilters(Request $request): array
    {
        $kabkotaId = trim((string) $request->input('kabkota_id', ''));
        $kecamatanId = trim((string) $request->input('kecamatan_id', ''));
        $kelurahanId = trim((string) $request->input('kelurahan_id', ''));

        if ($kabkotaId === '') {
            $kecamatanId = '';
            $kelurahanId = '';
        } elseif ($kecamatanId === '') {
            $kelurahanId = '';
        }

        return [
            'year' => $this->resolveYear($request),
            'kabkota_id' => $kabkotaId,
            'kecamatan_id' => $kecamatanId,
            'kelurahan_id' => $kelurahanId,
        ];
    }

    protected function filterCacheSuffix(array $filters): string
    {
        return implode(':', [
            'y' . $filters['year'],
            'kab' . ($filters['kabkota_id'] !== '' ? $filters['kabkota_id'] : 'all'),
            'kec' . ($filters['kecamatan_id'] !== '' ? $filters['kecamatan_id'] : 'all'),
            'kel' . ($filters['kelurahan_id'] !== '' ? $filters['kelurahan_id'] : 'all'),
        ]);
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return array{stats: array<string,mixed>, bayar: array<string,mixed>}
     */
    protected function computeStats(array $filters): array
    {
        $year = $filters['year'];
        $tertagihTable = $this->tertagihTable();
        $pendataanTable = $this->pendataanTable();
        $yearStart = sprintf('%04d-01-01 00:00:00', $year);
        $yearEnd = sprintf('%04d-12-31 23:59:59', $year);
        $statusGroups = $this->resolveStatusGroups();

        $tertagihQ = DB::table($tertagihTable)->where('year', $year);
        $this->applyTertagihWilayahFilter($tertagihQ, $filters);
        $tertagihAgg = $tertagihQ
            ->selectRaw('COUNT(*) as jumlah_tunggakan')
            ->selectRaw('SUM(CASE WHEN is_terdata = 1 THEN 1 ELSE 0 END) as jumlah_sudah_pendataan')
            ->selectRaw('SUM(CASE WHEN is_terdata = 0 THEN 1 ELSE 0 END) as jumlah_belum_pendataan')
            ->first();

        $menunggu = implode(',', array_map('intval', $statusGroups['menunggu']));
        $verifikasi = implode(',', array_map('intval', $statusGroups['verifikasi']));
        $ditolak = implode(',', array_map('intval', $statusGroups['ditolak']));

        $pendataanQ = DB::table($pendataanTable)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$yearStart, $yearEnd]);
        $this->applyPendataanWilayahFilter($pendataanQ, $filters);
        $pendataanAgg = $pendataanQ
            ->selectRaw("SUM(CASE WHEN status_verifikasi IN ({$menunggu}) THEN 1 ELSE 0 END) as menunggu_verifikasi")
            ->selectRaw("SUM(CASE WHEN status_verifikasi IN ({$verifikasi}) THEN 1 ELSE 0 END) as verifikasi")
            ->selectRaw("SUM(CASE WHEN status_verifikasi IN ({$ditolak}) THEN 1 ELSE 0 END) as ditolak")
            ->first();

        $stats = [
            'jumlah_tunggakan' => (int) ($tertagihAgg->jumlah_tunggakan ?? 0),
            'jumlah_sudah_pendataan' => (int) ($tertagihAgg->jumlah_sudah_pendataan ?? 0),
            'jumlah_belum_pendataan' => (int) ($tertagihAgg->jumlah_belum_pendataan ?? 0),
            'menunggu_verifikasi' => (int) ($pendataanAgg->menunggu_verifikasi ?? 0),
            'verifikasi' => (int) ($pendataanAgg->verifikasi ?? 0),
            'ditolak' => (int) ($pendataanAgg->ditolak ?? 0),
        ];
        $stats['pct_dikunjungi'] = $stats['jumlah_tunggakan'] > 0
            ? round(($stats['jumlah_sudah_pendataan'] / $stats['jumlah_tunggakan']) * 100, 2)
            : 0.0;

        $bayar = $this->computeBayarStats($filters, $tertagihTable, $pendataanTable, $yearStart, $yearEnd);
        $bayar = array_merge(
            $bayar,
            $this->computePotensiNominal($filters, $pendataanTable, $yearStart, $yearEnd, $stats['jumlah_tunggakan'])
        );

        $totalBayar = (int) ($bayar['nominal_total'] ?? 0);
        $totalPotensi = (int) ($bayar['potensi_total'] ?? 0);
        $bayar['pct_bayar_vs_potensi'] = $totalPotensi > 0
            ? round(($totalBayar / $totalPotensi) * 100, 2)
            : 0.0;

        return compact('stats', 'bayar');
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return array{level:string,rows:list<array<string,mixed>>}
     */
    protected function computeBreakdown(array $filters): array
    {
        $year = $filters['year'];
        $tertagihTable = $this->tertagihTable();
        $pendataanTable = $this->pendataanTable();
        $yearStart = sprintf('%04d-01-01 00:00:00', $year);
        $yearEnd = sprintf('%04d-12-31 23:59:59', $year);

        if ($filters['kelurahan_id'] !== '') {
            return [
                'level' => 'kelurahan',
                'rows' => $this->breakdownSingleScope($filters, $tertagihTable, $pendataanTable, $yearStart, $yearEnd),
            ];
        }

        if ($filters['kecamatan_id'] !== '') {
            return [
                'level' => 'kelurahan',
                'rows' => $this->breakdownByKelurahan($filters, $tertagihTable, $pendataanTable, $yearStart, $yearEnd),
            ];
        }

        if ($filters['kabkota_id'] !== '') {
            return [
                'level' => 'kecamatan',
                'rows' => $this->breakdownByKecamatan($filters, $tertagihTable, $pendataanTable, $yearStart, $yearEnd),
            ];
        }

        return [
            'level' => 'kabkota',
            'rows' => $this->breakdownByKabkota($filters, $tertagihTable, $pendataanTable, $yearStart, $yearEnd),
        ];
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     */
    protected function applyTertagihWilayahFilter(Builder $query, array $filters, string $alias = ''): void
    {
        $col = static function (string $name) use ($alias): string {
            return $alias !== '' ? "{$alias}.{$name}" : $name;
        };

        if ($filters['kabkota_id'] !== '') {
            $lokasiIds = SengSaamsat::lokasiFilterVariantsByKabkota($filters['kabkota_id']);
            if ($lokasiIds === []) {
                $query->whereRaw('1 = 0');

                return;
            }
            $query->whereIn($col('id_lokasi_samsat'), $lokasiIds);
        }

        if ($filters['kecamatan_id'] !== '') {
            $query->whereIn($col('id_kecamatan'), $this->codeVariants($filters['kecamatan_id']));
        }

        if ($filters['kelurahan_id'] !== '') {
            $query->whereIn($col('id_kelurahan'), $this->codeVariants($filters['kelurahan_id']));
        }
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @param Builder|\Illuminate\Database\Eloquent\Builder $query
     */
    protected function applyPendataanWilayahFilter($query, array $filters): void
    {
        if ($filters['kabkota_id'] !== '') {
            $query->where('kota_dagri', $filters['kabkota_id']);
        }

        if ($filters['kecamatan_id'] !== '') {
            $kecVariants = $this->codeVariants($filters['kecamatan_id']);
            $kecDagri = DB::table('wilayah_samsat_kec')
                ->whereIn('id_kecamatan', $kecVariants)
                ->value('kode_dagri');
            $dagriVariants = $kecDagri ? $this->codeVariants((string) $kecDagri) : [];

            $query->where(function ($q) use ($kecVariants, $dagriVariants) {
                $q->whereIn('kec', $kecVariants);
                if ($dagriVariants !== []) {
                    $q->orWhereIn('kec_dagri', $dagriVariants);
                }
            });
        }

        if ($filters['kelurahan_id'] !== '') {
            $query->whereIn('desa', $this->codeVariants($filters['kelurahan_id']));
        }
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return array<string, mixed>
     */
    protected function computeBayarStats(
        array $filters,
        string $tertagihTable,
        string $pendataanTable,
        string $yearStart,
        string $yearEnd
    ): array {
        $year = $filters['year'];

        $rows = DB::table('seng_bayar_pajak as b')
            ->where('b.year', $year)
            ->whereNotNull('b.nopol_')
            ->where('b.nopol_', '!=', '')
            ->whereExists(function ($q) use ($tertagihTable, $year, $filters) {
                $q->select(DB::raw(1))
                    ->from("{$tertagihTable} as t")
                    ->whereColumn('t.no_polisi', 'b.nopol_')
                    ->where('t.year', $year);
                $this->applyTertagihWilayahFilter($q, $filters, 't');
                $q->limit(1);
            })
            ->get([
                'b.nopol_',
                'b.tgl_bayar',
                'b.pkb_provinsi_jalan',
                'b.pkb_provinsi_tunggakan',
                'b.pkb_opsen_jalan',
                'b.pkb_opsen_tunggakan',
            ]);

        $pendataanQ = DB::table($pendataanTable)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->whereNotNull('nopol')
            ->where('nopol', '!=', '');
        $this->applyPendataanWilayahFilter($pendataanQ, $filters);
        $pendataanMap = $pendataanQ
            ->groupBy('nopol')
            ->selectRaw('nopol, MIN(DATE(created_at)) as tgl_pendataan')
            ->pluck('tgl_pendataan', 'nopol');

        $nopolUnik = [];
        $nopolBayarSebelum = [];
        $nopolBayarSesudah = [];
        $nopolTanpaPendataan = [];
        $nominalProvinsi = 0;
        $nominalOpsen = 0;
        $sebelumProv = 0;
        $sebelumOps = 0;
        $sesudahProv = 0;
        $sesudahOps = 0;
        $tanpaProv = 0;
        $tanpaOps = 0;

        foreach ($rows as $row) {
            $nopol = (string) $row->nopol_;
            $nopolUnik[$nopol] = true;

            $prov = (int) ($row->pkb_provinsi_jalan ?? 0) + (int) ($row->pkb_provinsi_tunggakan ?? 0);
            $ops = (int) ($row->pkb_opsen_jalan ?? 0) + (int) ($row->pkb_opsen_tunggakan ?? 0);
            $nominalProvinsi += $prov;
            $nominalOpsen += $ops;

            $tglPendataan = $pendataanMap[$nopol] ?? null;
            $tglBayar = $row->tgl_bayar ? substr((string) $row->tgl_bayar, 0, 10) : null;

            if ($tglPendataan === null || $tglPendataan === '') {
                $nopolTanpaPendataan[$nopol] = true;
                $tanpaProv += $prov;
                $tanpaOps += $ops;
                continue;
            }

            if ($tglBayar !== null && $tglBayar < $tglPendataan) {
                $nopolBayarSebelum[$nopol] = true;
                $sebelumProv += $prov;
                $sebelumOps += $ops;
            } else {
                $nopolBayarSesudah[$nopol] = true;
                $sesudahProv += $prov;
                $sesudahOps += $ops;
            }
        }

        $jumlahTerbayar = count($nopolUnik);
        $sesudah = count($nopolBayarSesudah);
        $sebelumMurni = count(array_diff_key($nopolBayarSebelum, $nopolBayarSesudah));
        $tanpa = count(array_diff_key($nopolTanpaPendataan, $nopolBayarSesudah));
        $sebelumTotal = $sebelumMurni + $tanpa;
        $sebelumProvTotal = $sebelumProv + $tanpaProv;
        $sebelumOpsTotal = $sebelumOps + $tanpaOps;

        return [
            'jumlah_terbayar' => $jumlahTerbayar,
            'nominal_provinsi' => $nominalProvinsi,
            'nominal_opsen' => $nominalOpsen,
            'nominal_total' => $nominalProvinsi + $nominalOpsen,
            'nominal_provinsi_fmt' => $this->formatMoney($nominalProvinsi),
            'nominal_opsen_fmt' => $this->formatMoney($nominalOpsen),
            'nominal_total_fmt' => $this->formatMoney($nominalProvinsi + $nominalOpsen),
            'sebelum_pendataan' => $sebelumTotal,
            'sesudah_pendataan' => $sesudah,
            'sebelum_pendataan_provinsi_fmt' => $this->formatMoney($sebelumProvTotal),
            'sebelum_pendataan_opsen_fmt' => $this->formatMoney($sebelumOpsTotal),
            'sesudah_pendataan_provinsi_fmt' => $this->formatMoney($sesudahProv),
            'sesudah_pendataan_opsen_fmt' => $this->formatMoney($sesudahOps),
        ];
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return array<string, mixed>
     */
    protected function computePotensiNominal(
        array $filters,
        string $pendataanTable,
        string $yearStart,
        string $yearEnd,
        int $jumlahTunggakan
    ): array {
        $q = DB::table($pendataanTable)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$yearStart, $yearEnd]);
        $this->applyPendataanWilayahFilter($q, $filters);
        $agg = $q
            ->selectRaw('COUNT(*) as c')
            ->selectRaw('SUM(COALESCE(pkb_pokok, 0)) as provinsi')
            ->selectRaw('SUM(COALESCE(pkb_pokok_opsen, 0)) as opsen')
            ->first();

        $sampleCount = (int) ($agg->c ?? 0);
        $sampleProv = (float) ($agg->provinsi ?? 0);
        $sampleOpsen = (float) ($agg->opsen ?? 0);

        if ($sampleCount > 0 && $jumlahTunggakan > 0) {
            $potensiProvinsi = (int) round(($sampleProv / $sampleCount) * $jumlahTunggakan);
            $potensiOpsen = (int) round(($sampleOpsen / $sampleCount) * $jumlahTunggakan);
        } else {
            $potensiProvinsi = (int) round($sampleProv);
            $potensiOpsen = (int) round($sampleOpsen);
        }

        $potensiTotal = $potensiProvinsi + $potensiOpsen;

        return [
            'potensi_total' => $potensiTotal,
            'potensi_total_fmt' => $this->formatMoney($potensiTotal),
        ];
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return list<array<string,mixed>>
     */
    protected function breakdownByKabkota(
        array $filters,
        string $tertagihTable,
        string $pendataanTable,
        string $yearStart,
        string $yearEnd
    ): array {
        $year = $filters['year'];
        $kabkotas = SengWilayah::query()->where('id_up', 33)->get(['id', 'nama']);

        $lokasiToKabkota = [];
        foreach (SengSaamsat::query()->get(['id', 'id_wilayah_samsat', 'kabkota']) as $samsat) {
            $kabId = (string) $samsat->kabkota;
            if ($kabId === '') {
                continue;
            }
            foreach ([(string) ($samsat->id ?? ''), (string) ($samsat->id_wilayah_samsat ?? '')] as $seed) {
                if ($seed === '') {
                    continue;
                }
                foreach ($this->codeVariants($seed) as $variant) {
                    $lokasiToKabkota[$variant] = $kabId;
                }
            }
        }

        $tagihanByLokasi = DB::table($tertagihTable)
            ->where('year', $year)
            ->selectRaw('id_lokasi_samsat, COUNT(*) as c')
            ->selectRaw('SUM(CASE WHEN is_terdata = 1 THEN 1 ELSE 0 END) as pendataan')
            ->groupBy('id_lokasi_samsat')
            ->get()
            ->keyBy(fn ($row) => (string) $row->id_lokasi_samsat);

        $bayarByLokasi = $this->bayarCountByLokasi($filters, $tertagihTable);
        $bayarSesudahByLokasi = $this->bayarSesudahCountByLokasi($filters, $tertagihTable, $pendataanTable, $yearStart, $yearEnd);

        $tagihanByKab = [];
        $pendataanByKab = [];
        $bayarByKab = [];
        $bayarSesudahByKab = [];

        foreach ($tagihanByLokasi as $lokasi => $row) {
            $kabId = $lokasiToKabkota[(string) $lokasi] ?? null;
            if ($kabId === null) {
                continue;
            }
            $tagihanByKab[$kabId] = ($tagihanByKab[$kabId] ?? 0) + (int) $row->c;
            $pendataanByKab[$kabId] = ($pendataanByKab[$kabId] ?? 0) + (int) $row->pendataan;
        }
        foreach ($bayarByLokasi as $lokasi => $count) {
            $kabId = $lokasiToKabkota[(string) $lokasi] ?? null;
            if ($kabId === null) {
                continue;
            }
            $bayarByKab[$kabId] = ($bayarByKab[$kabId] ?? 0) + (int) $count;
        }
        foreach ($bayarSesudahByLokasi as $lokasi => $count) {
            $kabId = $lokasiToKabkota[(string) $lokasi] ?? null;
            if ($kabId === null) {
                continue;
            }
            $bayarSesudahByKab[$kabId] = ($bayarSesudahByKab[$kabId] ?? 0) + (int) $count;
        }

        $rows = [];
        foreach ($kabkotas as $kab) {
            $kabId = (string) $kab->id;
            $tagihan = $tagihanByKab[$kabId] ?? 0;
            $pendataan = min($tagihan, $pendataanByKab[$kabId] ?? 0);
            $bayar = min($tagihan, $bayarByKab[$kabId] ?? 0);
            $bayarSesudah = min($pendataan, $bayarSesudahByKab[$kabId] ?? 0);
            $rows[] = $this->makeBreakdownRow($kabId, (string) $kab->nama, $tagihan, $pendataan, $bayar, $bayarSesudah);
        }

        usort($rows, static fn ($a, $b) => $b['bayar_pct'] <=> $a['bayar_pct']);

        return $rows;
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return list<array<string,mixed>>
     */
    protected function breakdownByKecamatan(
        array $filters,
        string $tertagihTable,
        string $pendataanTable,
        string $yearStart,
        string $yearEnd
    ): array {
        $year = $filters['year'];
        $q = DB::table($tertagihTable)->where('year', $year);
        $this->applyTertagihWilayahFilter($q, $filters);
        $tagihanRows = $q
            ->selectRaw("COALESCE(NULLIF(id_kecamatan, ''), '-') as wilayah_id")
            ->selectRaw('MAX(nm_kecamatan) as wilayah_nama')
            ->selectRaw('COUNT(*) as tagihan')
            ->selectRaw('SUM(CASE WHEN is_terdata = 1 THEN 1 ELSE 0 END) as pendataan')
            ->groupBy('wilayah_id')
            ->get();

        $bayarMap = $this->bayarCountByWilayahColumn($filters, $tertagihTable, 'id_kecamatan');
        $bayarSesudahMap = $this->bayarSesudahCountByWilayahColumn(
            $filters,
            $tertagihTable,
            $pendataanTable,
            $yearStart,
            $yearEnd,
            'id_kecamatan'
        );

        $namaMaster = SengWilayahKec::query()
            ->get(['id_kecamatan', 'kecamatan'])
            ->mapWithKeys(fn ($r) => [(string) $r->id_kecamatan => (string) $r->kecamatan]);

        $rows = [];
        foreach ($tagihanRows as $row) {
            $id = (string) $row->wilayah_id;
            $tagihan = (int) $row->tagihan;
            $pendataan = min($tagihan, (int) $row->pendataan);
            $bayar = min($tagihan, (int) ($bayarMap[$id] ?? 0));
            $bayarSesudah = min($pendataan, (int) ($bayarSesudahMap[$id] ?? 0));
            $nama = trim((string) ($row->wilayah_nama ?: '')) ?: ($namaMaster[$id] ?? $id);
            $rows[] = $this->makeBreakdownRow($id, $nama, $tagihan, $pendataan, $bayar, $bayarSesudah);
        }

        usort($rows, static fn ($a, $b) => $b['bayar_pct'] <=> $a['bayar_pct']);

        return $rows;
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return list<array<string,mixed>>
     */
    protected function breakdownByKelurahan(
        array $filters,
        string $tertagihTable,
        string $pendataanTable,
        string $yearStart,
        string $yearEnd
    ): array {
        $year = $filters['year'];
        $q = DB::table($tertagihTable)->where('year', $year);
        $this->applyTertagihWilayahFilter($q, $filters);
        $tagihanRows = $q
            ->selectRaw("COALESCE(NULLIF(id_kelurahan, ''), '-') as wilayah_id")
            ->selectRaw('MAX(nm_kelurahan) as wilayah_nama')
            ->selectRaw('COUNT(*) as tagihan')
            ->selectRaw('SUM(CASE WHEN is_terdata = 1 THEN 1 ELSE 0 END) as pendataan')
            ->groupBy('wilayah_id')
            ->get();

        $bayarMap = $this->bayarCountByWilayahColumn($filters, $tertagihTable, 'id_kelurahan');
        $bayarSesudahMap = $this->bayarSesudahCountByWilayahColumn(
            $filters,
            $tertagihTable,
            $pendataanTable,
            $yearStart,
            $yearEnd,
            'id_kelurahan'
        );

        $namaMaster = SengWilayahKel::query()
            ->whereIn('id_kecamatan', $this->codeVariants($filters['kecamatan_id']))
            ->get(['id_kelurahan', 'kelurahan'])
            ->mapWithKeys(fn ($r) => [(string) $r->id_kelurahan => (string) $r->kelurahan]);

        $rows = [];
        foreach ($tagihanRows as $row) {
            $id = (string) $row->wilayah_id;
            $tagihan = (int) $row->tagihan;
            $pendataan = min($tagihan, (int) $row->pendataan);
            $bayar = min($tagihan, (int) ($bayarMap[$id] ?? 0));
            $bayarSesudah = min($pendataan, (int) ($bayarSesudahMap[$id] ?? 0));
            $nama = trim((string) ($row->wilayah_nama ?: '')) ?: ($namaMaster[$id] ?? $id);
            $rows[] = $this->makeBreakdownRow($id, $nama, $tagihan, $pendataan, $bayar, $bayarSesudah);
        }

        usort($rows, static fn ($a, $b) => $b['bayar_pct'] <=> $a['bayar_pct']);

        return $rows;
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return list<array<string,mixed>>
     */
    protected function breakdownSingleScope(
        array $filters,
        string $tertagihTable,
        string $pendataanTable,
        string $yearStart,
        string $yearEnd
    ): array {
        $year = $filters['year'];
        $q = DB::table($tertagihTable)->where('year', $year);
        $this->applyTertagihWilayahFilter($q, $filters);
        $agg = $q
            ->selectRaw('COUNT(*) as tagihan')
            ->selectRaw('SUM(CASE WHEN is_terdata = 1 THEN 1 ELSE 0 END) as pendataan')
            ->first();

        $tagihan = (int) ($agg->tagihan ?? 0);
        $pendataan = min($tagihan, (int) ($agg->pendataan ?? 0));

        $bayar = DB::table('seng_bayar_pajak as b')
            ->where('b.year', $year)
            ->whereNotNull('b.nopol_')
            ->where('b.nopol_', '!=', '')
            ->whereExists(function ($sub) use ($tertagihTable, $year, $filters) {
                $sub->select(DB::raw(1))
                    ->from("{$tertagihTable} as t")
                    ->whereColumn('t.no_polisi', 'b.nopol_')
                    ->where('t.year', $year);
                $this->applyTertagihWilayahFilter($sub, $filters, 't');
                $sub->limit(1);
            })
            ->distinct()
            ->count('b.nopol_');

        $bayarSesudah = DB::table('seng_bayar_pajak as b')
            ->where('b.year', $year)
            ->whereNotNull('b.nopol_')
            ->where('b.nopol_', '!=', '')
            ->whereNotNull('b.tgl_bayar')
            ->whereExists(function ($sub) use ($tertagihTable, $year, $filters) {
                $sub->select(DB::raw(1))
                    ->from("{$tertagihTable} as t")
                    ->whereColumn('t.no_polisi', 'b.nopol_')
                    ->where('t.year', $year);
                $this->applyTertagihWilayahFilter($sub, $filters, 't');
                $sub->limit(1);
            })
            ->whereExists(function ($sub) use ($pendataanTable, $yearStart, $yearEnd, $filters) {
                $sub->select(DB::raw(1))
                    ->from("{$pendataanTable} as p")
                    ->whereColumn('p.nopol', 'b.nopol_')
                    ->whereNull('p.deleted_at')
                    ->whereBetween('p.created_at', [$yearStart, $yearEnd])
                    ->whereRaw('DATE(b.tgl_bayar) >= DATE(p.created_at)');
                $this->applyPendataanWilayahFilter($sub, $filters);
                $sub->limit(1);
            })
            ->distinct()
            ->count('b.nopol_');

        $nama = SengWilayahKel::query()
            ->whereIn('id_kelurahan', $this->codeVariants($filters['kelurahan_id']))
            ->value('kelurahan') ?: $filters['kelurahan_id'];

        return [
            $this->makeBreakdownRow(
                $filters['kelurahan_id'],
                (string) $nama,
                $tagihan,
                $pendataan,
                min($tagihan, (int) $bayar),
                min($pendataan, (int) $bayarSesudah)
            ),
        ];
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return \Illuminate\Support\Collection<string,int>
     */
    protected function bayarCountByLokasi(array $filters, string $tertagihTable)
    {
        $year = (int) $filters['year'];
        $lokasiSql = $this->tertagihLokasiSqlFragment($filters, 't');

        return DB::table(DB::raw("(
            SELECT x.nopol_, MIN(t.id_lokasi_samsat) AS id_lokasi_samsat
            FROM (
                SELECT DISTINCT b.nopol_
                FROM seng_bayar_pajak b
                WHERE b.year = {$year}
                  AND b.nopol_ IS NOT NULL
                  AND b.nopol_ != ''
            ) x
            INNER JOIN {$tertagihTable} t
                ON t.no_polisi = x.nopol_
               AND t.year = {$year}
               {$lokasiSql}
            GROUP BY x.nopol_
        ) as paid"))
            ->selectRaw('id_lokasi_samsat, COUNT(*) as c')
            ->groupBy('id_lokasi_samsat')
            ->pluck('c', 'id_lokasi_samsat');
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return \Illuminate\Support\Collection<string,int>
     */
    protected function bayarSesudahCountByLokasi(
        array $filters,
        string $tertagihTable,
        string $pendataanTable,
        string $yearStart,
        string $yearEnd
    ) {
        $year = (int) $filters['year'];
        $lokasiSql = $this->tertagihLokasiSqlFragment($filters, 't');
        $pendataanWhere = $this->pendataanWilayahSqlFragment($filters, 'p');

        return DB::table(DB::raw("(
            SELECT x.nopol_, MIN(t.id_lokasi_samsat) AS id_lokasi_samsat
            FROM (
                SELECT DISTINCT b.nopol_
                FROM seng_bayar_pajak b
                INNER JOIN (
                    SELECT nopol, MIN(DATE(created_at)) AS tgl_pendataan
                    FROM {$pendataanTable} p
                    WHERE deleted_at IS NULL
                      AND created_at BETWEEN '{$yearStart}' AND '{$yearEnd}'
                      AND nopol IS NOT NULL
                      AND nopol != ''
                      {$pendataanWhere}
                    GROUP BY nopol
                ) p ON p.nopol = b.nopol_
                WHERE b.year = {$year}
                  AND b.nopol_ IS NOT NULL
                  AND b.nopol_ != ''
                  AND b.tgl_bayar IS NOT NULL
                  AND DATE(b.tgl_bayar) >= p.tgl_pendataan
            ) x
            INNER JOIN {$tertagihTable} t
                ON t.no_polisi = x.nopol_
               AND t.year = {$year}
               {$lokasiSql}
            GROUP BY x.nopol_
        ) as paid_sesudah"))
            ->selectRaw('id_lokasi_samsat, COUNT(*) as c')
            ->groupBy('id_lokasi_samsat')
            ->pluck('c', 'id_lokasi_samsat');
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return array<string,int>
     */
    protected function bayarCountByWilayahColumn(array $filters, string $tertagihTable, string $column): array
    {
        $year = (int) $filters['year'];
        $lokasiSql = $this->tertagihLokasiSqlFragment($filters, 't');
        $safeCol = in_array($column, ['id_kecamatan', 'id_kelurahan'], true) ? $column : 'id_kecamatan';

        $rows = DB::table(DB::raw("(
            SELECT x.nopol_, MIN(t.{$safeCol}) AS wilayah_id
            FROM (
                SELECT DISTINCT b.nopol_
                FROM seng_bayar_pajak b
                WHERE b.year = {$year}
                  AND b.nopol_ IS NOT NULL
                  AND b.nopol_ != ''
            ) x
            INNER JOIN {$tertagihTable} t
                ON t.no_polisi = x.nopol_
               AND t.year = {$year}
               {$lokasiSql}
            GROUP BY x.nopol_
        ) as paid"))
            ->selectRaw("COALESCE(NULLIF(wilayah_id, ''), '-') as wilayah_id, COUNT(*) as c")
            ->groupBy('wilayah_id')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->wilayah_id] = (int) $row->c;
        }

        return $out;
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     * @return array<string,int>
     */
    protected function bayarSesudahCountByWilayahColumn(
        array $filters,
        string $tertagihTable,
        string $pendataanTable,
        string $yearStart,
        string $yearEnd,
        string $column
    ): array {
        $year = (int) $filters['year'];
        $lokasiSql = $this->tertagihLokasiSqlFragment($filters, 't');
        $pendataanWhere = $this->pendataanWilayahSqlFragment($filters, 'p');
        $safeCol = in_array($column, ['id_kecamatan', 'id_kelurahan'], true) ? $column : 'id_kecamatan';

        $rows = DB::table(DB::raw("(
            SELECT x.nopol_, MIN(t.{$safeCol}) AS wilayah_id
            FROM (
                SELECT DISTINCT b.nopol_
                FROM seng_bayar_pajak b
                INNER JOIN (
                    SELECT nopol, MIN(DATE(created_at)) AS tgl_pendataan
                    FROM {$pendataanTable} p
                    WHERE deleted_at IS NULL
                      AND created_at BETWEEN '{$yearStart}' AND '{$yearEnd}'
                      AND nopol IS NOT NULL
                      AND nopol != ''
                      {$pendataanWhere}
                    GROUP BY nopol
                ) pend ON pend.nopol = b.nopol_
                WHERE b.year = {$year}
                  AND b.nopol_ IS NOT NULL
                  AND b.nopol_ != ''
                  AND b.tgl_bayar IS NOT NULL
                  AND DATE(b.tgl_bayar) >= pend.tgl_pendataan
            ) x
            INNER JOIN {$tertagihTable} t
                ON t.no_polisi = x.nopol_
               AND t.year = {$year}
               {$lokasiSql}
            GROUP BY x.nopol_
        ) as paid_sesudah"))
            ->selectRaw("COALESCE(NULLIF(wilayah_id, ''), '-') as wilayah_id, COUNT(*) as c")
            ->groupBy('wilayah_id')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->wilayah_id] = (int) $row->c;
        }

        return $out;
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     */
    protected function tertagihLokasiSqlFragment(array $filters, string $alias): string
    {
        $parts = [];

        if ($filters['kabkota_id'] !== '') {
            $lokasiIds = SengSaamsat::lokasiFilterVariantsByKabkota($filters['kabkota_id']);
            if ($lokasiIds === []) {
                return ' AND 1 = 0';
            }
            $in = implode(',', array_map(static fn ($v) => "'" . str_replace("'", "''", (string) $v) . "'", $lokasiIds));
            $parts[] = "{$alias}.id_lokasi_samsat IN ({$in})";
        }

        if ($filters['kecamatan_id'] !== '') {
            $in = implode(',', array_map(
                static fn ($v) => "'" . str_replace("'", "''", (string) $v) . "'",
                $this->codeVariants($filters['kecamatan_id'])
            ));
            $parts[] = "{$alias}.id_kecamatan IN ({$in})";
        }

        if ($filters['kelurahan_id'] !== '') {
            $in = implode(',', array_map(
                static fn ($v) => "'" . str_replace("'", "''", (string) $v) . "'",
                $this->codeVariants($filters['kelurahan_id'])
            ));
            $parts[] = "{$alias}.id_kelurahan IN ({$in})";
        }

        return $parts === [] ? '' : ' AND ' . implode(' AND ', $parts);
    }

    /**
     * @param array{year:int,kabkota_id:string,kecamatan_id:string,kelurahan_id:string} $filters
     */
    protected function pendataanWilayahSqlFragment(array $filters, string $alias): string
    {
        $parts = [];

        if ($filters['kabkota_id'] !== '') {
            $kab = str_replace("'", "''", $filters['kabkota_id']);
            $parts[] = "{$alias}.kota_dagri = '{$kab}'";
        }

        if ($filters['kecamatan_id'] !== '') {
            $kecVariants = $this->codeVariants($filters['kecamatan_id']);
            $inKec = implode(',', array_map(
                static fn ($v) => "'" . str_replace("'", "''", (string) $v) . "'",
                $kecVariants
            ));
            $kecDagri = DB::table('wilayah_samsat_kec')
                ->whereIn('id_kecamatan', $kecVariants)
                ->value('kode_dagri');
            if ($kecDagri) {
                $inDagri = implode(',', array_map(
                    static fn ($v) => "'" . str_replace("'", "''", (string) $v) . "'",
                    $this->codeVariants((string) $kecDagri)
                ));
                $parts[] = "({$alias}.kec IN ({$inKec}) OR {$alias}.kec_dagri IN ({$inDagri}))";
            } else {
                $parts[] = "{$alias}.kec IN ({$inKec})";
            }
        }

        if ($filters['kelurahan_id'] !== '') {
            $in = implode(',', array_map(
                static fn ($v) => "'" . str_replace("'", "''", (string) $v) . "'",
                $this->codeVariants($filters['kelurahan_id'])
            ));
            $parts[] = "{$alias}.desa IN ({$in})";
        }

        return $parts === [] ? '' : ' AND ' . implode(' AND ', $parts);
    }

    /**
     * @return array<string,mixed>
     */
    protected function makeBreakdownRow(
        string $id,
        string $nama,
        int $tagihan,
        int $pendataan,
        int $bayar,
        int $bayarSesudah
    ): array {
        $bayarPct = $tagihan > 0 ? round(($bayar / $tagihan) * 100, 2) : 0.0;
        $successRate = $pendataan > 0 ? round(($bayarSesudah / $pendataan) * 100, 2) : 0.0;

        return [
            'id' => $id,
            'nama' => $nama,
            'tagihan' => $tagihan,
            'pendataan' => $pendataan,
            'bayar' => $bayar,
            'bayar_sesudah' => $bayarSesudah,
            'bayar_pct' => $bayarPct,
            'success_rate' => $successRate,
        ];
    }

    /**
     * @return array{menunggu:int[],verifikasi:int[],ditolak:int[]}
     */
    protected function resolveStatusGroups(): array
    {
        $statuses = SengStatusVerifikasi::query()->get(['id', 'nama']);
        $byName = [];
        foreach ($statuses as $s) {
            $key = strtoupper(trim((string) ($s->nama ?? '')));
            if ($key !== '') {
                $byName[$key] = (int) $s->id;
            }
        }

        $pick = static function (array $names) use ($byName): array {
            $ids = [];
            foreach ($names as $name) {
                if (isset($byName[strtoupper($name)])) {
                    $ids[] = $byName[strtoupper($name)];
                }
            }

            return array_values(array_unique($ids));
        };

        $menunggu = $pick(['MENUNGGU VERIFIKASI', 'SUDAH DIPERBAIKI']) ?: [1, 5];
        $verifikasi = $pick(['DIVERIFIKASI', 'TERVERIFIKASI']) ?: [2];
        $ditolak = $pick(['DITOLAK', 'REVISI', 'PERLU REVISI']) ?: [3, 4];

        return compact('menunggu', 'verifikasi', 'ditolak');
    }

    /**
     * @return list<string>
     */
    protected function codeVariants(string $value): array
    {
        $v = trim($value);
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

    protected function formatMoney(int|float|null $amount): string
    {
        $n = (float) ($amount ?? 0);
        if (abs($n) >= 1_000_000_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000_000_000, 2, ',', ''), '0'), ',') . ' T';
        }
        if (abs($n) >= 1_000_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000_000, 2, ',', ''), '0'), ',') . ' M';
        }
        if (abs($n) >= 1_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000, 2, ',', ''), '0'), ',') . ' jt';
        }
        if (abs($n) >= 1_000) {
            return rtrim(rtrim(number_format($n / 1_000, 1, ',', ''), '0'), ',') . ' rb';
        }

        return number_format($n, 0, ',', '.');
    }
}
