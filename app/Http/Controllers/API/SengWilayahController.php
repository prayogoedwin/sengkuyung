<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SengWilayah;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\Helper;

class SengWilayahController extends Controller
{
    public function index(Request $request)
    {

        if ($request->has('kode') && !empty($request->kode)) {
            
            $info = SengWilayah::find($request->kode)->nama;
            $keterangan = $info;

        }

        $query = SengWilayah::query();

        // Cek apakah parameter kode ada dan tidak kosong
        if ($request->has('kode') && !empty($request->kode)) {
            $query->where('id_up', $request->kode);
        }

        $data = $query->get();

        // Ubah menjadi array agar tidak mengganggu objek asli
        $data = $data->map(function ($item) {
            return [
                // 'id' => Helper::encodeId($item->id),
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'id_up' => $item->id_up,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'List data ditemukan: '.$keterangan,
            'data' => $data
        ]);
    }
}
