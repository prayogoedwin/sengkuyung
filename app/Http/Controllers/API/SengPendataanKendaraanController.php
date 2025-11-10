<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SengPendataanKendaraan;
use App\Models\SengStatus;
use App\Models\SengStatusVerifikasi;
use App\Models\SengSaamsat;
use App\Models\SengWilayahKec;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Auth;


class SengPendataanKendaraanController extends Controller
{

    // Definisikan aturan validasi sebagai property
    protected $rules = [
        'nohp' => 'required|digits_between:10,15|numeric',
        // 'email' => 'required|email',
        'nik' => 'required|digits:16|numeric',
        'nopol' => 'required',
        'nama' => 'required',
        'alamat' => 'required',
    ];
    
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 10); // Default 10 item per halaman
        // $data = SengPendataanKendaraan::paginate($perPage);
        $data = SengPendataanKendaraan::where('created_by', $user->id)->paginate($perPage);

         // Ubah ke array sebelum mengubah ID
        $items = collect($data->items())->map(function ($item) {
            $itemArray = $item->toArray(); // Konversi ke array
            $itemArray['id'] = Helper::encodeId($itemArray['id']); // Encode ID
            $itemArray['created_at'] = Carbon::parse($itemArray['created_at'])->format('Y-m-d H:i:s');
            return $itemArray;
        });

        return response()->json([
            'status' => true,
            'message' => 'List data ditemukan',
            'data' => $items, // Data hasil pagination yang sudah dimodifikasi
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'next_page_url' => $data->nextPageUrl(),
                'prev_page_url' => $data->previousPageUrl(),
            ]
        ]);
    }  

    // Store a new record
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Ambil user yang sedang login dari token
        $user = Auth::user(); 

        // Tambahkan `created_by` ke dalam request
        // $requestData = $request->all();
        // $requestData['created_by'] = $user->id;


        $dStatus = Helper::decodeId($request->status);
        $status = SengStatus::find( $dStatus);

        $dStatus_verifikasi = Helper::decodeId($request->status_verifikasi);
        $status_verifikasi = SengStatusVerifikasi::find($dStatus_verifikasi);
       
        $kec_dagri = SengWilayahKec::where('id_kecamatan', $request->kec)->first();

        // Buat variabel untuk menampung nilai dengan format khusus
        $kode_simpan = $request->kota;
        if (strlen($kode_simpan) === 2 && $kode_simpan[0] === '0' && $kode_simpan[1] >= '1' && $kode_simpan[1] <= '9') {
            $kode_simpan = $kode_simpan[1];
        }

        $kota_dagri = SengSaamsat::where('id', $kode_simpan)->first();



        if($user->kota != $kota_dagri->kabkota){
            return response()->json(['status' => false, 'message' => 'Nopol tidak berada di wilayah pencatatan Anda', 'data' => ''], Response::HTTP_BAD_REQUEST);
        }

        $pengecekan = SengPendataanKendaraan::where('nopol', $request->nopol)->first();
        if($pengecekan) {
            if(date('Y', strtotime($pengecekan->created_at)) == date('Y')) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Nomor polisi sudah pernah didata tahun ini',
                    'data' => ''
                ], Response::HTTP_BAD_REQUEST);
            }
        }     
        

        $requestData = array_merge($request->all(), [
            'status' => $status->id,
            'status_name' => $status->nama,
            'status_verifikasi' => $status_verifikasi->id,
            'status_verifikasi_name' => $status_verifikasi->nama,
            'kota_dagri' => $kota_dagri->kabkota,
            'kec_dagri' => $kec_dagri->kode_dagri,
            'created_by' => $user->id,
            'updated_by' => $user->id
        ]);

        // Simpan data
        $data = SengPendataanKendaraan::create($requestData);

        // Buat salinan data untuk response
        $responseData = $data->toArray();
        $responseData['id'] = Helper::encodeId($data->id);

        $data->id = Helper::encodeId($data->id);

        $html = null;
        if($dStatus == 2){
            $data = [
                'nama' => $request->nama, // Ganti dengan variabel atau data dari DB
                'alamat' => $request->alamat.''.$request->desa_name.''.$request->kec_name,
                'kota' => $request->kota_name,
                'no_polisi' => $request->nopol,
                'merk' => $request->merk,
                'tipe' => $request->tipe,
                'tanggal' => now()->format('d F Y') // Format tanggal: 20 Februari 2025
            ];
            $html = view('backend/html/surat_pernyataan', $data)->render();
        }

        return response()->json(['status' => true, 'message' => 'Data berhasil ditambahkan', 'data' => $responseData, 'html' => $html], Response::HTTP_CREATED);
    }

    public function upload(Request $request, $id)
    {
        // Decode ID dari request
        $decodedId = Helper::decodeId($id);
    
        $validator = Validator::make($request->all(), [
            'file_ke' => 'required|string|in:file0,file1,file2,file3,file4,file5,file6,file7,file8,file9',
            'file' => 'required|file|max:2048', // Maksimum 2MB
            'keterangan' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }
    
        $data = SengPendataanKendaraan::find($decodedId);
        if (!$data) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], Response::HTTP_NOT_FOUND);
        }
    
        $user = Auth::user();
        $file_ke = $request->file_ke;
        $tahun = Carbon::now()->year;
        $timestamp = Carbon::now()->format('YmdHis'); // Format waktu
    
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $nama_file = hash('sha256', $timestamp . $decodedId . $request->keterangan) . '.' . $extension;
        $path = "uploads/$tahun/$nama_file";
    
        // Ambil URL file lama dari database
        $oldFileUrl = $data->{"{$file_ke}_url"};
    
        if ($oldFileUrl) {
            // Ekstrak nama file dari URL (biasanya "storage/uploads/2024/nama_file.ext")
            $oldFilePath = str_replace('storage/', '', $oldFileUrl);
    
            // Cek dan hapus file lama jika ada
            if (Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }
        }
    
        // Simpan file baru
        $file->storeAs("uploads/$tahun", $nama_file, 'public');
    
        // Update database
        $data->update([
            'updated_by' => $user->id,
            $file_ke => $nama_file,
            "{$file_ke}_url" => "storage/$path",
            "{$file_ke}_ket" => $request->keterangan
        ]);
    
        // Buat salinan data untuk response
        $responseData = $data->toArray();
        $responseData['id'] = Helper::encodeId($data->id);
        // $responseData["{$file_ke}_url"] = config('app.url') . '/' . $data["{$file_ke}_url"];

        $baseUrl = config('app.url'); // Ambil BASE_URL dari .env

        for ($i = 0; $i <= 9; $i++) {
            $fileKey = "file{$i}_url";
            
            if (!empty($data[$fileKey])) {
                $responseData[$fileKey] = $baseUrl . '/' . $data[$fileKey];
            }
        }

    
        return response()->json([
            'status' => true,
            'message' => 'File berhasil diunggah',
            'data' => $responseData
        ]);
    }


    // Show a single record
    public function show($id)
    {
        // Decode ID dari request
        $decodedId = Helper::decodeId($id);

        // Cari data berdasarkan ID
        $data = SengPendataanKendaraan::find($decodedId);

        // Jika data tidak ditemukan
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        // Encode kembali ID sebelum dikirim
        $responseData = $data->toArray();
        $responseData['id'] = Helper::encodeId($data->id);

        $baseUrl = config('app.url'); // Ambil BASE_URL dari .env

        for ($i = 0; $i <= 9; $i++) {
            $fileKey = "file{$i}_url";
            
            if (!empty($data[$fileKey])) {
                $responseData[$fileKey] = $baseUrl . '/' . $data[$fileKey];
            }
        }

         // Tambahkan URL surat pernyataan
        $responseData['surat_pernyataan'] = $baseUrl . '/surat_pernyataan/' . Helper::encodeId($data->id);

        return response()->json([
            'status' => true,
            'message' => 'Data ditemukan',
            'data' => $responseData
        ]);
    }

    // Update a record
    public function update(Request $request, $id)
    {

        // Validasi request setelah data ditemukan
        $validator = Validator::make($request->all(), $this->rules);
        
        // Decode ID dari request
        $decodedId = Helper::decodeId($id);

        // Cari data berdasarkan ID yang sudah didecode
        $data = SengPendataanKendaraan::find($decodedId);
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

       
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // Ambil user yang sedang login
        $user = Auth::user();

        $status = SengStatus::find($request->status);
        $status_verifikasi = SengStatusVerifikasi::find($request->status_verifikasi);


        // Merge request data dengan updated_by
        $requestData = array_merge($request->all(), [
            'status_name' => $status->nama,
            'status_verifikasi_name' => $status_verifikasi->nama,
            'updated_by' => $user->id
        ]);

        // Update data
        $data->update($requestData);

        $responseData = $data->toArray();
        $responseData['id'] = Helper::encodeId($data->id);


        return response()->json([
            'status' => true,
            'message' => 'Data berhasil diperbarui',
            'data' => $responseData
        ]);
    }


    // Delete a record (Soft Delete jika pakai SoftDeletes)
    public function destroy($id)
    {
        // Decode ID dari request
        $decodedId = Helper::decodeId($id);

        // Cari data berdasarkan ID
        $data = SengPendataanKendaraan::find($decodedId);
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        // Hapus data (Soft Delete)
        $data->delete();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus',
            'data' => null
        ]);
    }
}

