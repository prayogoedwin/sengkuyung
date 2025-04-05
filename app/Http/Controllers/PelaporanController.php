<?php

namespace App\Http\Controllers;
use App\Models\WilayahSamsat;
use App\Models\SengPendataanKendaraan;
use App\Models\SengStatus;
use App\Models\SengStatusVerifikasi;
use App\Models\SengStatusFile;
use App\Models\SengWilayah;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class PelaporanController extends Controller
{
    public function index(Request $request)
    {
        $user = User::findOrFail(auth()->id());
        $status_verifikasis = SengStatusVerifikasi::select('*')->get();
        $kabkotas = SengWilayah::select('*')
        ->where('id_up', 33)
        ->get();
        return view('backend.pelaporan.index',  compact('kabkotas', 'status_verifikasis'));
    }

    public function pelaporanCsv(Request $request){
     
        $tipe = $request->tipe;
        if ($tipe == 1) {
            return $this->jurnalCsv($request);  // Kirim request ke fungsi
        } elseif ($tipe == 2) {
            return $this->rekapCsv($request);  // Kirim request ke fungsi
        }
    
        return response()->json(['message' => 'Tipe tidak valid'], 400);
    }

    public function jurnalCsv(Request $request)
    {
        $userRole = auth()->user()->role;
        $user = User::findOrFail(auth()->id());
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
            $verifikasis->whereBetween('tanggal_akhir_Pkb', [$request->tanggal_start, $request->tanggal_end]);
        }

        $filename = "jurnal_pelaporan_" . date('YmdHis') . ".csv";
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        $callback = function () use ($verifikasis) {
            $file = fopen('php://output', 'w');

            // Header CSV
            fputcsv($file, ['No', 'Tanggal Pendataan', 'Nopol', 'Nama', 'No HP', 'Kota', 'Kecamatan', 'Kelurahan', 'Alamat', 'Status Kendaraan', 'Tanggal Akhir PKB', 'Nama Petugas']);

            $no = 1;
            foreach ($verifikasis->get() as $verifikasi) {
                fputcsv($file, [
                    $no++,
                    $verifikasi->created_at ? Carbon::parse($verifikasi->created_at)->format('Y-m-d') : 'N/A',
                    $verifikasi->nopol ?? 'N/A',
                    $verifikasi->nama ?? 'N/A',
                    "'".$verifikasi->nohp."'" ?? 'N/A',
                    $verifikasi->kota_name ?? 'N/A',
                    $verifikasi->kec_name ?? 'N/A',
                    $verifikasi->desa_name ?? 'N/A',
                    $verifikasi->alamat ?? 'N/A',
                    $verifikasi->status_name ?? 'N/A',
                    $verifikasi->created_at ? Carbon::parse($verifikasi->tanggal_akhir_Pkb)->format('Y-m-d') : 'N/A',
                    $verifikasi->createdByUser ? $verifikasi->createdByUser->name : 'N/A', // Nama User dari created_by

                   
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function rekapCsv(Request $request)
    {
        $fileName = "rekap_pelaporan_" . date('YmdHis') . ".csv";
        $filePath = storage_path('app/' . $fileName);
        $file = fopen($filePath, 'w');

        // Header CSV
        fputcsv($file, [
            'NO', 'KAB/KOTA', 'DIMILIKI', 'GANTI KEPEMILIKAN', 'RUSAK BERAT', 'HILANG',
            'MENINGGAL DUNIA TANPA AHLI WARIS', 'MENUTUP USAHA / PAILIT', 
            'DICABUT REGISTRASINYA', 'TERKENA BENCANA ALAM', 
            'TIDAK MEMPUNYAI KEKAYAAN LAGI', 'TIDAK DIKETAHUI ALAMAT'
        ]);

        // Query LEFT JOIN untuk memastikan semua kabupaten/kota muncul
        $rekapData = DB::table('seng_wilayah AS w') // Daftar kabupaten/kota
            ->leftJoin('seng_pendataan_kendaraan AS k', 'w.id', '=', 'k.kota') // Gabungkan dengan kendaraan
            ->select(
                'w.nama AS kab_kota',
                DB::raw("COALESCE(SUM(CASE WHEN k.status = 'DIMILIKI' THEN 1 ELSE 0 END), 0) AS DIMILIKI"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status = 'GANTI KEPEMILIKAN' THEN 1 ELSE 0 END), 0) AS GANTI_KEPEMILIKAN"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status = 'RUSAK BERAT' THEN 1 ELSE 0 END), 0) AS RUSAK_BERAT"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status = 'HILANG' THEN 1 ELSE 0 END), 0) AS HILANG"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status = 'MENINGGAL DUNIA TANPA AHLI WARIS' THEN 1 ELSE 0 END), 0) AS MENINGGAL_DUNIA"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status = 'MENUTUP USAHA / PAILIT' THEN 1 ELSE 0 END), 0) AS MENUTUP_USAHA"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status = 'DICABUT REGISTRASINYA' THEN 1 ELSE 0 END), 0) AS DICABUT_REGISTRASI"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status = 'TERKENA BENCANA ALAM' THEN 1 ELSE 0 END), 0) AS BENCANA_ALAM"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status = 'TIDAK MEMPUNYAI KEKAYAAN LAGI' THEN 1 ELSE 0 END), 0) AS TIDAK_PUNYA_KEKAYAAN"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status = 'TIDAK DIKETAHUI ALAMAT' THEN 1 ELSE 0 END), 0) AS TIDAK_DIKEATAHUI_ALAMAT")
            )
            ->where('w.id_up', 33) // Tambahkan filter id_up = 33
            ->groupBy('w.nama')
            ->orderBy('w.id', 'ASC')
            ->get();

        // Menulis data ke CSV
        $no = 1;
        foreach ($rekapData as $row) {
            fputcsv($file, [
                $no++, 
                $row->kab_kota,
                $row->DIMILIKI,
                $row->GANTI_KEPEMILIKAN,
                $row->RUSAK_BERAT,
                $row->HILANG,
                $row->MENINGGAL_DUNIA,
                $row->MENUTUP_USAHA,
                $row->DICABUT_REGISTRASI,
                $row->BENCANA_ALAM,
                $row->TIDAK_PUNYA_KEKAYAAN,
                $row->TIDAK_DIKEATAHUI_ALAMAT
            ]);
        }

        fclose($file);

        // Mengembalikan file CSV sebagai response download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
