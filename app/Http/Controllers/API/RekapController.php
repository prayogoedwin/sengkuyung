<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SengPendataanKendaraan;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Helper;

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

        $pkb= (clone $verifikasis)->sum('pkb_pokok');
        // $pkb_denda= (clone $verifikasis)->sum('pkb_denda');
        // $pnbp = (clone $verifikasis)->sum('pnbp');
        // // $pnbp_denda = (clone $verifikasis)->sum('pnbp_denda');
        // $jr = (clone $verifikasis)->sum('jr');
        // $jr_denda = (clone $verifikasis)->sum('jr_denda');
    
        // Simpan data statistik
        $data = [
            'total' => $total,
            'menunggu_verifikasi' => $menunggu_verifikasi,
            'verifikasi' => $verifikasi,
            'ditolak' => $ditolak,
            'pkb' => $pkb,
            // 'pkb_denda'=>$pkb_denda,
            // 'pnbp' => $pnbp,
            // 'jr' => $jr,
            // 'jr_denda' => $jr_denda,
        ];
    
        return response()->json([
            'status' => true,
            'message' => 'Data ditemukan',
            'data' => $data
        ]);
    }

    public function jurnalPreview(Request $request)
    {
        // $user = Auth::user();
        $user = decodeId($request->petugas);

        $verifikasis = SengPendataanKendaraan::query();

        // Apply filters based on user role
        $verifikasis->where('created_by',  $user);

        if ($request->status_verifikasi_id) {
            $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
        }

        if ($request->kabkota_id) {
            $verifikasis->where('kota', $request->kabkota_id);
        }

        if ($request->district_id) {
            $verifikasis->where('kec', $request->district_id);
        }

        if ($request->tanggal_start && $request->tanggal_end) {
            $verifikasis->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }
        

        $data = $verifikasis->get();

        return view('backend.rekap.jurnal_mobile', compact('data', 'request'));
    }

    public function rekapPreview(Request $request)
    {
        $user = decodeId($request->petugas);

        $verifikasis = SengPendataanKendaraan::query();

        // Apply filters based on user role
        $verifikasis->where('created_by',  $user);

        if ($request->status_verifikasi_id) {
            $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
        }

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

        // $data = $verifikasis->get();
        $data = [
            'total' => $total,
            'menunggu_verifikasi' => $menunggu_verifikasi,
            'verifikasi' => $verifikasi,
            'ditolak' => $ditolak,
            // 'pkb' => $pkb,
            // 'pkb_denda'=>$pkb_denda,
            // 'pnbp' => $pnbp,
            // 'jr' => $jr,
            // 'jr_denda' => $jr_denda,
        ];

        return view('backend.rekap.rekap_mobile', compact('data', 'request'));
    }
    
}
