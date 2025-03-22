<?php

namespace App\Http\Controllers;
use App\Models\SengPendataanKendaraan;
use App\Models\SengStatus;
use App\Models\SengStatusVerifikasi;
use App\Models\SengStatusFile;
use App\Models\SengWilayah;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    public function index(Request $request)
    {

        $status = SengStatus::select('*')->get();

        $status_verifikasis = SengStatusVerifikasi::select('*')->get();

        $kabkotas = SengWilayah::select('*')
        ->where('id_up', 33)
        ->get();

        return view('backend.rekap.index',  compact('kabkotas','status','status_verifikasis'));

    }
}
