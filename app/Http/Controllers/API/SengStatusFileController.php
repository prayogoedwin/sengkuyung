<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SengStatusFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\Helper;
use App\Support\ApiCacheManager;

class SengStatusFileController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->input('status', '');
        $cacheSuffix = $status !== '' ? $status : 'all';
        $cacheKey = 'api:master:status-file:index:' . $cacheSuffix;

        $data = ApiCacheManager::remember($cacheKey, ApiCacheManager::masterTtl(), static function () use ($status) {
            $query = SengStatusFile::query();

            if ($status !== '') {
                $decodedStatus = Helper::decodeId($status);
                $query->where('id_status', $decodedStatus);
            }

            return $query->get();
        });

        // Ubah menjadi array agar tidak mengganggu objek asli
        $data = $data->map(function ($item) {
            return [
                // 'id' => Helper::encodeId($item->id),
                // 'id_status' => Helper::encodeId($item->id_status),
                'nama_file' => $item->nama_file,
                'type_file' => $item->type_file,
                'ukuran_file' => $item->ukuran_file,
                'keterangan_file' => $item->keterangan_file,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'List data ditemukan',
            'data' => $data
        ]);
    }
}
