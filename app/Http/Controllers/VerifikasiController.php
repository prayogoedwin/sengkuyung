<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WilayahSamsat;
use App\Models\SengPendataanKendaraan;
use App\Models\SengStatus;
use App\Models\SengStatusVerifikasi;
use App\Models\SengStatusFile;
use App\Models\SengWilayah;
use App\Models\SengWilayahKec;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class VerifikasiController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $userId = Auth::user()->id ?? null;
            $userRoleId = Auth::user()->roles[0]->id ?? null;
            $userKotaId = Auth::user()->kota ?? null;

            // $userRole = auth()->user()->role; 
            // Cari admin berdasarkan ID
            // $user = User::findOrFail(auth()->id());
            // $verifikasis = SengPendataanKendaraan::select('*')->get();
            $verifikasis = SengPendataanKendaraan::query();


            // Jika bukan role 1 atau 2, dan tidak ada filter, maka kembalikan data kosong
            if (!in_array($userRoleId, [1, 2])) {
                $noFilters = !$request->status_verifikasi_id && !$request->kabkota_id && !$request->district_id && 
                            !$request->tanggal_start && !$request->tanggal_end;

                if ($noFilters) {
                    $verifikasis->whereRaw('1 = 0'); // data kosong
                }
            }

            // Apply filters based on user role
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

            // Filter berdasarkan input dari form
            if ($request->status_verifikasi_id) {
                $verifikasis->where('status_verifikasi', $request->status_verifikasi_id);
            }else{
                $verifikasis->where('status_verifikasi', 1); //status menunggu verifikasi
            }
            
            if ($request->district_id) {
                $verifikasis->where('kec', $request->district_id);
            }
            if ($request->tanggal_start && $request->tanggal_end) {
                $verifikasis->whereBetween('created_at', [$request->tanggal_start, $request->tanggal_end]);
            }

                // return DataTables::of($verifikasis)
                // ->addIndexColumn()
                // ->addColumn('nopol', function ($verifikasi) {
                //     return $verifikasi->nopol ? $verifikasi->nopol : 'N/A';
                // })
                // ->addColumn('tanggal_pendataan', function ($verifikasi) {
                //     return $verifikasi->created_at ?  Carbon::parse($verifikasi->created_at)->format('Y-m-d H:i:s') : 'N/A';
                // })
                // ->addColumn('nama', function ($verifikasi) {
                //     return $verifikasi->nama ? $verifikasi->nama : 'N/A';
                // })
                // ->addColumn('nohp', function ($verifikasi) {
                //     return $verifikasi->nohp ? $verifikasi->nohp : 'N/A';
                // })
                // ->addColumn('status_name', function ($verifikasi) {
                //     return $verifikasi->status_name ? $verifikasi->status_name : 'N/A';
                // })
                // ->addColumn('status_verifikasi_name', function ($verifikasi) {
                //     return $verifikasi->status_verifikasi_name ? $verifikasi->status_verifikasi_name : 'N/A';
                // })

                
                // ->addColumn('options', function ($verifikasi) {
                //     return '
                //         <a href="' . route('verifikasi-detail.index', ['id' => Helper::encodeId($verifikasi->id)]) . '" class="btn btn-primary btn-sm">Verif</a>
                //         <button hidden class="btn btn-danger btn-sm" onclick="confirmDelete(' . Helper::encodeId($verifikasi->id) . ')">Delete</button>
                //     ';
                // })
                // ->rawColumns(['options'])  // Pastikan menambahkan ini untuk kolom options
                // ->make(true);

                $datatable = DataTables::of($verifikasis)
                    ->addIndexColumn()
                    ->addColumn('nopol', function ($verifikasi) {
                        return $verifikasi->nopol ?? 'N/A';
                    })
                    ->addColumn('tanggal_pendataan', function ($verifikasi) {
                        return $verifikasi->created_at ? Carbon::parse($verifikasi->created_at)->format('Y-m-d H:i:s') : 'N/A';
                    })
                    ->addColumn('nama', function ($verifikasi) {
                        return $verifikasi->nama ?? 'N/A';
                    })
                    ->addColumn('nohp', function ($verifikasi) {
                        return $verifikasi->nohp ?? 'N/A';
                    })
                    ->addColumn('status_name', function ($verifikasi) {
                        return $verifikasi->status_name ?? 'N/A';
                    })
                    ->addColumn('status_verifikasi_name', function ($verifikasi) {
                        return $verifikasi->status_verifikasi_name ?? 'N/A';
                    });

                // Tambahkan kolom options berdasarkan role
                if ($userRoleId == 1 || $userRoleId == 2 || $userRoleId == 3) {
                    $datatable->addColumn('options', function ($verifikasi) {
                        return '
                            <a href="' . route('verifikasi-detail.index', ['id' => Helper::encodeId($verifikasi->id)]) . '" class="btn btn-primary btn-sm">Verif</a>
                            <button hidden class="btn btn-danger btn-sm" onclick="confirmDelete(' . Helper::encodeId($verifikasi->id) . ')">Delete</button>
                        ';
                    });
                } else {
                    $datatable->addColumn('options', function ($verifikasi) {
                        return '-';
                    });
                }

                return $datatable
                    ->rawColumns(['options'])
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

        $html = null;
        if($data->status == 2){
            $data_html = [
                'nama' => $data->nama, // Ganti dengan variabel atau data dari DB
                'alamat' => $data->alamat.''.$data->desa_name.''.$data->kec_name,
                'kota' => $data->kota_name,
                'no_polisi' => $data->nopol,
                'merk' => $data->merk,
                'tipe' => $data->tipe,
                'tanggal' => now()->format('d F Y') // Format tanggal: 20 Februari 2025
            ];
            $html = view('backend/html/surat_pernyataan', $data_html)->render();
        }

        // Return the view with the data
        return view('backend.verifikasis.show',  compact('data', 'status_verifikasis', 'html'));
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

    public function suratPernyataan($id)
    {
        // Decode ID from request
        $decodedId = Helper::decodeId($id);

        // Find data based on ID
        $data = SengPendataanKendaraan::find($decodedId);

        // If data is not found
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        // Prepare data for the view
        $suratData = [
            'nama' => $data->nama,
            'alamat' => $data->alamat,
            'desa' => $data->desa_name ?? '',
            'kecamatan' => $data->kec_name ?? '',
            'kota' => $data->kota_name ?? '',
            'no_polisi' => $data->nopol,
            'merk' => $data->merk,
            'tipe' => $data->tipe,
            'tanggal' => now()->locale('id')->isoFormat('D MMMM YYYY') // Format: 20 Februari 2025
        ];

        // Return the view with the data
        return view('backend.template_surat.surat_pernyataan', $suratData);
    }

}
