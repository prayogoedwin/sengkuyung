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
use Illuminate\Support\Facades\Schema;

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
        return 'admin:rekap-visual:v2:';
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
        $useNopolKey = Schema::hasColumn('seng_bayar_pajak', 'nopol_key')
            && Schema::hasColumn($tertagihTable, 'nopol_key');

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

        $bayarAgg = DB::table('seng_bayar_pajak as b')
            ->where('b.year', $year)
            ->whereNotNull($useNopolKey ? 'b.nopol_key' : 'b.nopol_')
            ->whereExists(function ($q) use ($tertagihTable, $year, $useNopolKey) {
                $q->select(DB::raw(1))
                    ->from("{$tertagihTable} as t")
                    ->where('t.year', $year);
                $this->applyNopolMatch($q, $useNopolKey, 'b', 't');
            })
            ->selectRaw('COUNT(*) as jumlah_terbayar')
            ->selectRaw('COUNT(DISTINCT ' . ($useNopolKey ? 'b.nopol_key' : 'b.nopol_') . ') as jumlah_nopol_bayar')
            ->selectRaw('COALESCE(SUM(b.pkb_provinsi_jalan),0) + COALESCE(SUM(b.pkb_provinsi_tunggakan),0) as nominal_provinsi')
            ->selectRaw('COALESCE(SUM(b.pkb_opsen_jalan),0) + COALESCE(SUM(b.pkb_opsen_tunggakan),0) as nominal_opsen')
            ->first();

        $nominalProvinsi = (int) ($bayarAgg->nominal_provinsi ?? 0);
        $nominalOpsen = (int) ($bayarAgg->nominal_opsen ?? 0);

        $timing = $this->bayarSebelumSesudah($year, $pendataanTable, $tertagihTable, $useNopolKey);

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

        $mapKabkota = $this->buildMapKabkota($year, $tertagihTable, $useNopolKey);

        return compact('stats', 'bayar', 'mapKabkota');
    }

    /**
     * @return array{sebelum:int,sesudah:int,tanpa:int,sebelum_nominal:int,sesudah_nominal:int}
     */
    protected function bayarSebelumSesudah(int $year, string $pendataanTable, string $tertagihTable, bool $useNopolKey): array
    {
        $pendataanHasKey = $useNopolKey && Schema::hasColumn($pendataanTable, 'nopol_key');
        $pendataanKeyExpr = $pendataanHasKey
            ? 'pd.nopol_key'
            : $this->nopolExpr('pd.nopol');
        $bayarJoinKey = $useNopolKey ? 'b.nopol_key' : $this->nopolExpr('b.nopol_');

        $yearStart = sprintf('%04d-01-01 00:00:00', $year);
        $yearEnd = sprintf('%04d-12-31 23:59:59', $year);

        $pendataanSub = "
            SELECT {$pendataanKeyExpr} AS nopol_key, MIN(DATE(created_at)) AS tgl_pendataan
            FROM {$pendataanTable} pd
            WHERE pd.deleted_at IS NULL
              AND pd.created_at BETWEEN '{$yearStart}' AND '{$yearEnd}'
              " . ($pendataanHasKey ? 'AND pd.nopol_key IS NOT NULL' : '') . "
            GROUP BY {$pendataanKeyExpr}
        ";

        $query = DB::table('seng_bayar_pajak as b')
            ->where('b.year', $year)
            ->whereNotNull($useNopolKey ? 'b.nopol_key' : 'b.nopol_')
            ->whereExists(function ($q) use ($tertagihTable, $year, $useNopolKey) {
                $q->select(DB::raw(1))
                    ->from("{$tertagihTable} as t")
                    ->where('t.year', $year);
                $this->applyNopolMatch($q, $useNopolKey, 'b', 't');
            });

        if ($useNopolKey) {
            $query->leftJoin(DB::raw("({$pendataanSub}) as p"), 'b.nopol_key', '=', 'p.nopol_key');
        } else {
            $query->leftJoin(DB::raw("({$pendataanSub}) as p"), DB::raw($bayarJoinKey), '=', 'p.nopol_key');
        }

        $rows = $query
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
    protected function buildMapKabkota(int $year, string $tertagihTable, bool $useNopolKey): array
    {
        $kabkotas = SengWilayah::query()
            ->where('id_up', 33)
            ->get(['id', 'nama', 'lat', 'lng']);

        // lokasi_samsat variant -> kabkota id (sekali saja, tanpa N+1 query)
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

        $distinctCol = $useNopolKey ? 'b.nopol_key' : 'b.nopol_';

        $bayarJoin = DB::table('seng_bayar_pajak as b')
            ->join("{$tertagihTable} as t", function ($join) use ($year, $useNopolKey) {
                $join->where('t.year', $year);
                $this->applyNopolMatch($join, $useNopolKey, 'b', 't');
            })
            ->where('b.year', $year)
            ->when($useNopolKey, fn ($q) => $q->whereNotNull('b.nopol_key'))
            ->selectRaw("t.id_lokasi_samsat, COUNT(DISTINCT {$distinctCol}) as c")
            ->groupBy('t.id_lokasi_samsat')
            ->pluck('c', 'id_lokasi_samsat');

        $bayarByLokasi = $bayarJoin;

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
            // Hati-hati: satu nopol bisa muncul di beberapa lokasi jika data kotor.
            // Ambil max per lokasi lalu jumlahkan per kab — cukup akurat untuk sisa % warna.
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

    protected function applyNopolMatch(mixed $query, bool $useNopolKey, string $bayarAlias, string $tertagihAlias): void
    {
        if ($useNopolKey) {
            $query->whereColumn("{$bayarAlias}.nopol_key", "{$tertagihAlias}.nopol_key")
                ->whereNotNull("{$tertagihAlias}.nopol_key");

            return;
        }

        $query->whereRaw(
            $this->nopolExpr("{$bayarAlias}.nopol_") . ' = ' . $this->nopolExpr("{$tertagihAlias}.no_polisi")
        );
    }

    protected function nopolExpr(string $column): string
    {
        return "UPPER(REGEXP_REPLACE(COALESCE({$column}, ''), '[^A-Za-z0-9]', ''))";
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
