<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SengStatus;
use App\Models\SengWilayah;
use App\Models\SengPendataanKendaraan;
use App\Models\WilayahSamsat;
use App\Support\ApiCacheManager;


class BackController extends Controller
{

    public function index(Request $request)
    {
        $cacheKey = $this->readableDashboardStatsCacheKey($request);

        $data = ApiCacheManager::remember($cacheKey, ApiCacheManager::dashboardTtl(), function () use ($request) {
            $verifikasis = SengPendataanKendaraan::query();
            $this->applyDashboardFiltersToQuery($verifikasis, $request);

            $total = (clone $verifikasis)->count();
            $menunggu_verifikasi = (clone $verifikasis)->where('status_verifikasi', 1)->count();
            $verifikasi = (clone $verifikasis)->where('status_verifikasi', 2)->count();
            $ditolak = (clone $verifikasis)->where('status_verifikasi', 3)->count();

            return [
                'total' => $total,
                'menunggu_verifikasi' => $menunggu_verifikasi,
                'verifikasi' => $verifikasi,
                'ditolak' => $ditolak,
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
    
        return view('backend.dashboard.index', compact('kabkotas', 'statuss', 'data', 'samsats'));
    }

    /**
     * Kunci cache statistik: hanya constraint yang benar-benar dipakai di query (mirror applyDashboardFiltersToQuery).
     * Tanpa "persona" per role — super-admin, admin prov, kabkota, kecamatan, dll. berbagi cache
     * selama kombinasi filter SQL-nya sama (role 7 tetap terpisah lewat created_by).
     *
     * @return array<string, mixed>
     */
    private function canonicalDashboardStatsScope(Request $request): array
    {
        $user = Auth::user();
        $userRoleId = $user->roles[0]->id ?? null;
        $userId = $user->id;
        $userKotaId = $user->kota ?? null;
        $userLokasiSamsat = $user->lokasi_samsat ?? null;

        $scope = [
            'kota_dagri' => null,
            'created_by' => null,
            'kota_layanan' => null,
            'kec' => null,
            'desa' => null,
            'status_verifikasi' => null,
            'periode' => null,
        ];

        if ($userRoleId == 1 || $userRoleId == 2) {
            if ($request->filled('kabkota_id')) {
                $scope['kota_dagri'] = (string) $request->kabkota_id;
            }
        } elseif ($userRoleId == 4 || $userRoleId == 3) {
            if ($userKotaId !== null && $userKotaId !== '') {
                $scope['kota_dagri'] = (string) $userKotaId;
            }
        } elseif ($userRoleId == 7) {
            $scope['created_by'] = (int) $userId;
        }

        if (!empty($userLokasiSamsat)) {
            $scope['kota_layanan'] = (string) $userLokasiSamsat;
        } elseif ($request->filled('lokasi_samsat')) {
            $scope['kota_layanan'] = (string) $request->lokasi_samsat;
        }

        if ($request->filled('kecamatan_samsat')) {
            $scope['kec'] = (string) $request->kecamatan_samsat;
        }
        if ($request->filled('kelurahan_samsat')) {
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
        }

        return $scope;
    }

    /**
     * Kunci cache yang bisa dibaca di menu Kelola Cache: berisi nilai param yang dipakai query
     * (bukan hash saja). Jika kombinasi sangat panjang, ditambah sufiks __h_{md5} supaya tetap unik.
     */
    private function readableDashboardStatsCacheKey(Request $request): string
    {
        $scope = $this->canonicalDashboardStatsScope($request);

        $parts = [];
        $seg = static function (string $label, string $value): string {
            $safe = preg_replace('/[^a-zA-Z0-9._@-]/', '_', $value);

            return $label . '_' . $safe;
        };

        if ($scope['kota_dagri'] !== null && $scope['kota_dagri'] !== '') {
            $parts[] = $seg('kabkota', (string) $scope['kota_dagri']);
        }
        if ($scope['created_by'] !== null) {
            $parts[] = 'petugas_user_' . (int) $scope['created_by'];
        }
        if ($scope['kota_layanan'] !== null && $scope['kota_layanan'] !== '') {
            $parts[] = $seg('lokasi_samsat', (string) $scope['kota_layanan']);
        }
        if ($scope['kec'] !== null && $scope['kec'] !== '') {
            $parts[] = $seg('kecamatan_samsat', (string) $scope['kec']);
        }
        if ($scope['desa'] !== null && $scope['desa'] !== '') {
            $parts[] = $seg('kelurahan_samsat', (string) $scope['desa']);
        }
        if ($scope['status_verifikasi'] !== null && $scope['status_verifikasi'] !== '') {
            $parts[] = $seg('status_verifikasi', (string) $scope['status_verifikasi']);
        }
        if ($scope['periode'] !== null) {
            $start = (string) $scope['periode']['start'];
            $end = (string) $scope['periode']['end'];
            $parts[] = 'tanggal_' . preg_replace('/[^0-9-]/', '_', $start) . '_sampai_' . preg_replace('/[^0-9-]/', '_', $end);
        }

        $body = count($parts) > 0 ? implode('__', $parts) : 'tanpa_filter';
        $prefix = 'admin:dashboard:stats:';
        $full = $prefix . $body;

        if (strlen($full) > 220) {
            return $prefix . substr($body, 0, 160) . '__h_' . md5(json_encode($scope));
        }

        return $full;
    }

    private function applyDashboardFiltersToQuery($verifikasis, Request $request): void
    {
        $userId = Auth::user()->id ?? null;
        $userRoleId = Auth::user()->roles[0]->id ?? null;
        $userKotaId = Auth::user()->kota ?? null;
        $userLokasiSamsat = Auth::user()->lokasi_samsat ?? null;

        if ($userRoleId == 1 || $userRoleId == 2) {
            if ($request->kabkota_id) {
                $verifikasis->where('kota_dagri', $request->kabkota_id);
            }
        } elseif ($userRoleId == 4 || $userRoleId == 3) {
            $verifikasis->where('kota_dagri', $userKotaId);
        } elseif ($userRoleId == 7) {
            $verifikasis->where('created_by', $userId);
        }

        if (!empty($userLokasiSamsat)) {
            $verifikasis->where('kota', $userLokasiSamsat);
        } elseif ($request->lokasi_samsat) {
            $verifikasis->where('kota', $request->lokasi_samsat);
        }

        if ($request->kecamatan_samsat) {
            $verifikasis->where('kec', $request->kecamatan_samsat);
        }
        if ($request->kelurahan_samsat) {
            $verifikasis->where('desa', $request->kelurahan_samsat);
        }
        if ($request->status_verifikasi_id) {
            $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
        }
        if ($request->tanggal_start && $request->tanggal_end) {
            $verifikasis->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }
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