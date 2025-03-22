<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\SengStatus;
use App\Models\SengStatusVerifikasi;
use App\Models\SengStatusFile;
use App\Models\SengWilayah;
use App\Models\SengPendataanKendaraan;

class BackController extends Controller
{

    public function index(Request $request)
    {
        $userRole = auth()->user()->role;
        $user = User::findOrFail(auth()->id());
    
        // Query awal
        $verifikasis = SengPendataanKendaraan::query();
    
        // Apply filters based on user role
        if ($userRole == 1 || $userRole == 2) {
            // No additional WHERE clause for roles 1 and 2
        } elseif ($userRole == 4) {
            $verifikasis->where('kota', $user->kota);
        } elseif ($userRole == 7) {
            $verifikasis->where('created_by', auth()->id());
        }
    
        // Filter berdasarkan input dari form
        // if ($request->status_verifikasi_id) {
        //     $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
        // }

        if ($request->kabkota_id) {
            $verifikasis->where('kota', $request->kabkota_id);
        }
        if ($request->district_id) {
            $verifikasis->where('kec', $request->district_id);
        }
        if ($request->tanggal_start && $request->tanggal_end) {
            $verifikasis->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }
    
        // Hitung jumlah total data dan berdasarkan status_verifikasi
        $total = (clone $verifikasis)->count();
        $menunggu_verifikasi = (clone $verifikasis)->where('status_verifikasi', 1)->count();
        $verifikasi = (clone $verifikasis)->where('status_verifikasi', 2)->count();
        $ditolak = (clone $verifikasis)->where('status_verifikasi', 3)->count();
    
        // Simpan data statistik
        $data = [
            'total' => $total,
            'menunggu_verifikasi' => $menunggu_verifikasi,
            'verifikasi' => $verifikasi,
            'ditolak' => $ditolak
        ];
    
        // Ambil data status verifikasi dan wilayah
        $statuss = SengStatus::all();
        $kabkotas = SengWilayah::where('id_up', 33)->get();
    
        return view('backend.dashboard.index', compact('kabkotas', 'statuss', 'data'));
    }


    public function download () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.download');
        }
    }

    public function verifikasi () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.verifikasi');
        }
    }

    public function verifikasi_detail () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.verifikasi-detail');
        }
    }

    public function pelaporan () {
        if(Auth::user()->roles[0]['name'] == 'super-admin'){
            return view('backend.sample.pelaporan');
        }
    }

}