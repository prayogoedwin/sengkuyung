<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

class VerifikasiController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $verifikasis = SengPendataanKendaraan::select('*')->where('email', '!=', 'superadmin@example.com')->get();

            return DataTables::of($verifikasis)
                ->addIndexColumn()
                ->addColumn('nopol', function ($verifikasi) {
                    return $verifikasi->nopol ? $verifikasi->nopol : 'N/A';
                })
                ->addColumn('tanggal_pendataan', function ($verifikasi) {
                    return $verifikasi->created_at ?  Carbon::parse($verifikasi->created_at)->format('Y-m-d H:i:s') : 'N/A';
                })
                ->addColumn('nama', function ($verifikasi) {
                    return $verifikasi->nama ? $verifikasi->nama : 'N/A';
                })
                ->addColumn('nohp', function ($verifikasi) {
                    return $verifikasi->nohp ? $verifikasi->nohp : 'N/A';
                })
                ->addColumn('status_name', function ($verifikasi) {
                    return $verifikasi->status_name ? $verifikasi->status_name : 'N/A';
                })
                ->addColumn('status_verifikasi_name', function ($verifikasi) {
                    return $verifikasi->status_verifikasi_name ? $verifikasi->status_verifikasi_name : 'N/A';
                })
                ->addColumn('options', function ($verifikasi) {
                    return '
                        <a href="' . route('verifikasi-detail.index', ['id' => Helper::encodeId($verifikasi->id)]) . '" class="btn btn-primary btn-sm">Verif</a>
                        <button hidden class="btn btn-danger btn-sm" onclick="confirmDelete(' . Helper::encodeId($verifikasi->id) . ')">Delete</button>
                    ';
                })
                ->rawColumns(['options'])  // Pastikan menambahkan ini untuk kolom options
                ->make(true);
        }

        $status_verifikasis = SengStatusVerifikasi::select('*')->get();

        $kabkotas = SengWilayah::select('*')
        ->where('id_up', 33)
        ->get();

        return view('backend.verifikasis.index',  compact('kabkotas', 'status_verifikasis'));
    }

    // Show a single record
    public function show($id)
    {
        // Decode ID from request

        $decodedId = Helper::decodeId($id);

        // Find data based on ID
        $data = SengPendataanKendaraan::find($decodedId);
        $status_verifikasis = SengStatusVerifikasi::select('*')->get();

        // If data is not found
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        // Return the view with the data
        return view('backend.verifikasis.show',  compact('data', 'status_verifikasis'));
    }

    public function verif(Request $request)
    {
        // Decode ID from request
        $decodedId = Helper::decodeId($request->id);

        // Find the record by the decoded ID
        $data = SengPendataanKendaraan::find($decodedId);
        $status_verifikasi = SengStatusVerifikasi::find($request->status_verifikasi_id);

        // Check if the record exists
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        // Update the status_verifikasi field
        $data->status_verifikasi = $request->status_verifikasi_id;
        $data->status_verifikasi_name = $status_verifikasi->nama;
        $data->file9_ket = $request->keterangan;

        // Save the changes
        if ($data->save()) {
            // Redirect to the detail page upon successful update
            return redirect()->route('verifikasi-detail.index', ['id' => Helper::encodeId($data->id)])->with('success', 'Status updated successfully.');
        } else {
            return back()->with('error', 'Failed to update status.');
        }
    }

}
