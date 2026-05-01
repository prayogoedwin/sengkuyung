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
        $districts = ApiCacheManager::remember($cacheKey, ApiCacheManager::DEFAULT_TTL_SECONDS, static function () use ($kabkotaId) {
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

        $cacheKey = 'admin:master:wilayah:samsat-by-kabkota:' . (string) $kabkotaId;
        $samsats = ApiCacheManager::remember($cacheKey, ApiCacheManager::DEFAULT_TTL_SECONDS, static function () use ($kabkotaId) {
            return SengSaamsat::where('kabkota', $kabkotaId)
                ->orderBy('lokasi')
                ->get(['id', 'id_wilayah_samsat', 'lokasi']);
        });

        return response()->json(['success' => true, 'samsats' => $samsats]);
    }

    public function getSamsatKecamatan(Request $request)
    {
        $lokasiSamsatId = $request->input('lokasi_samsat_id');

        if (!$lokasiSamsatId) {
            return response()->json(['success' => false, 'message' => 'Lokasi Samsat ID is required.'], 400);
        }

        $cacheKey = 'admin:master:wilayah:kecamatan-by-samsat:' . (string) $lokasiSamsatId;
        $kecamatans = ApiCacheManager::remember($cacheKey, ApiCacheManager::DEFAULT_TTL_SECONDS, static function () use ($lokasiSamsatId) {
            return SengWilayahKec::where(function ($query) use ($lokasiSamsatId) {
                    $query->where('id_lokasi_samsat', $lokasiSamsatId);

                    if (is_numeric($lokasiSamsatId)) {
                        $query->orWhereRaw('CAST(id_lokasi_samsat AS UNSIGNED) = ?', [(int) $lokasiSamsatId]);
                    }
                })
                ->orderBy('kecamatan')
                ->get(['id_kecamatan', 'kecamatan']);
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
        $kelurahans = ApiCacheManager::remember($cacheKey, ApiCacheManager::DEFAULT_TTL_SECONDS, static function () use ($kecamatanId) {
            return SengWilayahKel::where('id_kecamatan', $kecamatanId)
                ->orderBy('kelurahan')
                ->get(['id_kelurahan', 'kelurahan']);
        });

        return response()->json(['success' => true, 'kelurahans' => $kelurahans]);
    }
}
