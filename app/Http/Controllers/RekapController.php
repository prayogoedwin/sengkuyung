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
use Illuminate\Support\Facades\DB;

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

        if ($request->status_id) {
            $verifikasis->where('status', $request->status_id);
        }

         // Tambahkan filter jika status_id dikirim
         if ($request->status_verifikasi_id) {
            $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
         }

        

        if ($request->tanggal_start && $request->tanggal_end) {
            $verifikasis->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
        }
    
        $total = (clone $verifikasis)->count();
        $pkb= (clone $verifikasis)->sum('pkb_pokok');
        $pkb_denda= (clone $verifikasis)->sum('pkb_denda');
        $pnbp = (clone $verifikasis)->sum('pnbp');
        // $pnbp_denda = (clone $verifikasis)->sum('pnbp_denda');
        $jr = (clone $verifikasis)->sum('jr');
        $jr_denda = (clone $verifikasis)->sum('jr_denda');

        $kabkotaId = $request->input('kabkota_id');

        if ($request->kabkota_id) {

            $koordinats = SengWilayah::select('*')
            ->where('id', $kabkotaId)
            ->first();
       
        }else{

            $koordinats = SengWilayah::select('*')
            ->where('id', 33)
            ->first();

        }

        // Pastikan lat dan lng ada sebelum dikirim ke View
        $koordinats = $koordinats ?: (object) ['lat' => -7.150975, 'lng' => 110.140259]; // Default ke Jawa Tengah

        if ($request->kabkota_id) {
            // Jika kabkota_id dikirim, hitung kendaraan per kecamatan dalam kabupaten/kota tertentu
            $potensiKend = SengWilayah::where('id_up', $kabkotaId)
                ->leftJoin('seng_pendataan_kendaraan', 'seng_wilayah.id', '=', 'seng_pendataan_kendaraan.kec')
                ->select(
                    'seng_wilayah.id', 
                    'seng_wilayah.nama as wilayah', 
                    'seng_wilayah.lat',
                    'seng_wilayah.lng',
                    DB::raw('COUNT(seng_pendataan_kendaraan.id) as total_vehicles')
                )
                ->groupBy('seng_wilayah.id', 'seng_wilayah.nama', 'seng_wilayah.lat', 'seng_wilayah.lng');
              
        } else {
            // Jika tidak ada kabkota_id, hitung kendaraan per kota
            $potensiKend = SengWilayah::where('id_up', 33)
                ->leftJoin('seng_pendataan_kendaraan', 'seng_wilayah.id', '=', 'seng_pendataan_kendaraan.kota')
                ->select(
                    'seng_wilayah.id', 
                    'seng_wilayah.nama as wilayah', 
                    'seng_wilayah.lat',
                    'seng_wilayah.lng',
                    DB::raw('COUNT(seng_pendataan_kendaraan.id) as total_vehicles')
                )
                ->groupBy('seng_wilayah.id', 'seng_wilayah.nama', 'seng_wilayah.lat', 'seng_wilayah.lng');
         
        }

        // Tambahkan filter jika status_id dikirim
        if ($request->status_id) {
            $potensiKend->where('seng_pendataan_kendaraan.status', $request->status_id);
        }

         // Tambahkan filter jika status_id dikirim
         if ($request->status_verifikasi_id) {
            $potensiKend->where('seng_pendataan_kendaraan.status_verifikasi', $request->status_verifikasi_id);
         }

        if ($request->tanggal_start && $request->tanggal_end) {
            $potensiKend->whereBetween('seng_pendataan_kendaraan.created_at', [$request->tanggal_start, $request->tanggal_end]);
        }

        // Ambil hasil query
        $potensiKend = $potensiKend->get();



        if ($request->kabkota_id) {
            // Jika kabkota_id dikirim, x-axis harus kecamatan
            $potensiKendStatus = SengWilayah::where('seng_wilayah.id_up', $kabkotaId)
                ->leftJoin('seng_pendataan_kendaraan', 'seng_wilayah.id', '=', 'seng_pendataan_kendaraan.kec')
                ->leftJoin('seng_status', 'seng_pendataan_kendaraan.status', '=', 'seng_status.id')
                ->select(
                    'seng_wilayah.id',
                    'seng_wilayah.nama as kecamatan',
                    DB::raw('CASE 
                        WHEN COUNT(seng_pendataan_kendaraan.id) = 0 THEN "Belum Ada Data" 
                        ELSE COALESCE(seng_status.nama, "Tidak Diketahui") 
                    END as status'),
                    DB::raw('COUNT(seng_pendataan_kendaraan.id) as total_vehicles')
                )
                ->groupBy('seng_wilayah.id', 'seng_wilayah.nama', 'seng_status.nama');
        
        } else {
            // Jika tidak ada kabkota_id, x-axis adalah kota
            $potensiKendStatus = SengWilayah::where('seng_wilayah.id_up', 33)
                ->leftJoin('seng_pendataan_kendaraan', 'seng_wilayah.id', '=', 'seng_pendataan_kendaraan.kota')
                ->leftJoin('seng_status', 'seng_pendataan_kendaraan.status', '=', 'seng_status.id')
                ->select(
                    'seng_wilayah.id',
                    'seng_wilayah.nama as kota', // X-axis sebagai kota
                    DB::raw('CASE 
                                WHEN COUNT(seng_pendataan_kendaraan.id) = 0 THEN "Belum Ada Data" 
                                ELSE COALESCE(seng_status.nama, "Tidak Diketahui") 
                            END as status'),
                    DB::raw('COUNT(seng_pendataan_kendaraan.id) as total_vehicles')
                )
                ->groupBy('seng_wilayah.id', 'seng_wilayah.nama', 'seng_status.nama');
        }
        
        // Tambahkan filter berdasarkan status_id jika dikirim
        if ($request->status_id) {
            $potensiKendStatus->where('seng_pendataan_kendaraan.status', $request->status_id);
        }

         // Tambahkan filter jika status_id dikirim
         if ($request->status_verifikasi_id) {
            $potensiKendStatus->where('seng_pendataan_kendaraan.status_verifikasi', $request->status_verifikasi_id);
         }

        if ($request->tanggal_start && $request->tanggal_end) {
            $potensiKendStatus->whereBetween('seng_pendataan_kendaraan.created_at', [$request->tanggal_start, $request->tanggal_end]);
        }
        
        // Eksekusi query
        $potensiKendStatus = $potensiKendStatus->get();
        
        // Ambil daftar kota atau kecamatan berdasarkan kondisi
        $kotaList = $potensiKendStatus->pluck($request->kabkota_id ? 'kecamatan' : 'kota')->unique()->values();
        
        // Ambil daftar status kendaraan, hapus NULL
        $statusList = $potensiKendStatus->pluck('status')->filter()->unique()->values();
        
        // Format data untuk Highcharts
        $seriesData = [];
        
        foreach ($statusList as $status) {

            if ($status === "Belum Ada Data") {
                continue;
            }

            $dataPerStatus = [];
        
            foreach ($kotaList as $kota) {
                // Ambil jumlah kendaraan dengan status tertentu di kota/kecamatan tertentu
                $totalVehicles = $potensiKendStatus
                    ->where($request->kabkota_id ? 'kecamatan' : 'kota', $kota)
                    ->where('status', $status)
                    ->sum('total_vehicles');
        
                $dataPerStatus[] = $totalVehicles;
            }
        
            $seriesData[] = [
                'name' => $status,
                'data' => $dataPerStatus
            ];
        }



    
        // Simpan data statistik
        $data = [
            'total' => $total,
            'pkb' => $pkb,
            'pkb_denda'=>$pkb_denda,
            'pnbp' => $pnbp,
            'jr' => $jr,
            'jr_denda' => $jr_denda,
        ];
    
        // Ambil data status verifikasi dan wilayah
        $statuss = SengStatus::all();
        $kabkotas = SengWilayah::where('id_up', 33)->get();
        $status_verifikasis = SengStatusVerifikasi::select('*')->get();

    
        // return view('backend.rekap.index', compact('kabkotas', 'statuss', 'data'));
        return view('backend.rekap.index',  compact(
            'kabkotas','statuss','status_verifikasis',
            'data', 'potensiKend', 'potensiKendStatus',
            'kotaList', 'seriesData', 'koordinats'
        ));
    }
}
