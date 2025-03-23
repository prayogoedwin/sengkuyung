<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\SengPendataanKendaraan;
use App\Models\SengStatus;
use App\Models\SengStatusVerifikasi;
use App\Models\SengStatusFile;
use App\Models\SengWilayah;
use App\Models\User;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    public function index_(Request $request)
    {

        $status = SengStatus::select('*')->get();

        $status_verifikasis = SengStatusVerifikasi::select('*')->get();

        $kabkotas = SengWilayah::select('*')
        ->where('id_up', 33)
        ->get();

        return view('backend.rekap.index',  compact('kabkotas','status','status_verifikasis'));

    }

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
        // $menunggu_verifikasi = (clone $verifikasis)->where('status_verifikasi', 1)->count();
        // $verifikasi = (clone $verifikasis)->where('status_verifikasi', 2)->count();
        // $ditolak = (clone $verifikasis)->where('status_verifikasi', 3)->count();
        // $sumMenungguVerifikasi = (clone $verifikasis)->where('status_verifikasi', 1)->sum('pkb_pokok');
        $pkb= (clone $verifikasis)->sum('pkb_pokok');
        $pkb_denda= (clone $verifikasis)->sum('pkb_denda');
        $pnbp = (clone $verifikasis)->sum('pnbp');
        // $pnbp_denda = (clone $verifikasis)->sum('pnbp_denda');
        $jr = (clone $verifikasis)->sum('jr');
        $jr_denda = (clone $verifikasis)->sum('jr_denda');
    
        // Simpan data statistik
        $data = [
            'total' => $total,
            'pkb' => $pkb,
            'pkb_denda'=>$pkb_denda,
            'pnbp' => $pnbp,
            // 'pnbp_denda' => $pnbp_denda,
            'jr' => $jr,
            'jr_denda' => $jr_denda
        ];
    
        // Ambil data status verifikasi dan wilayah
        $statuss = SengStatus::all();
        $kabkotas = SengWilayah::where('id_up', 33)->get();
        $status_verifikasis = SengStatusVerifikasi::select('*')->get();

    
        // return view('backend.rekap.index', compact('kabkotas', 'statuss', 'data'));
        return view('backend.rekap.index',  compact('kabkotas','statuss','status_verifikasis','data'));
    }
}
