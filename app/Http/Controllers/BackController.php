<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SengStatus;
use App\Models\SengWilayah;
use App\Models\DataTertagih;
use App\Models\DataTertagihD2d;
use App\Models\SengPendataanKendaraan;
use App\Models\SengPendataanKendaraanD2d;
use App\Models\SengSaamsat;
use App\Models\WilayahSamsat;
use App\Support\ApiCacheManager;


class BackController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        $isKecamatanScope = $user && $user->hasRole('kecamatan');
        $isKelurahanScope = $user && $user->hasRole('kelurahan');
        $isKabkotaScope = $user && $user->hasRole('kabkota');
        $isUppdScope = $user && ($user->hasRole('uppd') || $user->hasRole('uptd'));
        $isScopedKabkota = $user && (
            $isKabkotaScope
            || $isUppdScope
            || $isKecamatanScope
            || $isKelurahanScope
        );
        $userLokasiSamsat = (string) ($user->lokasi_samsat ?? '');
        $userKecamatanSamsat = (string) ($user->kecamatan_samsat ?: $user->kecamatan ?: '');
        $userKelurahanSamsat = (string) ($user->kelurahan_samsat ?: $user->kelurahan ?: '');

        $cacheKey = $this->readableDashboardStatsCacheKey($request);

        $data = ApiCacheManager::remember($cacheKey, ApiCacheManager::dashboardTtl(), function () use ($request) {
            // ===== Regular (data_tertagih + seng_pendataan_kendaraan) =====
            $dataTertagihQuery = DataTertagih::query();
            $this->applyDashboardFiltersToDataTertagihQuery($dataTertagihQuery, $request);

            $verifikasis = SengPendataanKendaraan::query();
            $this->applyDashboardFiltersToQuery($verifikasis, $request);

            // ===== D2D (data_tertagih_d2d + seng_pendataan_kendaraan_d2d) =====
            // Skema kolom identik dengan tabel regular, jadi filter helper yang sama bisa dipakai ulang.
            $dataTertagihD2dQuery = DataTertagihD2d::query();
            $this->applyDashboardFiltersToDataTertagihQuery($dataTertagihD2dQuery, $request);

            $verifikasisD2d = SengPendataanKendaraanD2d::query();
            $this->applyDashboardFiltersToQuery($verifikasisD2d, $request);

            return [
                'jumlah_tunggakan' => (clone $dataTertagihQuery)->count(),
                'jumlah_sudah_pendataan' => (clone $dataTertagihQuery)->where('is_terdata', 1)->count(),
                'jumlah_belum_pendataan' => (clone $dataTertagihQuery)->where('is_terdata', 0)->count(),
                'menunggu_verifikasi' => (clone $verifikasis)->where('status_verifikasi', 1)->count(),
                'verifikasi' => (clone $verifikasis)->where('status_verifikasi', 2)->count(),
                'ditolak' => (clone $verifikasis)->where('status_verifikasi', 3)->count(),

                // D2D counters
                'jumlah_tunggakan_d2d' => (clone $dataTertagihD2dQuery)->count(),
                'jumlah_sudah_pendataan_d2d' => (clone $dataTertagihD2dQuery)->where('is_terdata', 1)->count(),
                'jumlah_belum_pendataan_d2d' => (clone $dataTertagihD2dQuery)->where('is_terdata', 0)->count(),
                'menunggu_verifikasi_d2d' => (clone $verifikasisD2d)->where('status_verifikasi', 1)->count(),
                'verifikasi_d2d' => (clone $verifikasisD2d)->where('status_verifikasi', 2)->count(),
                'ditolak_d2d' => (clone $verifikasisD2d)->where('status_verifikasi', 3)->count(),
            ];
        });
    
        // Ambil data status verifikasi dan wilayah
        $statuss = ApiCacheManager::remember('admin:master:status:all', ApiCacheManager::masterTtl(), static function () {
            return SengStatus::all();
        });
        $kabkotas = ApiCacheManager::remember('admin:master:kabkota:all', ApiCacheManager::masterTtl(), static function () {
            return SengWilayah::where('id_up', 33)->get();
        });
        $samsats = ApiCacheManager::remember('admin:master:wilayah-samsat:all', ApiCacheManager::masterTtl(), static function () {
            return WilayahSamsat::select('id', 'nama', 'kabkota')->orderBy('nama')->get();
        });
    
        return view('backend.dashboard.index', compact(
            'kabkotas',
            'statuss',
            'data',
            'samsats',
            'isKecamatanScope',
            'isKelurahanScope',
            'isScopedKabkota',
            'userLokasiSamsat',
            'userKecamatanSamsat',
            'userKelurahanSamsat'
        ));
    }

    private function resolveKecamatanDagriValue(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $row = DB::table('wilayah_samsat_kec')
            ->select('kode_dagri')
            ->where('id_kecamatan', $value)
            ->first();

        return isset($row->kode_dagri) && $row->kode_dagri !== null && $row->kode_dagri !== ''
            ? (string) $row->kode_dagri
            : $value;
    }

    /**
     * Kunci cache statistik: hanya constraint yang benar-benar dipakai di query (mirror applyDashboardFiltersToQuery).
     * Tanpa "persona" per role — super-admin, admin prov, UPTD, dll. berbagi cache selama filter SQL sama.
     * Petugas (role 7) memakai API mobile; scope web tidak membedakan created_by.
     *
     * @return array<string, mixed>
     */
    private function canonicalDashboardStatsScope(Request $request): array
    {
        $user = Auth::user();
        $userKotaId = $user->kota ?? null;
        $userLokasiSamsat = $user->lokasi_samsat ?? null;
        $userKecamatanSamsat = $user->kecamatan_samsat ?: $user->kecamatan ?: null;
        $userKelurahanSamsat = $user->kelurahan_samsat ?: $user->kelurahan ?: null;
        $isKecamatanScope = $user && $user->hasRole('kecamatan');
        $isKelurahanScope = $user && $user->hasRole('kelurahan');
        $isKabkotaScope = $user && $user->hasRole('kabkota');
        $isUppdScope = $user && ($user->hasRole('uppd') || $user->hasRole('uptd'));
        $isAdminScope = $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin') || $user->hasRole('admin') || $user->hasRole('adminprov'));

        $scope = [
            'kota_dagri' => null,
            'kota_layanan' => null,
            'kec' => null,
            'desa' => null,
            'status_verifikasi' => null,
            'periode' => null,
        ];

        if ($isAdminScope) {
            if ($request->filled('kabkota_id')) {
                $scope['kota_dagri'] = (string) $request->kabkota_id;
            }
        } elseif ($isKabkotaScope || $isUppdScope || $isKecamatanScope || $isKelurahanScope) {
            if ($userKotaId !== null && $userKotaId !== '') {
                $scope['kota_dagri'] = (string) $userKotaId;
            }
        }

        if (!empty($userLokasiSamsat)) {
            $scope['kota_layanan'] = (string) $userLokasiSamsat;
        } elseif ($request->filled('lokasi_samsat')) {
            $scope['kota_layanan'] = (string) $request->lokasi_samsat;
        }

        if ($isKecamatanScope && !empty($userKecamatanSamsat)) {
            $scope['kec'] = (string) $this->resolveKecamatanDagriValue((string) $userKecamatanSamsat);
        } elseif ($request->filled('kecamatan_samsat')) {
            $scope['kec'] = (string) $this->resolveKecamatanDagriValue((string) $request->kecamatan_samsat);
        }
        if ($isKelurahanScope && !empty($userKelurahanSamsat)) {
            $scope['desa'] = (string) $userKelurahanSamsat;
        } elseif ($request->filled('kelurahan_samsat')) {
            $scope['desa'] = (string) $request->kelurahan_samsat;
        }
        if ($request->filled('status_verifikasi_id')) {
            $scope['status_verifikasi'] = (string) $request->status_verifikasi_id;
        }
        if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
            $scope['periode'] = [
                'start' => (string) $request->tanggal_start,
                'end' => (string) $request->tanggal_end,
            ];
        } else {
            $scope['year'] = (string) now()->year;
        }

        return $scope;
    }

    /**
     * Kunci cache ringkas untuk Kelola Cache, contoh:
     * admin:dashboard:stats:kabkota:3375-lokasisamsat:21-kec:2102-kel:2102006-statusverifikasi:1-start:2025-01-01-end:2025-12-31
     * Jika terlalu panjang, ditambah sufiks -h-{md5}.
     */
    private function readableDashboardStatsCacheKey(Request $request): string
    {
        $scope = $this->canonicalDashboardStatsScope($request);

        $safe = static function (?string $value): string {
            if ($value === null || $value === '') {
                return '';
            }

            return preg_replace('/[^a-zA-Z0-9._@-]/', '_', $value);
        };

        $pairs = [];
        if ($scope['kota_dagri'] !== null && $scope['kota_dagri'] !== '') {
            $pairs[] = 'kabkota:' . $safe((string) $scope['kota_dagri']);
        }
        if ($scope['kota_layanan'] !== null && $scope['kota_layanan'] !== '') {
            $pairs[] = 'lokasisamsat:' . $safe((string) $scope['kota_layanan']);
        }
        if ($scope['kec'] !== null && $scope['kec'] !== '') {
            $pairs[] = 'kec:' . $safe((string) $scope['kec']);
        }
        if ($scope['desa'] !== null && $scope['desa'] !== '') {
            $pairs[] = 'kel:' . $safe((string) $scope['desa']);
        }
        if ($scope['status_verifikasi'] !== null && $scope['status_verifikasi'] !== '') {
            $pairs[] = 'statusverifikasi:' . $safe((string) $scope['status_verifikasi']);
        }
        if ($scope['periode'] !== null) {
            $pairs[] = 'start:' . $safe((string) $scope['periode']['start']);
            $pairs[] = 'end:' . $safe((string) $scope['periode']['end']);
        }
        if (isset($scope['year']) && $scope['year'] !== null && $scope['year'] !== '') {
            $pairs[] = 'year:' . $safe((string) $scope['year']);
        }

        $body = count($pairs) > 0 ? implode('-', $pairs) : 'none';
        $prefix = 'admin:dashboard:stats:';
        $full = $prefix . $body;

        if (strlen($full) > 240) {
            return $prefix . substr($body, 0, 180) . '-h-' . md5(json_encode($scope));
        }

        return $full;
    }

    private function applyDashboardFiltersToQuery($verifikasis, Request $request): void
    {
        $userKotaId = Auth::user()->kota ?? null;
        $userLokasiSamsat = Auth::user()->lokasi_samsat ?? null;
        $userKecamatanSamsat = Auth::user()->kecamatan_samsat ?: Auth::user()->kecamatan ?: null;
        $userKelurahanSamsat = Auth::user()->kelurahan_samsat ?: Auth::user()->kelurahan ?: null;
        $isKecamatanScope = Auth::user()->hasRole('kecamatan');
        $isKelurahanScope = Auth::user()->hasRole('kelurahan');
        $isKabkotaScope = Auth::user()->hasRole('kabkota');
        $isUppdScope = Auth::user()->hasRole('uppd') || Auth::user()->hasRole('uptd');
        $isAdminScope = Auth::user()->hasRole('super-admin') || Auth::user()->hasRole('superadmin') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('adminprov');

        if ($isAdminScope) {
            if ($request->kabkota_id) {
                $verifikasis->where('kota_dagri', $request->kabkota_id);
            }
        } elseif ($isKabkotaScope || $isUppdScope || $isKecamatanScope || $isKelurahanScope) {
            $verifikasis->where('kota_dagri', $userKotaId);
        }

        if (!empty($userLokasiSamsat)) {
            $verifikasis->where('kota', $userLokasiSamsat);
        } elseif ($request->lokasi_samsat) {
            $verifikasis->where('kota', $request->lokasi_samsat);
        }

        if ($isKecamatanScope && !empty($userKecamatanSamsat)) {
            $verifikasis->where('kec_dagri', $this->resolveKecamatanDagriValue((string) $userKecamatanSamsat));
        } elseif ($request->kecamatan_samsat) {
            $verifikasis->where('kec_dagri', $this->resolveKecamatanDagriValue((string) $request->kecamatan_samsat));
        }
        if ($isKelurahanScope && !empty($userKelurahanSamsat)) {
            $verifikasis->where('desa', $userKelurahanSamsat);
        } elseif ($request->kelurahan_samsat) {
            $verifikasis->where('desa', $request->kelurahan_samsat);
        }
        if ($request->status_verifikasi_id) {
            $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
        }
        if ($request->tanggal_start && $request->tanggal_end) {
            $verifikasis->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }
    }

    private function applyDashboardFiltersToDataTertagihQuery($query, Request $request): void
    {
        $user = Auth::user();
        $userKotaId = $user->kota ?? null;
        $userLokasiSamsat = $user->lokasi_samsat ?? null;
        $userKecamatanSamsat = $user->kecamatan_samsat ?: $user->kecamatan ?: null;
        $userKelurahanSamsat = $user->kelurahan_samsat ?: $user->kelurahan ?: null;
        $isKecamatanScope = $user->hasRole('kecamatan');
        $isKelurahanScope = $user->hasRole('kelurahan');
        $isKabkotaScope = $user->hasRole('kabkota');
        $isUppdScope = $user->hasRole('uppd') || $user->hasRole('uptd');
        $isAdminScope = $user->hasRole('super-admin') || $user->hasRole('superadmin') || $user->hasRole('admin') || $user->hasRole('adminprov');

        if ($request->tanggal_start && $request->tanggal_end) {
            $query->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        } else {
            $query->where('year', (int) now()->year);
        }

        $kabkotaId = null;
        if ($isAdminScope) {
            if ($request->kabkota_id) {
                $kabkotaId = (string) $request->kabkota_id;
            }
        } elseif ($isKabkotaScope || $isUppdScope || $isKecamatanScope || $isKelurahanScope) {
            if ($userKotaId !== null && $userKotaId !== '') {
                $kabkotaId = (string) $userKotaId;
            }
        }

        if ($kabkotaId !== null) {
            $lokasiKabkota = SengSaamsat::query()
                ->where('kabkota', $kabkotaId)
                ->pluck('id_wilayah_samsat')
                ->filter()
                ->flatMap(fn ($id) => $this->samsatCodeVariants((string) $id))
                ->unique()
                ->values()
                ->all();

            if (!empty($lokasiKabkota)) {
                $query->whereIn('id_lokasi_samsat', $lokasiKabkota);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (!empty($userLokasiSamsat)) {
            $query->whereIn('id_lokasi_samsat', $this->samsatCodeVariants((string) $userLokasiSamsat));
        } elseif ($request->lokasi_samsat) {
            $query->whereIn('id_lokasi_samsat', $this->samsatCodeVariants((string) $request->lokasi_samsat));
        }

        if ($isKecamatanScope && !empty($userKecamatanSamsat)) {
            $query->whereIn('id_kecamatan', $this->samsatCodeVariants((string) $userKecamatanSamsat));
        } elseif ($request->kecamatan_samsat) {
            $query->whereIn('id_kecamatan', $this->samsatCodeVariants((string) $request->kecamatan_samsat));
        }

        if ($isKelurahanScope && !empty($userKelurahanSamsat)) {
            $query->whereIn('id_kelurahan', $this->samsatCodeVariants((string) $userKelurahanSamsat));
        } elseif ($request->kelurahan_samsat) {
            $query->whereIn('id_kelurahan', $this->samsatCodeVariants((string) $request->kelurahan_samsat));
        }
    }

    /**
     * @return list<string>
     */
    private function samsatCodeVariants(?string $value): array
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

    public function download () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.download');
        }
    }

    public function verifikasi () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.verifikasi');
        }
    }

    public function verifikasi_detail () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.verifikasi-detail');
        }
    }

    public function pelaporan () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.pelaporan');
        }
    }

}