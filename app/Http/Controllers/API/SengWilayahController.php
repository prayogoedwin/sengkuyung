<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SengWilayah;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\Helper;
use App\Support\ApiCacheManager;

class SengWilayahController extends Controller
{
    public function index(Request $request)
    {
        $keterangan = 'Semua wilayah';
        $kode = (string) $request->input('kode', '');

        if ($kode !== '') {
            $info = SengWilayah::find($kode);
            $keterangan = $info?->nama ?? 'Wilayah tidak ditemukan';
        }

        $cacheSuffix = $kode !== '' ? $kode : 'root';
        $cacheKey = 'api:master:wilayah:index:' . $cacheSuffix;
        $data = ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () use ($kode) {
            $query = SengWilayah::query();

            if ($kode !== '') {
                $query->where('id_up', $kode);
            }

            return $query->get();
        });

        // Ubah menjadi array agar tidak mengganggu objek asli
        $data = $data->map(function ($item) {
            return [
                // 'id' => Helper::encodeId($item->id),
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'id_up' => $item->id_up,
                'kode_samsat' => $item->kode_samsat,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'List data ditemukan: '.$keterangan,
            'data' => $data
        ]);
    }
}
