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
     * Tanpa "persona" per role — super-admin, admin prov, UPTD, dll. berbagi cache selama filter SQL sama.
     * Petugas (role 7) memakai API mobile; scope web tidak membedakan created_by.
     *
     * @return array<string, mixed>
     */
    private function canonicalDashboardStatsScope(Request $request): array
    {
        $user = Auth::user();
        $userRoleId = $user->roles[0]->id ?? null;
        $userKotaId = $user->kota ?? null;
        $userLokasiSamsat = $user->lokasi_samsat ?? null;

        $scope = [
            'kota_dagri' => null,
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
        $userRoleId = Auth::user()->roles[0]->id ?? null;
        $userKotaId = Auth::user()->kota ?? null;
        $userLokasiSamsat = Auth::user()->lokasi_samsat ?? null;

        if ($userRoleId == 1 || $userRoleId == 2) {
            if ($request->kabkota_id) {
                $verifikasis->where('kota_dagri', $request->kabkota_id);
            }
        } elseif ($userRoleId == 4 || $userRoleId == 3) {
            $verifikasis->where('kota_dagri', $userKotaId);
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