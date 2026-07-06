<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SengStatusVerifikasi;
use App\Support\ApiCacheManager;

class JrMasterController extends Controller
{
    public function statusVerifikasi()
    {
        $data = ApiCacheManager::remember(
            'api:jr:master:status-verifikasi:all',
            ApiCacheManager::masterTtl(),
            static fn () => SengStatusVerifikasi::query()->orderBy('id')->get(['id', 'nama', 'keterangan'])
        );

        return response()->json([
            'status' => true,
            'message' => 'Master status verifikasi ditemukan',
            'data' => $data,
        ]);
    }
}
