<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SengWilayah;
use App\Models\SengSaamsat;
use App\Models\SengWilayahKec;
use App\Models\SengWilayahKel;
use App\Support\ApiCacheManager;

class WilayahController extends Controller
{
    public function getDistricts(Request $request)
    {
        $kabkotaId = $request->input('kabkota_id');

        if (!$kabkotaId) {
            return response()->json(['success' => false, 'message' => 'Kabkota ID is required.'], 400);
        }

        $cacheKey = 'admin:master:wilayah:districts-by-kabkota:' . (string) $kabkotaId;
        $districts = ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () use ($kabkotaId) {
            return SengWilayah::where('id_up', $kabkotaId)->get();
        });

        return response()->json(['success' => true, 'districts' => $districts]);
    }

    public function getSamsatByKabkota(Request $request)
    {
        $kabkotaId = $request->input('kabkota_id');

        if (!$kabkotaId) {
            return response()->json(['success' => false, 'message' => 'Kabkota ID is required.'], 400);
        }

        $cacheKey = 'admin:master:wilayah:samsat-by-kabkota:v3:' . (string) $kabkotaId;
        $samsats = ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () use ($kabkotaId) {
            return SengSaamsat::where('kabkota', $kabkotaId)
                ->orderBy('lokasi')
                ->get(['id', 'id_wilayah_samsat', 'lokasi']);
        });

        return response()->json(['success' => true, 'samsats' => $samsats]);
    }

    public function getSamsatKecamatan(Request $request)
    {
        $kabkotaId = $request->input('kabkota_id');
        $lokasiSamsatId = $request->input('lokasi_samsat_id');

        if ($kabkotaId) {
            $lokasiIds = SengSaamsat::lokasiFilterVariantsByKabkota((string) $kabkotaId);
            $cacheKey = 'admin:master:wilayah:kecamatan-by-kabkota:v2:' . (string) $kabkotaId;
        } elseif ($lokasiSamsatId) {
            $lokasiIds = SengSaamsat::lokasiFilterVariants((string) $lokasiSamsatId);
            $cacheKey = 'admin:master:wilayah:kecamatan-by-samsat:v2:' . (string) $lokasiSamsatId;
        } else {
            return response()->json(['success' => false, 'message' => 'Kabkota ID or Lokasi Samsat ID is required.'], 400);
        }

        if (empty($lokasiIds)) {
            return response()->json(['success' => true, 'kecamatans' => []]);
        }

        $kecamatans = ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () use ($lokasiIds) {
            return SengWilayahKec::whereIn('id_lokasi_samsat', $lokasiIds)
                ->orderBy('kecamatan')
                ->get(['id_kecamatan', 'kecamatan'])
                ->unique('id_kecamatan')
                ->values();
        });

        return response()->json(['success' => true, 'kecamatans' => $kecamatans]);
    }

    public function getSamsatKelurahan(Request $request)
    {
        $kecamatanId = $request->input('kecamatan_samsat_id');

        if (!$kecamatanId) {
            return response()->json(['success' => false, 'message' => 'Kecamatan Samsat ID is required.'], 400);
        }

        $cacheKey = 'admin:master:wilayah:kelurahan-by-kecamatan:' . (string) $kecamatanId;
        $kelurahans = ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () use ($kecamatanId) {
            return SengWilayahKel::where('id_kecamatan', $kecamatanId)
                ->orderBy('kelurahan')
                ->get(['id_kelurahan', 'kelurahan']);
        });

        return response()->json(['success' => true, 'kelurahans' => $kelurahans]);
    }
}
