<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Support\ApiCacheManager;
use Illuminate\Http\JsonResponse;

class CekVersiController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $cacheKey = 'api:cek-versi:' . $id;

        $version = ApiCacheManager::remember($cacheKey, 86400, static function () use ($id) {
            return AppVersion::query()->find($id);
        });

        if (!$version) {
            return response()->json([
                'status' => false,
                'message' => 'Data versi tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data versi ditemukan.',
            'data' => [
                'id' => $version->id,
                'nama_aplikasi' => $version->nama_aplikasi,
                'versi' => $version->versi,
                'alias' => $version->alias,
            ],
            'cache_ttl_seconds' => 86400,
        ]);
    }
}
