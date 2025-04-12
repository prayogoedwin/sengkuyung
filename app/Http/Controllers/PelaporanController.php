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
use Dompdf\Dompdf;
use Dompdf\Options;

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

        // Jika ingin tambahkan nama kota di judul, bisa seperti ini:
        $kotajudul = '';
        $kotajudul_id = '';
        if ($request->kabkota_id) {
            $verifikasis->where('kota', $request->kabkota_id);
            // $wilayah = SengWilayah::find($request->kabkota_id);
            $wilayah = SengWilayah::where('id', $request->kabkota_id)->first();
            $kotajudul = $wilayah ? $wilayah->nama : '';
            $kotajudul = ' '.$kotajudul;
            $kotajudul_id = '_'.$request->kabkota_id;
        }

        $kecjudul = '';
        $kecjudul_id = '';
        if ($request->district_id) {
            $verifikasis->where('kec', $request->district_id);
            $wilayah = SengWilayah::where('id', $request->district_id)->first();
            $kecjudul = $wilayah ? $wilayah->nama : '';
            $kecjudul = ' Kec. '.$kecjudul;
            $kecjudul_id = '_'.$request->district_id;
        }

        $periode = '';
        if ($request->tanggal_start && $request->tanggal_end) {
            $verifikasis->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);

            $tanggalStart = Carbon::parse($request->tanggal_start)->translatedFormat('d F Y');
            $tanggalEnd = Carbon::parse($request->tanggal_end)->translatedFormat('d F Y');

            $periode = " Periode: $tanggalStart s.d. $tanggalEnd";
        }

        $filename = "jurnal_pelaporan_" . date('YmdHis') . ".csv";
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Expires" => "0",
        ];

        $judul = mb_strtoupper('JURNAL PELAPORAN ' . $kotajudul . ' ' . $kecjudul . ' ' . $periode, 'UTF-8');

        $callback = function () use ($verifikasis, $judul) {
            ob_clean();
            flush();

            $file = fopen('php://output', 'w');

            fputcsv($file, ['']); // baris kosong
            fputcsv($file, [$judul]); // header utama
            fputcsv($file, ['']); // baris kosong lagi


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

        // Ambil user login
        $user = auth()->user();
        $userRole = $user->role;

        // Mulai query dengan join
        if ($request->kabkota_id) {

            $query = DB::table('seng_wilayah AS w')
            ->leftJoin('seng_pendataan_kendaraan AS k', 'w.id', '=', 'k.kec');
        
        }else{

            $query = DB::table('seng_wilayah AS w')
            ->leftJoin('seng_pendataan_kendaraan AS k', 'w.id', '=', 'k.kota');

        }


        // Filter tambahan
        if ($request->status_verifikasi_id) {
            $query->where('k.status_verifikasi', $request->status_verifikasi_id);
        }

        $kotajudul = '';
        $kotajudul_id = '';
        if ($request->kabkota_id) {
            $query->where('w.id_up', $request->kabkota_id);

            $wilayah = SengWilayah::where('id', $request->kabkota_id)->first();
            $kotajudul = $wilayah ? $wilayah->nama : '';
            $kotajudul = ' '.$kotajudul;
            $kotajudul_id = '_'.$request->kabkota_id;

        }else{
            $query->where('w.id_up', 33);
        }

        $kecjudul = '';
        $kecjudul_id = '';
        if ($request->district_id) {
            $query->where('k.kec', $request->district_id);
            $wilayah = SengWilayah::where('id', $request->district_id)->first();
            $kecjudul = $wilayah ? $wilayah->nama : '';
            $kecjudul = ' Kec. '.$kecjudul;
            $kecjudul_id = '_'.$request->district_id;
        }

        $periode = '';
        if ($request->tanggal_start && $request->tanggal_end) {
            $query->whereBetween('k.created_at', [$request->tanggal_start, $request->tanggal_end]);
            $periode = " Periode: $tanggalStart s.d. $tanggalEnd";
        }
        
        $judul = mb_strtoupper('REKAP PELAPORAN ' . $kotajudul . ' ' . $kecjudul . ' ' . $periode, 'UTF-8');

        $fileName = "rekap_pelaporan_" . date('YmdHis') . ".csv";
        $filePath = storage_path('app/' . $fileName);
        $file = fopen($filePath, 'w');

        fputcsv($file, ['']); // baris kosong
        fputcsv($file, [$judul]); // header utama
        fputcsv($file, ['']); // baris kosong lagi

        // Header CSV
        fputcsv($file, [
            'NO', 'WILAYAH', 'DIMILIKI', 'GANTI KEPEMILIKAN', 'RUSAK BERAT', 'HILANG',
            'MENINGGAL DUNIA TANPA AHLI WARIS', 'MENUTUP USAHA / PAILIT', 
            'DICABUT REGISTRASINYA', 'TERKENA BENCANA ALAM', 
            'TIDAK MEMPUNYAI KEKAYAAN LAGI', 'TIDAK DIKETAHUI ALAMAT'
        ]);

        
        
        // Select + Grouping
        $rekapData = $query
            ->select(
                'w.nama AS kab_kota',
                DB::raw("COALESCE(SUM(CASE WHEN k.status_name = 'DIMILIKI' THEN 1 ELSE 0 END), 0) AS DIMILIKI"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status_name = 'GANTI KEPEMILIKAN' THEN 1 ELSE 0 END), 0) AS GANTI_KEPEMILIKAN"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status_name = 'RUSAK BERAT' THEN 1 ELSE 0 END), 0) AS RUSAK_BERAT"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status_name = 'HILANG' THEN 1 ELSE 0 END), 0) AS HILANG"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status_name = 'MENINGGAL DUNIA TANPA AHLI WARIS' THEN 1 ELSE 0 END), 0) AS MENINGGAL_DUNIA"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status_name = 'MENUTUP USAHA / PAILIT' THEN 1 ELSE 0 END), 0) AS MENUTUP_USAHA"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status_name = 'DICABUT REGISTRASINYA' THEN 1 ELSE 0 END), 0) AS DICABUT_REGISTRASI"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status_name = 'TERKENA BENCANA ALAM' THEN 1 ELSE 0 END), 0) AS BENCANA_ALAM"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status_name = 'TIDAK MEMPUNYAI KEKAYAAN LAGI' THEN 1 ELSE 0 END), 0) AS TIDAK_PUNYA_KEKAYAAN"),
                DB::raw("COALESCE(SUM(CASE WHEN k.status_name = 'TIDAK DIKETAHUI ALAMAT' THEN 1 ELSE 0 END), 0) AS TIDAK_DIKEATAHUI_ALAMAT")
            )
            ->groupBy('w.nama')
            ->orderBy('w.id', 'ASC')
            ->get();

        // Tulis data ke CSV
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

        // Kirim response sebagai file download dan hapus file setelah dikirim
        return response()->download($filePath)->deleteFileAfterSend(true);
    }


    public function pelaporanPdf(Request $request){
     
        $tipe = $request->tipe;
        if ($tipe == 1) {
            return $this->jurnalPdf($request);  // Kirim request ke fungsi
        } elseif ($tipe == 2) {
            return $this->rekapPdf($request);  // Kirim request ke fungsi
        }
    
        return response()->json(['message' => 'Tipe tidak valid'], 400);
    }

    public function jurnalPdf(Request $request)
    {
        $userRole = auth()->user()->role;
        $user = User::findOrFail(auth()->id());
        $verifikasis = SengPendataanKendaraan::query();

        if ($userRole == 1 || $userRole == 2) {
            // admin pusat / admin prov
        } elseif ($userRole == 4) {
            $verifikasis->where('kota', $user->kota);
        } elseif ($userRole == 7) {
            $verifikasis->where('created_by', auth()->id());
        }

        if ($request->status_verifikasi_id) {
            $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
        }

        $kotajudul = '';
        if ($request->kabkota_id) {
            $verifikasis->where('kota', $request->kabkota_id);
            $wilayah = SengWilayah::where('id', $request->kabkota_id)->first();
            $kotajudul = $wilayah ? ' ' . $wilayah->nama : '';
        }

        $kecjudul = '';
        if ($request->district_id) {
            $verifikasis->where('kec', $request->district_id);
            $wilayah = SengWilayah::where('id', $request->district_id)->first();
            $kecjudul = $wilayah ? ' Kec. ' . $wilayah->nama : '';
        }

        $periode = '';
        if ($request->tanggal_start && $request->tanggal_end) {
            $verifikasis->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
            $tanggalStart = Carbon::parse($request->tanggal_start)->translatedFormat('d F Y');
            $tanggalEnd = Carbon::parse($request->tanggal_end)->translatedFormat('d F Y');
            $periode = " Periode: $tanggalStart s.d. $tanggalEnd";
        }

        $judul = mb_strtoupper('JURNAL PELAPORAN' . $kotajudul . $kecjudul . $periode, 'UTF-8');

        $data = $verifikasis->get();

        // DOMPDF
        $dompdf = new \Dompdf\Dompdf();
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf->setOptions($options);

        $html = "<html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid black; padding: 6px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <h2 style='text-align:center;'>{$judul}</h2>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Pendataan</th>
                        <th>Nopol</th>
                        <th>Nama</th>
                        <th>No HP</th>
                        <th>Kota</th>
                        <th>Kecamatan</th>
                        <th>Kelurahan</th>
                        <th>Alamat</th>
                        <th>Status Kendaraan</th>
                        <th>Tanggal Akhir PKB</th>
                        <th>Nama Petugas</th>
                    </tr>
                </thead>
                <tbody>";

        $no = 1;
        foreach ($data as $verifikasi) {
            $html .= "<tr>
                <td>{$no}</td>
                <td>" . ($verifikasi->created_at ? Carbon::parse($verifikasi->created_at)->format('Y-m-d') : 'N/A') . "</td>
                <td>" . ($verifikasi->nopol ?? 'N/A') . "</td>
                <td>" . ($verifikasi->nama ?? 'N/A') . "</td>
                <td>'" . ($verifikasi->nohp ?? 'N/A') . "'</td>
                <td>" . ($verifikasi->kota_name ?? 'N/A') . "</td>
                <td>" . ($verifikasi->kec_name ?? 'N/A') . "</td>
                <td>" . ($verifikasi->desa_name ?? 'N/A') . "</td>
                <td>" . ($verifikasi->alamat ?? 'N/A') . "</td>
                <td>" . ($verifikasi->status_name ?? 'N/A') . "</td>
                <td>" . ($verifikasi->tanggal_akhir_Pkb ? Carbon::parse($verifikasi->tanggal_akhir_Pkb)->format('Y-m-d') : 'N/A') . "</td>
                <td>" . ($verifikasi->createdByUser ? $verifikasi->createdByUser->name : 'N/A') . "</td>
            </tr>";
            $no++;
        }

        $html .= "</tbody></table></body></html>";

        $filename = "jurnal_pelaporan_" . date('YmdHis') . ".pdf";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response()->streamDownload(
            fn() => print($dompdf->output()),
            $filename
        );
    }

    public function rekapPdf(Request $request)
    {
        $user = auth()->user();

        // Query logic tetap sama seperti rekapCsv
        if ($request->kabkota_id) {
            $query = DB::table('seng_wilayah AS w')
                ->leftJoin('seng_pendataan_kendaraan AS k', 'w.id', '=', 'k.kec');
        } else {
            $query = DB::table('seng_wilayah AS w')
                ->leftJoin('seng_pendataan_kendaraan AS k', 'w.id', '=', 'k.kota');
        }

        if ($request->status_verifikasi_id) {
            $query->where('k.status_verifikasi', $request->status_verifikasi_id);
        }

        $kotajudul = '';
        $kecjudul = '';
        $periode = '';

        if ($request->kabkota_id) {
            $query->where('w.id_up', $request->kabkota_id);
            $wilayah = SengWilayah::find($request->kabkota_id);
            $kotajudul = $wilayah ? ' ' . $wilayah->nama : '';
        } else {
            $query->where('w.id_up', 33);
        }

        if ($request->district_id) {
            $query->where('k.kec', $request->district_id);
            $wilayah = SengWilayah::find($request->district_id);
            $kecjudul = $wilayah ? ' Kec. ' . $wilayah->nama : '';
        }

        if ($request->tanggal_start && $request->tanggal_end) {
            $query->whereBetween('k.created_at', [$request->tanggal_start, $request->tanggal_end]);
            $periode = " Periode: {$request->tanggal_start} s.d. {$request->tanggal_end}";
        }

        $judul = mb_strtoupper('REKAP PELAPORAN ' . $kotajudul . $kecjudul . $periode, 'UTF-8');

        $rekapData = $query
            ->select(
                'w.nama AS kab_kota',
                DB::raw("SUM(CASE WHEN k.status_name = 'DIMILIKI' THEN 1 ELSE 0 END) AS DIMILIKI"),
                DB::raw("SUM(CASE WHEN k.status_name = 'GANTI KEPEMILIKAN' THEN 1 ELSE 0 END) AS GANTI_KEPEMILIKAN"),
                DB::raw("SUM(CASE WHEN k.status_name = 'RUSAK BERAT' THEN 1 ELSE 0 END) AS RUSAK_BERAT"),
                DB::raw("SUM(CASE WHEN k.status_name = 'HILANG' THEN 1 ELSE 0 END) AS HILANG"),
                DB::raw("SUM(CASE WHEN k.status_name = 'MENINGGAL DUNIA TANPA AHLI WARIS' THEN 1 ELSE 0 END) AS MENINGGAL_DUNIA"),
                DB::raw("SUM(CASE WHEN k.status_name = 'MENUTUP USAHA / PAILIT' THEN 1 ELSE 0 END) AS MENUTUP_USAHA"),
                DB::raw("SUM(CASE WHEN k.status_name = 'DICABUT REGISTRASINYA' THEN 1 ELSE 0 END) AS DICABUT_REGISTRASI"),
                DB::raw("SUM(CASE WHEN k.status_name = 'TERKENA BENCANA ALAM' THEN 1 ELSE 0 END) AS BENCANA_ALAM"),
                DB::raw("SUM(CASE WHEN k.status_name = 'TIDAK MEMPUNYAI KEKAYAAN LAGI' THEN 1 ELSE 0 END) AS TIDAK_PUNYA_KEKAYAAN"),
                DB::raw("SUM(CASE WHEN k.status_name = 'TIDAK DIKETAHUI ALAMAT' THEN 1 ELSE 0 END) AS TIDAK_DIKEATAHUI_ALAMAT")
            )
            ->groupBy('w.nama')
            ->orderBy('w.id', 'ASC')
            ->get();

        // Mulai HTML untuk PDF
        $html = '
            <style>
                body { font-size: 10px; }
                table { font-size: 9px; }
                h1, h2, h3 { font-size: 12px; }
            </style>
            <h3 style="text-align:center;">' . $judul . '</h3>
            <table border="1" cellpadding="5" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>WILAYAH</th>
                        <th>DIMILIKI</th>
                        <th>GANTI KEPEMILIKAN</th>
                        <th>RUSAK BERAT</th>
                        <th>HILANG</th>
                        <th>MENINGGAL DUNIA TANPA AHLI WARIS</th>
                        <th>MENUTUP USAHA / PAILIT</th>
                        <th>DICABUT REGISTRASINYA</th>
                        <th>TERKENA BENCANA ALAM</th>
                        <th>TIDAK MEMPUNYAI KEKAYAAN LAGI</th>
                        <th>TIDAK DIKETAHUI ALAMAT</th>
                    </tr>
                </thead>
                <tbody>';

        $no = 1;
        foreach ($rekapData as $row) {
            $html .= '
                <tr>
                    <td>' . $no++ . '</td>
                    <td>' . $row->kab_kota . '</td>
                    <td>' . $row->DIMILIKI . '</td>
                    <td>' . $row->GANTI_KEPEMILIKAN . '</td>
                    <td>' . $row->RUSAK_BERAT . '</td>
                    <td>' . $row->HILANG . '</td>
                    <td>' . $row->MENINGGAL_DUNIA . '</td>
                    <td>' . $row->MENUTUP_USAHA . '</td>
                    <td>' . $row->DICABUT_REGISTRASI . '</td>
                    <td>' . $row->BENCANA_ALAM . '</td>
                    <td>' . $row->TIDAK_PUNYA_KEKAYAAN . '</td>
                    <td>' . $row->TIDAK_DIKEATAHUI_ALAMAT . '</td>
                </tr>';
        }

        $html .= '</tbody></table>';

        // Dompdf setup
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Download langsung PDF
        $fileName = 'rekap_pelaporan_' . date('YmdHis') . '.pdf';
        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"$fileName\"");
    }

}
