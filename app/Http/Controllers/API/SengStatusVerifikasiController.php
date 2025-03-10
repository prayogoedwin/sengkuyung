<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SengStatusVerifikasi;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\Helper;

class SengStatusVerifikasiController extends Controller
{
    public function index(Request $request)
    {
        $data = SengStatusVerifikasi::all();
    
        // Ubah menjadi array agar tidak mengganggu objek asli
        $data = $data->map(function ($item) {
            return [
                'id' => Helper::encodeId($item->id),
                'nama' => $item->nama, // Sesuaikan dengan field lain yang ada
                // Tambahkan field lain yang diperlukan
            ];
        });
    
        return response()->json([
            'status' => true,
            'message' => 'List data ditemukan',
            'data' => $data // Data hasil pagination
        ]);
    }
}
