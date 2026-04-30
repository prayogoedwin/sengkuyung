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
use App\Models\WilayahSamsat;


class BackController extends Controller
{

    public function index(Request $request)
    {
        // $userRole = auth()->user()->role;
        // $user = User::findOrFail(auth()->id());

        $userId = Auth::user()->id ?? null;
        $userRoleId = Auth::user()->roles[0]->id ?? null;
        $userKotaId = Auth::user()->kota ?? null;
        $userLokasiSamsat = Auth::user()->lokasi_samsat ?? null;

        // Query awal
        $verifikasis = SengPendataanKendaraan::query();
    
        // Apply filters based on user role
        // if ($userRoleId == 1 || $userRoleId == 2) {
        //     // No additional WHERE clause for roles 1 and 2
        // } elseif ($userRoleId == 4 || $userRoleId == 4) {
        //     $verifikasis->where('kota', $user->kota);
        // } elseif ($userRoleId == 7) {
        //     $verifikasis->where('created_by', auth()->id());
        // }

        if ($userRoleId == 1 || $userRoleId == 2) {
            // No additional WHERE clause for roles 1 and 2
            if ($request->kabkota_id) {
                $verifikasis->where('kota_dagri', $request->kabkota_id);
            }
        } elseif ($userRoleId == 4 || $userRoleId == 3) {
            // Add WHERE clause for role 4
            $verifikasis->where('kota_dagri', $userKotaId);

        } elseif ($userRoleId == 7) {
            // Add WHERE clause for role 7
            $verifikasis->where('created_by', $userId);
        }

        // Jika akun punya lokasi_samsat, paksa hanya data lokasi samsat tersebut.
        if (!empty($userLokasiSamsat)) {
            $verifikasis->where('kota', $userLokasiSamsat);
        } elseif ($request->lokasi_samsat) {
            $verifikasis->where('kota', $request->lokasi_samsat);
        }
    
        // Filter berdasarkan input dari form
        // if ($request->status_verifikasi_id) {
        //     $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
        // }

        // if ($request->kabkota_id) {
        //     $verifikasis->where('kota', $request->kabkota_id);
        // }

        if ($request->district_id) {
            $verifikasis->where('kec', $request->district_id);
        }
        if ($request->status_verifikasi_id) {
            $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
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
        $samsats = WilayahSamsat::select('id', 'nama', 'kabkota')->orderBy('nama')->get();
    
        return view('backend.dashboard.index', compact('kabkotas', 'statuss', 'data', 'samsats'));
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