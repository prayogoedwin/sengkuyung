<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SengWilayah;
use App\Models\SengSaamsat;
use App\Models\SengWilayahKec;
use App\Models\SengWilayahKel;

class WilayahController extends Controller
{
    public function getDistricts(Request $request)
    {
        $kabkotaId = $request->input('kabkota_id');

        if (!$kabkotaId) {
            return response()->json(['success' => false, 'message' => 'Kabkota ID is required.'], 400);
        }

        $districts = SengWilayah::where('id_up', $kabkotaId)->get();

        return response()->json(['success' => true, 'districts' => $districts]);
    }

    public function getSamsatByKabkota(Request $request)
    {
        $kabkotaId = $request->input('kabkota_id');

        if (!$kabkotaId) {
            return response()->json(['success' => false, 'message' => 'Kabkota ID is required.'], 400);
        }

        $samsats = SengSaamsat::where('kabkota', $kabkotaId)
            ->orderBy('lokasi')
            ->get(['id', 'lokasi']);

        return response()->json(['success' => true, 'samsats' => $samsats]);
    }

    public function getSamsatKecamatan(Request $request)
    {
        $lokasiSamsatId = $request->input('lokasi_samsat_id');

        if (!$lokasiSamsatId) {
            return response()->json(['success' => false, 'message' => 'Lokasi Samsat ID is required.'], 400);
        }

        $kecamatans = SengWilayahKec::where('id_lokasi_samsat', $lokasiSamsatId)
            ->orderBy('kecamatan')
            ->get(['id_kecamatan', 'kecamatan']);

        return response()->json(['success' => true, 'kecamatans' => $kecamatans]);
    }

    public function getSamsatKelurahan(Request $request)
    {
        $kecamatanId = $request->input('kecamatan_samsat_id');

        if (!$kecamatanId) {
            return response()->json(['success' => false, 'message' => 'Kecamatan Samsat ID is required.'], 400);
        }

        $kelurahans = SengWilayahKel::where('id_kecamatan', $kecamatanId)
            ->orderBy('kelurahan')
            ->get(['id_kelurahan', 'kelurahan']);

        return response()->json(['success' => true, 'kelurahans' => $kelurahans]);
    }
}
