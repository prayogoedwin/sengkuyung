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
use Dompdf\Dompdf;
use Dompdf\Options;

use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index(Request $request)
    {
        $user = User::findOrFail(auth()->id());
        $status_verifikasis = SengStatusVerifikasi::select('*')->get();
        $kabkotas = SengWilayah::select('*')
        ->where('id_up', 33)
        ->get();
        return view('backend.downloads.index',  compact('kabkotas', 'status_verifikasis'));
    }

    public function downloadCsv(Request $request)
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
            $kecjudul = ' '.$kecjudul;
            $kecjudul_id = '_'.$request->district_id;
        }

        $periode = '';
        if ($request->tanggal_start && $request->tanggal_end) {
            $verifikasis->whereBetween('tanggal_akhir_Pkb', [$request->tanggal_start, $request->tanggal_end]);

            $tanggalStart = Carbon::parse($request->tanggal_start)->translatedFormat('d F Y');
            $tanggalEnd = Carbon::parse($request->tanggal_end)->translatedFormat('d F Y');

            $periode = " Jatuh Tempo: $tanggalStart s.d. $tanggalEnd";
        }


        $filename = "jurnal_download". $kotajudul_id .'_'. date('YmdHis') . ".csv";
        $headers = [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Expires" => "0",
        ];

      
        // $judul = 'JURNAL HASIL PENDATAAN'.$kotajudul.$kecjudul.$periode;
        $judul = mb_strtoupper('JURNAL HASIL PENDATAAN ' . $kotajudul . ' ' . $kecjudul . ' ' . $periode, 'UTF-8');


        $callback = function () use ($verifikasis, $judul) {
            ob_clean();
            flush();

            $file = fopen('php://output', 'w');  
   
            fputcsv($file, ['']); // baris kosong
            fputcsv($file, [$judul]); // header utama
            fputcsv($file, ['']); // baris kosong lagi

            // Header CSV
            fputcsv($file, ['NO', 'NOPOL', 'NAMA', 'NOHP', 'KOTA', 'KEC', 'KEL', 'ALAMAT', 'JATUH TEMPO']);

            $no = 1;
            foreach ($verifikasis->get() as $verifikasi) {
                fputcsv($file, [
                    $no++,
                    $verifikasi->nopol ?? 'N/A',
                    $verifikasi->nama ?? 'N/A',
                    "'".$verifikasi->nohp."'" ?? 'N/A',
                    $verifikasi->kota_name ?? 'N/A',
                    $verifikasi->kec_name ?? 'N/A',
                    $verifikasi->desa_name ?? 'N/A',
                    $verifikasi->alamat ?? 'N/A',
                    $verifikasi->tanggal_akhir_Pkb ? Carbon::parse($verifikasi->tanggal_akhir_Pkb)->format('Y-m-d') : 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadPdf(Request $request)
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
            // $kotajudul = mb_convert_encoding($kotajudul, 'UTF-8', 'auto');
            // $kotajudul = preg_replace('/[^\PC\s]/u', '', $kotajudul);
            // $kotajudul = trim(strip_tags($kotajudul));
            $kotajudul = ' '.$kotajudul;
            $kotajudul_id = '_'.$request->kabkota_id;
        }

        $kecjudul = '';
        $kecjudul_id = '';
        if ($request->district_id) {
            $verifikasis->where('kec', $request->district_id);
            $wilayah = SengWilayah::where('id', $request->district_id)->first();
            $kecjudul = $wilayah ? $wilayah->nama : '';
            $kecjudul = ' '.$kecjudul;
            $kecjudul_id = '_'.$request->district_id;
        }

        $periode = '';
        if ($request->tanggal_start && $request->tanggal_end) {
            $verifikasis->whereBetween('tanggal_akhir_Pkb', [$request->tanggal_start, $request->tanggal_end]);

            $tanggalStart = Carbon::parse($request->tanggal_start)->translatedFormat('d F Y');
            $tanggalEnd = Carbon::parse($request->tanggal_end)->translatedFormat('d F Y');

            $periode = " Jatuh Tempo: $tanggalStart s.d. $tanggalEnd";
        }

        // $judul = 'JURNAL HASIL PENDATAAN'.$kotajudul.$kecjudul.$periode;
        $judul = mb_strtoupper('JURNAL HASIL PENDATAAN ' . $kotajudul . ' ' . $kecjudul . ' ' . $periode, 'UTF-8');


        $dompdf = new Dompdf();
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf->setOptions($options);

        // HTML untuk PDF
        $html = "<html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid black; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <h2 style='text-align:center;'>{$judul}</h2>
            <table>
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>NOPOL</th>
                        <th>NAMA</th>
                        <th>NOHP</th>
                        <th>KOTA</th>
                        <th>KEC</th>
                        <th>KEL</th>
                        <th>ALAMAT</th>
                        <th>JATUH TEMPO</th>
                    </tr>
                </thead>
                <tbody>";

                $no = 1;
                foreach ($verifikasis->get() as $verifikasi) {
                    $html .= "<tr>
                        <td>{$no}</td>
                        <td>" . ($verifikasi->nopol ?? 'N/A') . "</td>
                        <td>" . ($verifikasi->nama ?? 'N/A') . "</td>
                        <td>'" . ($verifikasi->nohp ?? 'N/A') . "'</td>
                        <td>" . ($verifikasi->kota_name ?? 'N/A') . "</td>
                        <td>" . ($verifikasi->kec_name ?? 'N/A') . "</td>
                        <td>" . ($verifikasi->desa_name ?? 'N/A') . "</td>
                        <td>" . ($verifikasi->alamat ?? 'N/A') . "</td>
                        <td>" . ($verifikasi->tanggal_akhir_Pkb ? Carbon::parse($verifikasi->tanggal_akhir_Pkb)->format('Y-m-d') : 'N/A') . "</td>
                    </tr>";
                    $no++;
                }

        $html .= "</tbody></table></body></html>";

        // echo $html;
        // die();
       

         // Atur header agar file dikenali sebagai PDF
        $filename = "jurnal_download_" . date('YmdHis') . ".pdf";
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
    
        return response()->streamDownload(
            fn() => print($dompdf->output()),
            $filename
        );
        header("Expires: 0");
    }

}
