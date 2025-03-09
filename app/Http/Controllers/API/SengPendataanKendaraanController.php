<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SengPendataanKendaraan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\Helper;

use Illuminate\Support\Facades\Auth;


class SengPendataanKendaraanController extends Controller
{

    // Definisikan aturan validasi sebagai property
    protected $rules = [
        'nohp' => 'required|digits_between:10,15|numeric',
        'email' => 'required|email',
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
    
        // Encode ID pada setiap item
        $data->getCollection()->transform(function ($item) {
            $item->id = Helper::encodeId($item->id);
            return $item;
        });
    
        return response()->json([
            'status' => true,
            'message' => 'List data ditemukan',
            'data' => $data->items(), // Data hasil pagination
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

        $requestData = array_merge($request->all(), [
            'created_by' => $user->id,
            'updated_by' => $user->id
        ]);

        // Simpan data
        $data = SengPendataanKendaraan::create($requestData);

        // Buat salinan data untuk response
        $responseData = $data->toArray();
        $responseData['id'] = Helper::encodeId($data->id);

        $data->id = Helper::encodeId($data->id);

        return response()->json(['status' => true, 'message' => 'Data berhasil ditambahkan', 'data' => $responseData], Response::HTTP_CREATED);
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


        // Merge request data dengan updated_by
        $requestData = array_merge($request->all(), [
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

