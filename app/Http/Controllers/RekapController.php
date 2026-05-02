<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\DataTertagih;
use App\Models\SengPendataanKendaraan;
use App\Models\SengSaamsat;
use App\Models\SengStatus;
use App\Models\SengStatusVerifikasi;
use App\Models\SengWilayah;
use App\Models\SengWilayahKec;
use App\Models\SengWilayahKel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\ApiCacheManager;

class RekapController extends Controller
{
    public function index_(Request $request)
    {

        $status = SengStatus::select('*')->get();

        $status_verifikasis = SengStatusVerifikasi::select('*')->get();

        $kabkotas = SengWilayah::select('*')
        ->where('id_up', 33)
        ->get();

        return view('backend.rekap.index',  compact('kabkotas','status','status_verifikasis'));

    }

    public function index(Request $request)
    {
        $userRole = auth()->user()->role;
        $user = User::findOrFail(auth()->id());

        $kabkotaId = $request->input('kabkota_id');
        $lokasiSamsatId = $request->input('lokasi_samsat');
        $kecamatanSamsatId = $request->input('kecamatan_samsat');
        $kelurahanSamsatId = $request->input('kelurahan_samsat');

        // Query pendataan untuk statistik PKB dan Top 5.
        $pendataanQuery = SengPendataanKendaraan::query();
        if ($userRole == 4) {
            $pendataanQuery->where('kota', $user->uptd_id);
        } elseif ($userRole == 7) {
            $pendataanQuery->where('created_by', auth()->id());
        }

        if (!$request->tanggal_start && !$request->tanggal_end) {
            $pendataanQuery->whereYear('created_at', now()->year);
        }

        if ($kabkotaId) {
            if ($userRole == 4) {
                $pendataanQuery->where('kota', $user->uptd_id);
            } else {
                $pendataanQuery->where('kota_dagri', $kabkotaId);
            }
        }
        if ($lokasiSamsatId) {
            $pendataanQuery->where('kota', $lokasiSamsatId);
        }
        if ($kecamatanSamsatId) {
            $pendataanQuery->where('kec', $kecamatanSamsatId);
        }
        if ($kelurahanSamsatId) {
            $pendataanQuery->where('desa', $kelurahanSamsatId);
        }
        if ($request->status_id) {
            $pendataanQuery->where('status', $request->status_id);
        }
        if ($request->status_verifikasi_id) {
            $pendataanQuery->where('status_verifikasi', $request->status_verifikasi_id);
        }
        if ($request->tanggal_start && $request->tanggal_end) {
            $pendataanQuery->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }

        // Query data tertagih untuk potensi dan terdata.
        $dataTertagihQuery = DataTertagih::query();
        if (!$request->tanggal_start && !$request->tanggal_end) {
            $dataTertagihQuery->where('year', now()->year);
        }
        if ($request->tanggal_start && $request->tanggal_end) {
            $dataTertagihQuery->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }

        if ($kabkotaId) {
            $lokasiKabkota = SengSaamsat::where('kabkota', $kabkotaId)
                ->pluck('id_wilayah_samsat')
                ->filter()
                ->values()
                ->all();

            if (!empty($lokasiKabkota)) {
                $dataTertagihQuery->whereIn('id_lokasi_samsat', $lokasiKabkota);
            } else {
                $dataTertagihQuery->whereRaw('1 = 0');
            }
        }
        if ($lokasiSamsatId) {
            $dataTertagihQuery->whereIn('id_lokasi_samsat', $this->codeVariants($lokasiSamsatId));
        }
        if ($kecamatanSamsatId) {
            $dataTertagihQuery->whereIn('id_kecamatan', $this->codeVariants($kecamatanSamsatId));
        }
        if ($kelurahanSamsatId) {
            $dataTertagihQuery->whereIn('id_kelurahan', $this->codeVariants($kelurahanSamsatId));
        }

        $totalPotensiKend = (clone $dataTertagihQuery)->count();
        $totalSudahTerdata = (clone $dataTertagihQuery)->where('is_terdata', 1)->count();
        $nominalPkb = (clone $pendataanQuery)->sum('pkb_pokok');

        $data = [
            'total_potensi' => $totalPotensiKend,
            'total_terdata' => $totalSudahTerdata,
            'pkb' => $nominalPkb,
        ];

        // Tentukan level barchart sesuai filter.
        $chartLevel = 'lokasi';
        $chartGroupColumnTertagih = 'id_lokasi_samsat';
        $chartGroupColumnPendataan = 'kota';
        $chartTitle = 'Perbandingan Lokasi Samsat';

        if ($kecamatanSamsatId) {
            $chartLevel = 'kelurahan';
            $chartGroupColumnTertagih = 'id_kelurahan';
            $chartGroupColumnPendataan = 'desa';
            $chartTitle = 'Perbandingan Kelurahan';
        } elseif ($kabkotaId) {
            // Jika pilih kab/kota saja (atau kab/kota + lokasi), bandingkan antar kecamatan.
            $chartLevel = 'kecamatan';
            $chartGroupColumnTertagih = 'id_kecamatan';
            $chartGroupColumnPendataan = 'kec';
            $chartTitle = 'Perbandingan Kecamatan';
        }

        $groupedTertagih = (clone $dataTertagihQuery)
            ->select(
                $chartGroupColumnTertagih . ' as group_code',
                DB::raw('COUNT(*) as total_potensi'),
                DB::raw('SUM(CASE WHEN is_terdata = 1 THEN 1 ELSE 0 END) as total_terdata')
            )
            ->groupBy($chartGroupColumnTertagih)
            ->get();

        $groupedPkb = (clone $pendataanQuery)
            ->select(
                $chartGroupColumnPendataan . ' as group_code',
                DB::raw('SUM(pkb_pokok) as total_pkb')
            )
            ->groupBy($chartGroupColumnPendataan)
            ->get();

        $chartCodes = $groupedTertagih->pluck('group_code')
            ->merge($groupedPkb->pluck('group_code'))
            ->filter()
            ->unique()
            ->values();

        $chartNameMap = [];
        if ($chartLevel === 'lokasi' && $chartCodes->isNotEmpty()) {
            $samsatRows = SengSaamsat::whereIn('id_wilayah_samsat', $chartCodes)->get();
            foreach ($samsatRows as $row) {
                $chartNameMap[(string) $row->id_wilayah_samsat] = $row->lokasi ?: $row->lokasi_singkat ?: (string) $row->id_wilayah_samsat;
            }
        } elseif ($chartLevel === 'kecamatan' && $chartCodes->isNotEmpty()) {
            $kecRows = SengWilayahKec::whereIn('id_kecamatan', $chartCodes)->get();
            foreach ($kecRows as $row) {
                $chartNameMap[(string) $row->id_kecamatan] = $row->kecamatan ?: (string) $row->id_kecamatan;
            }
        } elseif ($chartLevel === 'kelurahan' && $chartCodes->isNotEmpty()) {
            $kelRows = SengWilayahKel::whereIn('id_kelurahan', $chartCodes)->get();
            foreach ($kelRows as $row) {
                $chartNameMap[(string) $row->id_kelurahan] = $row->kelurahan ?: (string) $row->id_kelurahan;
            }
        }

        $chartCategories = [];
        $chartPotensiData = [];
        $chartSudahTerdataData = [];
        $chartNominalPkbData = [];

        foreach ($chartCodes as $code) {
            $code = (string) $code;
            $tert = $groupedTertagih->firstWhere('group_code', $code);
            $pkbGroup = $groupedPkb->firstWhere('group_code', $code);

            $chartCategories[] = $chartNameMap[$code] ?? $code;
            $chartPotensiData[] = (int) ($tert->total_potensi ?? 0);
            $chartSudahTerdataData[] = (int) ($tert->total_terdata ?? 0);
            $chartNominalPkbData[] = (float) ($pkbGroup->total_pkb ?? 0);
        }

        $barChartData = [
            'title' => $chartTitle,
            'categories' => $chartCategories,
            'potensi' => $chartPotensiData,
            'terdata' => $chartSudahTerdataData,
            'pkb' => $chartNominalPkbData,
        ];

        // Marker peta berdasarkan agregasi data tertagih per lokasi samsat.
        $mapGrouped = (clone $dataTertagihQuery)
            ->select('id_lokasi_samsat', DB::raw('COUNT(*) as total'))
            ->groupBy('id_lokasi_samsat')
            ->get();

        $mapIds = $mapGrouped->pluck('id_lokasi_samsat')->filter()->unique()->values();
        $mapSamsat = $mapIds->isNotEmpty()
            ? SengSaamsat::whereIn('id_wilayah_samsat', $mapIds)->get()
            : collect();

        $samsatMapById = [];
        foreach ($mapSamsat as $s) {
            $samsatMapById[(string) $s->id_wilayah_samsat] = $s;
        }

        $mapPoints = [];
        foreach ($mapGrouped as $item) {
            $code = (string) $item->id_lokasi_samsat;
            if (!isset($samsatMapById[$code])) {
                continue;
            }
            $samsat = $samsatMapById[$code];
            if ($samsat->lat === null || $samsat->lng === null) {
                continue;
            }

            $mapPoints[] = [
                'wilayah' => $samsat->lokasi ?: $samsat->lokasi_singkat ?: $code,
                'lat' => (float) $samsat->lat,
                'lng' => (float) $samsat->lng,
                'total_vehicles' => (int) $item->total,
            ];
        }

        if (!empty($mapPoints)) {
            $avgLat = collect($mapPoints)->avg('lat');
            $avgLng = collect($mapPoints)->avg('lng');
            $koordinats = (object) ['lat' => $avgLat, 'lng' => $avgLng];
        } else {
            $koordinats = $kabkotaId
                ? SengWilayah::where('id', $kabkotaId)->first()
                : SengWilayah::where('id', 33)->first();

            $koordinats = $koordinats ?: (object) ['lat' => -7.150975, 'lng' => 110.140259];
        }

        // Top 5 pendataan berdasarkan wilayah dari tabel seng_pendataan_kendaraan.
        $topKota = (clone $pendataanQuery)
            ->selectRaw("COALESCE(NULLIF(kota_name, ''), '-') as wilayah, COUNT(*) as total")
            ->groupBy('kota_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topKecamatan = (clone $pendataanQuery)
            ->selectRaw("COALESCE(NULLIF(kec_name, ''), '-') as wilayah, COUNT(*) as total")
            ->groupBy('kec_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topKelurahan = (clone $pendataanQuery)
            ->selectRaw("COALESCE(NULLIF(desa_name, ''), '-') as wilayah, COUNT(*) as total")
            ->groupBy('desa_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    
        // Ambil data status verifikasi dan wilayah
        $statuss = ApiCacheManager::remember('admin:master:status:all', ApiCacheManager::masterTtl(), static function () {
            return SengStatus::all();
        });
        $kabkotas = ApiCacheManager::remember('admin:master:kabkota:all', ApiCacheManager::masterTtl(), static function () {
            return SengWilayah::where('id_up', 33)->get();
        });
        $status_verifikasis = ApiCacheManager::remember('admin:master:status-verifikasi:all', ApiCacheManager::masterTtl(), static function () {
            return SengStatusVerifikasi::select('*')->get();
        });

    
        return view('backend.rekap.index',  compact(
            'kabkotas','statuss','status_verifikasis',
            'data', 'koordinats', 'mapPoints',
            'barChartData', 'topKota', 'topKecamatan', 'topKelurahan'
        ));
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
}
