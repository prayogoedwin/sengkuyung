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

    protected function routeStats(): string
    {
        return 'rekap-visual.stats';
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
        return 'admin:rekap-visual:v7:';
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();

        $year = $request->filled('year') ? (int) $request->year : (int) date('Y');

        return view($this->viewName(), [
            'year' => $year,
            'pageTitle' => $this->pageTitle(),
            'channelLabel' => $this->channelLabel(),
            'isD2d' => $this->isD2d(),
            'routeIndex' => $this->routeIndex(),
            'routeSibling' => $this->routeSibling(),
            'statsUrl' => route($this->routeStats(), ['year' => $year]),
            'mapUrl' => route($this->routeMap(), ['year' => $year]),
            'refreshedAt' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    public function stats(Request $request)
    {
        $this->authorizeAccess();
        @set_time_limit(300);

        $year = $request->filled('year') ? (int) $request->year : (int) date('Y');

        $payload = ApiCacheManager::remember(
            $this->cachePrefix() . 'stats:y:' . $year,
            ApiCacheManager::dataTtl(),
            fn () => $this->buildStatsPayload($year)
        );

        return response()->json([
            'year' => $year,
            'stats' => $payload['stats'],
            'bayar' => $payload['bayar'],
            'refreshedAt' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    public function map(Request $request)
    {
        $this->authorizeAccess();
        @set_time_limit(300);

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
        $yearStart = sprintf('%04d-01-01 00:00:00', $year);
        $yearEnd = sprintf('%04d-12-31 23:59:59', $year);

        $tertagihAgg = DB::table($tertagihTable)
            ->where('year', $year)
            ->selectRaw('COUNT(*) as jumlah_tunggakan')
            ->selectRaw('SUM(CASE WHEN is_terdata = 1 THEN 1 ELSE 0 END) as jumlah_sudah_pendataan')
            ->selectRaw('SUM(CASE WHEN is_terdata = 0 THEN 1 ELSE 0 END) as jumlah_belum_pendataan')
            ->first();

        $menunggu = implode(',', array_map('intval', $statusGroups['menunggu']));
        $verifikasi = implode(',', array_map('intval', $statusGroups['verifikasi']));
        $ditolak = implode(',', array_map('intval', $statusGroups['ditolak']));

        $pendataanAgg = DB::table($pendataanTable)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$yearStart, $yearEnd])
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
            : 0;
        $stats['pct_verifikasi'] = $stats['jumlah_sudah_pendataan'] > 0
            ? round(($stats['verifikasi'] / $stats['jumlah_sudah_pendataan']) * 100, 2)
            : 0;

        $bayar = $this->buildBayarStats($year, $tertagihTable, $pendataanTable, $yearStart, $yearEnd);
        $bayar = array_merge($bayar, $this->buildPotensiNominal($pendataanTable, $yearStart, $yearEnd, $stats['jumlah_tunggakan']));

        $totalBayar = (int) ($bayar['nominal_total'] ?? 0);
        $totalPotensi = (int) ($bayar['potensi_total'] ?? 0);
        $bayar['pct_bayar_vs_potensi'] = $totalPotensi > 0
            ? round(($totalBayar / $totalPotensi) * 100, 2)
            : 0.0;

        return compact('stats', 'bayar');
    }

    /**
     * TOTAL POTENSI = PKB provinsi + opsen dari obyek potensi.
     * Karena data_tertagih tidak punya nominal, diestimasi dari rata-rata pendataan × jumlah obyek potensi.
     *
     * @return array<string, mixed>
     */
    protected function buildPotensiNominal(
        string $pendataanTable,
        string $yearStart,
        string $yearEnd,
        int $jumlahTunggakan
    ): array {
        $agg = DB::table($pendataanTable)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->selectRaw('COUNT(*) as c')
            ->selectRaw('SUM(COALESCE(pkb_pokok, 0)) as provinsi')
            ->selectRaw('SUM(COALESCE(pkb_pokok_opsen, 0)) as opsen')
            ->first();

        $sampleCount = (int) ($agg->c ?? 0);
        $sampleProv = (float) ($agg->provinsi ?? 0);
        $sampleOpsen = (float) ($agg->opsen ?? 0);

        if ($sampleCount > 0 && $jumlahTunggakan > 0) {
            $avgProv = $sampleProv / $sampleCount;
            $avgOpsen = $sampleOpsen / $sampleCount;
            $potensiProvinsi = (int) round($avgProv * $jumlahTunggakan);
            $potensiOpsen = (int) round($avgOpsen * $jumlahTunggakan);
        } else {
            $potensiProvinsi = (int) round($sampleProv);
            $potensiOpsen = (int) round($sampleOpsen);
        }

        $potensiTotal = $potensiProvinsi + $potensiOpsen;

        return [
            'potensi_provinsi' => $potensiProvinsi,
            'potensi_opsen' => $potensiOpsen,
            'potensi_total' => $potensiTotal,
            'potensi_provinsi_fmt' => MoneyShortFormatter::format($potensiProvinsi),
            'potensi_opsen_fmt' => MoneyShortFormatter::format($potensiOpsen),
            'potensi_total_fmt' => MoneyShortFormatter::format($potensiTotal),
        ];
    }

    /**
     * Agregasi bayar di PHP: hindari LEFT JOIN SQL bayar×pendataan yang 50–100+ detik.
     *
     * @return array<string, mixed>
     */
    protected function buildBayarStats(
        int $year,
        string $tertagihTable,
        string $pendataanTable,
        string $yearStart,
        string $yearEnd
    ): array {
        $rows = DB::table('seng_bayar_pajak as b')
            ->where('b.year', $year)
            ->whereNotNull('b.nopol_')
            ->where('b.nopol_', '!=', '')
            ->whereExists(function ($q) use ($tertagihTable, $year) {
                $q->select(DB::raw(1))
                    ->from("{$tertagihTable} as t")
                    ->whereColumn('t.no_polisi', 'b.nopol_')
                    ->where('t.year', $year)
                    ->limit(1);
            })
            ->get([
                'b.nopol_',
                'b.tgl_bayar',
                'b.pkb_provinsi_jalan',
                'b.pkb_provinsi_tunggakan',
                'b.pkb_opsen_jalan',
                'b.pkb_opsen_tunggakan',
            ]);

        $pendataanMap = DB::table($pendataanTable)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->whereNotNull('nopol')
            ->where('nopol', '!=', '')
            ->groupBy('nopol')
            ->selectRaw('nopol, MIN(DATE(created_at)) as tgl_pendataan')
            ->pluck('tgl_pendataan', 'nopol');

        $jumlahTerbayar = 0;
        $nopolUnik = [];
        $nominalProvinsi = 0;
        $nominalOpsen = 0;
        $sebelum = 0;
        $sesudah = 0;
        $tanpa = 0;
        $sebelumProv = 0;
        $sebelumOps = 0;
        $sesudahProv = 0;
        $sesudahOps = 0;
        $tanpaProv = 0;
        $tanpaOps = 0;

        foreach ($rows as $row) {
            $jumlahTerbayar++;
            $nopol = (string) $row->nopol_;
            $nopolUnik[$nopol] = true;

            $prov = (int) ($row->pkb_provinsi_jalan ?? 0) + (int) ($row->pkb_provinsi_tunggakan ?? 0);
            $ops = (int) ($row->pkb_opsen_jalan ?? 0) + (int) ($row->pkb_opsen_tunggakan ?? 0);
            $nominalProvinsi += $prov;
            $nominalOpsen += $ops;

            $tglPendataan = $pendataanMap[$nopol] ?? null;
            $tglBayar = $row->tgl_bayar ? substr((string) $row->tgl_bayar, 0, 10) : null;

            if ($tglPendataan === null || $tglPendataan === '') {
                $tanpa++;
                $tanpaProv += $prov;
                $tanpaOps += $ops;
                continue;
            }

            if ($tglBayar !== null && $tglBayar < $tglPendataan) {
                $sebelum++;
                $sebelumProv += $prov;
                $sebelumOps += $ops;
            } else {
                $sesudah++;
                $sesudahProv += $prov;
                $sesudahOps += $ops;
            }
        }

        // "Sebelum pendataan" = bayar sebelum tgl pendataan + belum ada pendataan (sama seperti sebelumnya).
        $sebelumTotal = $sebelum + $tanpa;
        $sebelumProvTotal = $sebelumProv + $tanpaProv;
        $sebelumOpsTotal = $sebelumOps + $tanpaOps;
        $sebelumNominalTotal = $sebelumProvTotal + $sebelumOpsTotal;
        $sesudahNominal = $sesudahProv + $sesudahOps;

        return [
            'jumlah_terbayar' => $jumlahTerbayar,
            'jumlah_nopol_bayar' => count($nopolUnik),
            'nominal_provinsi' => $nominalProvinsi,
            'nominal_opsen' => $nominalOpsen,
            'nominal_total' => $nominalProvinsi + $nominalOpsen,
            'nominal_provinsi_fmt' => MoneyShortFormatter::format($nominalProvinsi),
            'nominal_opsen_fmt' => MoneyShortFormatter::format($nominalOpsen),
            'nominal_total_fmt' => MoneyShortFormatter::format($nominalProvinsi + $nominalOpsen),
            'sebelum_pendataan' => $sebelumTotal,
            'sebelum_pendataan_murni' => $sebelum,
            'tanpa_pendataan' => $tanpa,
            'sesudah_pendataan' => $sesudah,
            'sebelum_pendataan_provinsi' => $sebelumProvTotal,
            'sebelum_pendataan_opsen' => $sebelumOpsTotal,
            'sebelum_pendataan_nominal' => $sebelumNominalTotal,
            'sesudah_pendataan_provinsi' => $sesudahProv,
            'sesudah_pendataan_opsen' => $sesudahOps,
            'sesudah_pendataan_nominal' => $sesudahNominal,
            'sebelum_pendataan_provinsi_fmt' => MoneyShortFormatter::format($sebelumProvTotal),
            'sebelum_pendataan_opsen_fmt' => MoneyShortFormatter::format($sebelumOpsTotal),
            'sebelum_pendataan_nominal_fmt' => MoneyShortFormatter::format($sebelumNominalTotal),
            'sesudah_pendataan_provinsi_fmt' => MoneyShortFormatter::format($sesudahProv),
            'sesudah_pendataan_opsen_fmt' => MoneyShortFormatter::format($sesudahOps),
            'sesudah_pendataan_nominal_fmt' => MoneyShortFormatter::format($sesudahNominal),
            // Legacy keys (tetap ada agar cache lama tidak error di FE lama)
            'sebelum_pendataan_murni_nominal' => $sebelumProv + $sebelumOps,
            'tanpa_pendataan_nominal' => $tanpaProv + $tanpaOps,
            'sebelum_pendataan_murni_nominal_fmt' => MoneyShortFormatter::format($sebelumProv + $sebelumOps),
            'tanpa_pendataan_nominal_fmt' => MoneyShortFormatter::format($tanpaProv + $tanpaOps),
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
            ->selectRaw('SUM(CASE WHEN is_terdata = 1 THEN 1 ELSE 0 END) as pendataan')
            ->groupBy('id_lokasi_samsat')
            ->get()
            ->keyBy(fn ($row) => (string) $row->id_lokasi_samsat);

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
        $pendataanByKab = [];
        $bayarByKab = [];

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

        $out = [];
        foreach ($kabkotas as $kab) {
            $kabId = (string) $kab->id;
            $tagihan = $tagihanByKab[$kabId] ?? 0;
            $pendataan = min($tagihan, $pendataanByKab[$kabId] ?? 0);
            $bayar = min($tagihan, $bayarByKab[$kabId] ?? 0);
            $sisa = max(0, $tagihan - $bayar);
            $sisaPct = $tagihan > 0 ? round(($sisa / $tagihan) * 100, 2) : 100.0;

            $out[] = [
                'id' => $kabId,
                'nama' => $kab->nama,
                'lat' => $kab->lat !== null ? (float) $kab->lat : null,
                'lng' => $kab->lng !== null ? (float) $kab->lng : null,
                'tagihan' => $tagihan,
                'pendataan' => $pendataan,
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
