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

        $pkb= (clone $verifikasis)->sum('pkb_pokok');
        $pkb_denda= (clone $verifikasis)->sum('pkb_denda');
        $pnbp = (clone $verifikasis)->sum('pnbp');
        // $pnbp_denda = (clone $verifikasis)->sum('pnbp_denda');
        $jr = (clone $verifikasis)->sum('jr');
        $jr_denda = (clone $verifikasis)->sum('jr_denda');
    
        // Simpan data statistik
        $data = [
            'total' => $total,
            'menunggu_verifikasi' => $menunggu_verifikasi,
            'verifikasi' => $verifikasi,
            'ditolak' => $ditolak,
            'pkb' => $pkb,
            'pkb_denda'=>$pkb_denda,
            'pnbp' => $pnbp,
            'jr' => $jr,
            'jr_denda' => $jr_denda,
        ];
    
        return response()->json([
            'status' => true,
            'message' => 'Data ditemukan',
            'data' => $data
        ]);
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

    
}
