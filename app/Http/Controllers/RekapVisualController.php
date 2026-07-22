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
        return 'admin:rekap-visual:';
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['super-admin', 'superadmin', 'admin', 'adminprov', 'uptd', 'uppd'], 'web'),
            403,
            'Akses terbatas.'
        );

        $year = $request->filled('year') ? (int) $request->year : (int) date('Y');

        $payload = ApiCacheManager::remember(
            $this->cachePrefix() . 'y:' . $year,
            ApiCacheManager::dashboardTtl(),
            fn () => $this->buildPayload($year)
        );

        return view($this->viewName(), [
            'year' => $year,
            'pageTitle' => $this->pageTitle(),
            'channelLabel' => $this->channelLabel(),
            'isD2d' => $this->isD2d(),
            'routeIndex' => $this->routeIndex(),
            'routeSibling' => $this->routeSibling(),
            'stats' => $payload['stats'],
            'bayar' => $payload['bayar'],
            'mapKabkota' => $payload['mapKabkota'],
            'refreshedAt' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    protected function buildPayload(int $year): array
    {
        $statusGroups = VerifikasiStatusGroups::all();
        $tertagihTable = (new ($this->dataTertagihModelClass()))->getTable();
        $pendataanTable = (new ($this->pendataanModelClass()))->getTable();

        $tertagihBase = $this->dataTertagihModelClass()::query()->where('year', $year);
        $pendataanBase = $this->pendataanModelClass()::query()
            ->whereYear('created_at', $year);

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

        // Hanya pembayaran yang nopol-nya ada di data tertagih channel ini (reguler / D2D).
        $nopolExprBayar = "REPLACE(REPLACE(UPPER(COALESCE(b.nopol_, '')), '-', ''), ' ', '')";
        $nopolExprTertagih = "REPLACE(REPLACE(UPPER(COALESCE(t.no_polisi, '')), '-', ''), ' ', '')";

        $bayarAgg = DB::table('seng_bayar_pajak as b')
            ->where('b.year', $year)
            ->whereExists(function ($q) use ($tertagihTable, $year, $nopolExprBayar, $nopolExprTertagih) {
                $q->select(DB::raw(1))
                    ->from("{$tertagihTable} as t")
                    ->whereRaw("{$nopolExprBayar} = {$nopolExprTertagih}")
                    ->where('t.year', $year);
            })
            ->selectRaw('COUNT(*) as jumlah_terbayar')
            ->selectRaw('COUNT(DISTINCT b.nopol_) as jumlah_nopol_bayar')
            ->selectRaw('COALESCE(SUM(b.pkb_provinsi_jalan),0) + COALESCE(SUM(b.pkb_provinsi_tunggakan),0) as nominal_provinsi')
            ->selectRaw('COALESCE(SUM(b.pkb_opsen_jalan),0) + COALESCE(SUM(b.pkb_opsen_tunggakan),0) as nominal_opsen')
            ->first();

        $nominalProvinsi = (int) ($bayarAgg->nominal_provinsi ?? 0);
        $nominalOpsen = (int) ($bayarAgg->nominal_opsen ?? 0);

        $timing = $this->bayarSebelumSesudah($year, $pendataanTable, $tertagihTable);

        $bayar = [
            'jumlah_terbayar' => (int) ($bayarAgg->jumlah_terbayar ?? 0),
            'jumlah_nopol_bayar' => (int) ($bayarAgg->jumlah_nopol_bayar ?? 0),
            'nominal_provinsi' => $nominalProvinsi,
            'nominal_opsen' => $nominalOpsen,
            'nominal_total' => $nominalProvinsi + $nominalOpsen,
            'nominal_provinsi_fmt' => MoneyShortFormatter::format($nominalProvinsi),
            'nominal_opsen_fmt' => MoneyShortFormatter::format($nominalOpsen),
            'nominal_total_fmt' => MoneyShortFormatter::format($nominalProvinsi + $nominalOpsen),
            'sebelum_pendataan' => $timing['sebelum'],
            'sesudah_pendataan' => $timing['sesudah'],
            'tanpa_pendataan' => $timing['tanpa'],
            'sebelum_pendataan_nominal' => $timing['sebelum_nominal'],
            'sesudah_pendataan_nominal' => $timing['sesudah_nominal'],
            'sebelum_pendataan_nominal_fmt' => MoneyShortFormatter::format($timing['sebelum_nominal']),
            'sesudah_pendataan_nominal_fmt' => MoneyShortFormatter::format($timing['sesudah_nominal']),
        ];

        $mapKabkota = $this->buildMapKabkota($year, $tertagihTable);

        return compact('stats', 'bayar', 'mapKabkota');
    }

    /**
     * @return array{sebelum:int,sesudah:int,tanpa:int,sebelum_nominal:int,sesudah_nominal:int}
     */
    protected function bayarSebelumSesudah(int $year, string $pendataanTable, string $tertagihTable): array
    {
        $nopolExprBayar = "REPLACE(REPLACE(UPPER(COALESCE(b.nopol_, '')), '-', ''), ' ', '')";
        $nopolExprPendataan = "REPLACE(REPLACE(UPPER(COALESCE(pd.nopol, '')), '-', ''), ' ', '')";
        $nopolExprTertagih = "REPLACE(REPLACE(UPPER(COALESCE(t.no_polisi, '')), '-', ''), ' ', '')";

        $rows = DB::table('seng_bayar_pajak as b')
            ->where('b.year', $year)
            ->whereExists(function ($q) use ($tertagihTable, $year, $nopolExprBayar, $nopolExprTertagih) {
                $q->select(DB::raw(1))
                    ->from("{$tertagihTable} as t")
                    ->whereRaw("{$nopolExprBayar} = {$nopolExprTertagih}")
                    ->where('t.year', $year);
            })
            ->leftJoin(DB::raw("(
                SELECT {$nopolExprPendataan} AS nopol_key, MIN(DATE(created_at)) AS tgl_pendataan
                FROM {$pendataanTable} pd
                WHERE pd.deleted_at IS NULL
                  AND YEAR(pd.created_at) = " . (int) $year . "
                GROUP BY {$nopolExprPendataan}
            ) as p"), DB::raw($nopolExprBayar), '=', 'p.nopol_key')
            ->selectRaw('
                SUM(CASE WHEN p.tgl_pendataan IS NULL THEN 1 ELSE 0 END) AS tanpa,
                SUM(CASE WHEN p.tgl_pendataan IS NOT NULL AND b.tgl_bayar < p.tgl_pendataan THEN 1 ELSE 0 END) AS sebelum,
                SUM(CASE WHEN p.tgl_pendataan IS NOT NULL AND b.tgl_bayar >= p.tgl_pendataan THEN 1 ELSE 0 END) AS sesudah,
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

        $out = [];
        foreach ($kabkotas as $kab) {
            $kabId = (string) $kab->id;
            $lokasiIds = SengSaamsat::lokasiFilterVariantsByKabkota($kabId);

            $tagihan = 0;
            $bayar = 0;

            if ($lokasiIds !== []) {
                $tagihan = (int) DB::table($tertagihTable)
                    ->where('year', $year)
                    ->whereIn('id_lokasi_samsat', $lokasiIds)
                    ->count();

                $nopolExprBayar = "REPLACE(REPLACE(UPPER(COALESCE(b.nopol_, '')), '-', ''), ' ', '')";
                $nopolExprTertagih = "REPLACE(REPLACE(UPPER(COALESCE(t.no_polisi, '')), '-', ''), ' ', '')";

                $bayar = (int) DB::table('seng_bayar_pajak as b')
                    ->where('b.year', $year)
                    ->whereExists(function ($q) use ($tertagihTable, $year, $lokasiIds, $nopolExprBayar, $nopolExprTertagih) {
                        $q->select(DB::raw(1))
                            ->from("{$tertagihTable} as t")
                            ->whereRaw("{$nopolExprBayar} = {$nopolExprTertagih}")
                            ->where('t.year', $year)
                            ->whereIn('t.id_lokasi_samsat', $lokasiIds);
                    })
                    ->selectRaw('COUNT(DISTINCT b.nopol_) as c')
                    ->value('c');
            }

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
            return '#22c55e'; // hijau
        }
        if ($sisaPct <= 50) {
            return '#eab308'; // kuning
        }
        if ($sisaPct <= 75) {
            return '#f97316'; // oranye
        }

        return '#ef4444'; // merah
    }
}
