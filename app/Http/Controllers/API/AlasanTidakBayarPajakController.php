<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Support\ApiCacheManager;

class AlasanTidakBayarPajakController extends Controller
{
    /**
     * Master: alasan tidak membayar pajak (untuk verifikasi).
     */
    public function index()
    {
        $cacheKey = 'api:master:alasan-tidak-bayar-pajak:index';

        $data = ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () {
            $items = [];
            foreach (Helper::getAlasanTidakBayarPajak() as $id => $nama) {
                $items[] = [
                    'id' => Helper::encodeId($id),
                    'nama' => $nama,
                ];
            }

            return $items;
        });

        return response()->json([
            'status' => true,
            'message' => 'List data ditemukan',
            'data' => $data,
        ]);
    }
}
