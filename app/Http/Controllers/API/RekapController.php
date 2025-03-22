<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SengPendataanKendaraan;
use Illuminate\Support\Facades\Auth;

class RekapController extends Controller
{
    public function index(Request $request)
    {

        $user = Auth::user();

        // Query awal
        $verifikasis = SengPendataanKendaraan::query();
    
        // Apply filters based on user role
        $verifikasis->where('created_by',  $user->id);

        // Filter berdasarkan input dari request
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
    
        return response()->json([
            'status' => true,
            'message' => 'Data ditemukan',
            'data' => $data
        ]);
    }
    
}
