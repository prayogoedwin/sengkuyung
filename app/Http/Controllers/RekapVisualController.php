<?php

namespace App\Http\Controllers;

use App\Models\DataTertagih;
use App\Models\SengPendataanKendaraan;
use App\Models\SengSaamsat;
use App\Models\SengWilayah;
use App\Support\ApiCacheManager;
use App\Support\MoneyShortFormatter;
use App\Support\VerifikasiStatusGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RekapVisualController extends Controller
{
    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraan::class;
    }

    protected function dataTertagihModelClass(): string
    {
        return DataTertagih::class;
    }

    protected function viewName(): string
    {
        return 'backend.rekap-visual.index';
    }

    protected function routeIndex(): string
    {
        return 'rekap-visual.index';
    }

    protected function routeMap(): string
    {
        return 'rekap-visual.map';
    }

    protected function routeSibling(): string
    {
        return 'rekap-visual-d2d.index';
    }

    protected function pageTitle(): string
    {
        return 'REKAP VISUAL SENGKUYUNG REGULER';
    }

    protected function channelLabel(): string
    {
        return 'Reguler';
    }

    protected function isD2d(): bool
    {
        return false;
    }

    protected function cachePrefix(): string
    {
        return 'admin:rekap-visual:v5:';
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();
        @set_time_limit(180);

        $year = $request->filled('year') ? (int) $request->year : (int) date('Y');

        // TTL data (bukan 60s dashboard) — rebuild statistik/peta mahal untuk reguler & D2D.
        $payload = ApiCacheManager::remember(
            $this->cachePrefix() . 'stats:y:' . $year,
            ApiCacheManager::dataTtl(),
            fn () => $this->buildStatsPayload($year)
        );

        return view($this->viewName(), [
            'year' => $year,
            'pageTitle' => $this->pageTitle(),
            'channelLabel' => $this->channelLabel(),
            'isD2d' => $this->isD2d(),
            'routeIndex' => $this->routeIndex(),
            'routeMap' => $this->routeMap(),
            'routeSibling' => $this->routeSibling(),
            'stats' => $payload['stats'],
            'bayar' => $payload['bayar'],
            'mapKabkota' => [],
            'mapUrl' => route($this->routeMap(), ['year' => $year]),
            'refreshedAt' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    public function map(Request $request)
    {
        $this->authorizeAccess();
        @set_time_limit(180);

        $year = $request->filled('year') ? (int) $request->year : (int) date('Y');
        $tertagihTable = (new ($this->dataTertagihModelClass()))->getTable();

        $mapKabkota = ApiCacheManager::remember(
            $this->cachePrefix() . 'map:y:' . $year,
            ApiCacheManager::dataTtl(),
            fn () => $this->buildMapKabkota($year, $tertagihTable)
        );

        return response()->json([
            'year' => $year,
            'mapKabkota' => $mapKabkota,
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

    /**
     * @return array{stats: array<string, mixed>, bayar: array<string, mixed>}
     */
    protected function buildStatsPayload(int $year): array
    {
        $statusGroups = VerifikasiStatusGroups::all();
        $tertagihTable = (new ($this->dataTertagihModelClass()))->getTable();
        $pendataanTable = (new ($this->pendataanModelClass()))->getTable();

        $tertagihBase = $this->dataTertagihModelClass()::query()->where('year', $year);
        $yearStart = sprintf('%04d-01-01 00:00:00', $year);
        $yearEnd = sprintf('%04d-12-31 23:59:59', $year);
        $pendataanBase = $this->pendataanModelClass()::query()
            ->whereBetween('created_at', [$yearStart, $yearEnd]);

        $stats = [
            'jumlah_tunggakan' => (clone $tertagihBase)->count(),
            'jumlah_sudah_pendataan' => (clone $tertagihBase)->where('is_terdata', 1)->count(),
            'jumlah_belum_pendataan' => (clone $tertagihBase)->where('is_terdata', 0)->count(),
            'menunggu_verifikasi' => (clone $pendataanBase)->whereIn('status_verifikasi', $statusGroups['menunggu'])->count(),
            'verifikasi' => (clone $pendataanBase)->whereIn('status_verifikasi', $statusGroups['verifikasi'])->count(),
            'ditolak' => (clone $pendataanBase)->whereIn('status_verifikasi', $statusGroups['ditolak'])->count(),
        ];

        $stats['pct_dikunjungi'] = $stats['jumlah_tunggakan'] > 0
            ? round(($stats['jumlah_sudah_pendataan'] / $stats['jumlah_tunggakan']) * 100, 2)
            : 0;
        $stats['pct_verifikasi'] = $stats['jumlah_sudah_pendataan'] > 0
            ? round(($stats['verifikasi'] / $stats['jumlah_sudah_pendataan']) * 100, 2)
            : 0;

        // Channel filter: distinct nopol tertagih sekali, lalu join ke bayar (lebih aman di tabel D2D besar).
        $channelNopolSub = "
            SELECT DISTINCT t.no_polisi AS nopol
            FROM {$tertagihTable} t
            WHERE t.year = " . (int) $year . "
              AND t.no_polisi IS NOT NULL
              AND t.no_polisi != ''
        ";

        $bayarAgg = DB::table('seng_bayar_pajak as b')
            ->join(DB::raw("({$channelNopolSub}) as ch"), 'ch.nopol', '=', 'b.nopol_')
            ->where('b.year', $year)
            ->whereNotNull('b.nopol_')
            ->where('b.nopol_', '!=', '')
            ->selectRaw('COUNT(*) as jumlah_terbayar')
            ->selectRaw('COUNT(DISTINCT b.nopol_) as jumlah_nopol_bayar')
            ->selectRaw('COALESCE(SUM(b.pkb_provinsi_jalan),0) + COALESCE(SUM(b.pkb_provinsi_tunggakan),0) as nominal_provinsi')
            ->selectRaw('COALESCE(SUM(b.pkb_opsen_jalan),0) + COALESCE(SUM(b.pkb_opsen_tunggakan),0) as nominal_opsen')
            ->first();

        $nominalProvinsi = (int) ($bayarAgg->nominal_provinsi ?? 0);
        $nominalOpsen = (int) ($bayarAgg->nominal_opsen ?? 0);

        $timing = $this->bayarSebelumSesudah($year, $pendataanTable, $channelNopolSub);

        $sebelumTotal = $timing['sebelum'] + $timing['tanpa'];
        $sebelumTotalNominal = $timing['sebelum_nominal'] + $timing['tanpa_nominal'];

        $bayar = [
            'jumlah_terbayar' => (int) ($bayarAgg->jumlah_terbayar ?? 0),
            'jumlah_nopol_bayar' => (int) ($bayarAgg->jumlah_nopol_bayar ?? 0),
            'nominal_provinsi' => $nominalProvinsi,
            'nominal_opsen' => $nominalOpsen,
            'nominal_total' => $nominalProvinsi + $nominalOpsen,
            'nominal_provinsi_fmt' => MoneyShortFormatter::format($nominalProvinsi),
            'nominal_opsen_fmt' => MoneyShortFormatter::format($nominalOpsen),
            'nominal_total_fmt' => MoneyShortFormatter::format($nominalProvinsi + $nominalOpsen),
            'sebelum_pendataan' => $sebelumTotal,
            'sebelum_pendataan_murni' => $timing['sebelum'],
            'tanpa_pendataan' => $timing['tanpa'],
            'sesudah_pendataan' => $timing['sesudah'],
            'sebelum_pendataan_nominal' => $sebelumTotalNominal,
            'sebelum_pendataan_murni_nominal' => $timing['sebelum_nominal'],
            'tanpa_pendataan_nominal' => $timing['tanpa_nominal'],
            'sesudah_pendataan_nominal' => $timing['sesudah_nominal'],
            'sebelum_pendataan_nominal_fmt' => MoneyShortFormatter::format($sebelumTotalNominal),
            'sebelum_pendataan_murni_nominal_fmt' => MoneyShortFormatter::format($timing['sebelum_nominal']),
            'tanpa_pendataan_nominal_fmt' => MoneyShortFormatter::format($timing['tanpa_nominal']),
            'sesudah_pendataan_nominal_fmt' => MoneyShortFormatter::format($timing['sesudah_nominal']),
        ];

        return compact('stats', 'bayar');
    }

    /**
     * @return array{sebelum:int,sesudah:int,tanpa:int,sebelum_nominal:int,sesudah_nominal:int,tanpa_nominal:int}
     */
    protected function bayarSebelumSesudah(int $year, string $pendataanTable, string $channelNopolSub): array
    {
        $yearStart = sprintf('%04d-01-01 00:00:00', $year);
        $yearEnd = sprintf('%04d-12-31 23:59:59', $year);

        $pendataanSub = "
            SELECT pd.nopol AS nopol, MIN(DATE(pd.created_at)) AS tgl_pendataan
            FROM {$pendataanTable} pd
            WHERE pd.deleted_at IS NULL
              AND pd.created_at BETWEEN '{$yearStart}' AND '{$yearEnd}'
              AND pd.nopol IS NOT NULL
              AND pd.nopol != ''
            GROUP BY pd.nopol
        ";

        $rows = DB::table('seng_bayar_pajak as b')
            ->join(DB::raw("({$channelNopolSub}) as ch"), 'ch.nopol', '=', 'b.nopol_')
            ->leftJoin(DB::raw("({$pendataanSub}) as p"), 'b.nopol_', '=', 'p.nopol')
            ->where('b.year', $year)
            ->whereNotNull('b.nopol_')
            ->where('b.nopol_', '!=', '')
            ->selectRaw('
                SUM(CASE WHEN p.tgl_pendataan IS NULL THEN 1 ELSE 0 END) AS tanpa,
                SUM(CASE WHEN p.tgl_pendataan IS NOT NULL AND b.tgl_bayar < p.tgl_pendataan THEN 1 ELSE 0 END) AS sebelum,
                SUM(CASE WHEN p.tgl_pendataan IS NOT NULL AND b.tgl_bayar >= p.tgl_pendataan THEN 1 ELSE 0 END) AS sesudah,
                COALESCE(SUM(CASE WHEN p.tgl_pendataan IS NULL
                    THEN COALESCE(b.pkb_provinsi_jalan,0)+COALESCE(b.pkb_provinsi_tunggakan,0)+COALESCE(b.pkb_opsen_jalan,0)+COALESCE(b.pkb_opsen_tunggakan,0)
                    ELSE 0 END),0) AS tanpa_nominal,
                COALESCE(SUM(CASE WHEN p.tgl_pendataan IS NOT NULL AND b.tgl_bayar < p.tgl_pendataan
                    THEN COALESCE(b.pkb_provinsi_jalan,0)+COALESCE(b.pkb_provinsi_tunggakan,0)+COALESCE(b.pkb_opsen_jalan,0)+COALESCE(b.pkb_opsen_tunggakan,0)
                    ELSE 0 END),0) AS sebelum_nominal,
                COALESCE(SUM(CASE WHEN p.tgl_pendataan IS NOT NULL AND b.tgl_bayar >= p.tgl_pendataan
                    THEN COALESCE(b.pkb_provinsi_jalan,0)+COALESCE(b.pkb_provinsi_tunggakan,0)+COALESCE(b.pkb_opsen_jalan,0)+COALESCE(b.pkb_opsen_tunggakan,0)
                    ELSE 0 END),0) AS sesudah_nominal
            ')
            ->first();

        return [
            'sebelum' => (int) ($rows->sebelum ?? 0),
            'sesudah' => (int) ($rows->sesudah ?? 0),
            'tanpa' => (int) ($rows->tanpa ?? 0),
            'sebelum_nominal' => (int) ($rows->sebelum_nominal ?? 0),
            'sesudah_nominal' => (int) ($rows->sesudah_nominal ?? 0),
            'tanpa_nominal' => (int) ($rows->tanpa_nominal ?? 0),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildMapKabkota(int $year, string $tertagihTable): array
    {
        $kabkotas = SengWilayah::query()
            ->where('id_up', 33)
            ->get(['id', 'nama', 'lat', 'lng']);

        $lokasiToKabkota = [];
        $samsats = SengSaamsat::query()->get(['id', 'id_wilayah_samsat', 'kabkota']);
        foreach ($samsats as $samsat) {
            $kabId = (string) $samsat->kabkota;
            if ($kabId === '') {
                continue;
            }
            foreach ([(string) ($samsat->id ?? ''), (string) ($samsat->id_wilayah_samsat ?? '')] as $seed) {
                if ($seed === '') {
                    continue;
                }
                foreach (SengSaamsat::codeVariants($seed) as $variant) {
                    $lokasiToKabkota[$variant] = $kabId;
                }
            }
        }

        $tagihanByLokasi = DB::table($tertagihTable)
            ->where('year', $year)
            ->selectRaw('id_lokasi_samsat, COUNT(*) as c')
            ->groupBy('id_lokasi_samsat')
            ->pluck('c', 'id_lokasi_samsat');

        // Satu lokasi per nopol bayar (hindari COUNT DISTINCT di join besar).
        $bayarByLokasi = DB::table(DB::raw("(
            SELECT x.nopol_, MIN(t.id_lokasi_samsat) AS id_lokasi_samsat
            FROM (
                SELECT DISTINCT b.nopol_
                FROM seng_bayar_pajak b
                WHERE b.year = " . (int) $year . "
                  AND b.nopol_ IS NOT NULL
                  AND b.nopol_ != ''
            ) x
            INNER JOIN {$tertagihTable} t
                ON t.no_polisi = x.nopol_
               AND t.year = " . (int) $year . "
            GROUP BY x.nopol_
        ) as paid"))
            ->selectRaw('id_lokasi_samsat, COUNT(*) as c')
            ->groupBy('id_lokasi_samsat')
            ->pluck('c', 'id_lokasi_samsat');

        $tagihanByKab = [];
        $bayarByKab = [];

        foreach ($tagihanByLokasi as $lokasi => $count) {
            $kabId = $lokasiToKabkota[(string) $lokasi] ?? null;
            if ($kabId === null) {
                continue;
            }
            $tagihanByKab[$kabId] = ($tagihanByKab[$kabId] ?? 0) + (int) $count;
        }

        foreach ($bayarByLokasi as $lokasi => $count) {
            $kabId = $lokasiToKabkota[(string) $lokasi] ?? null;
            if ($kabId === null) {
                continue;
            }
            $bayarByKab[$kabId] = ($bayarByKab[$kabId] ?? 0) + (int) $count;
        }

        $out = [];
        foreach ($kabkotas as $kab) {
            $kabId = (string) $kab->id;
            $tagihan = $tagihanByKab[$kabId] ?? 0;
            $bayar = min($tagihan, $bayarByKab[$kabId] ?? 0);
            $sisa = max(0, $tagihan - $bayar);
            $sisaPct = $tagihan > 0 ? round(($sisa / $tagihan) * 100, 2) : 100.0;

            $out[] = [
                'id' => $kabId,
                'nama' => $kab->nama,
                'lat' => $kab->lat !== null ? (float) $kab->lat : null,
                'lng' => $kab->lng !== null ? (float) $kab->lng : null,
                'tagihan' => $tagihan,
                'bayar' => $bayar,
                'sisa' => $sisa,
                'sisa_pct' => $sisaPct,
                'color' => $this->sisaColor($sisaPct),
            ];
        }

        usort($out, static fn ($a, $b) => $b['sisa_pct'] <=> $a['sisa_pct']);

        return $out;
    }

    protected function sisaColor(float $sisaPct): string
    {
        if ($sisaPct <= 25) {
            return '#22c55e';
        }
        if ($sisaPct <= 50) {
            return '#eab308';
        }
        if ($sisaPct <= 75) {
            return '#f97316';
        }

        return '#ef4444';
    }
}
