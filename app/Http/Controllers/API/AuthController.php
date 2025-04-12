<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\SengWilayah;
use App\Helpers\Helper;

class AuthController extends Controller
{
    /**
     * Register User
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully',
            'token' => $token,
        ], 201);
    }

    /**
     * Login User
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user()->makeHidden([
            'email_verified_at',
            'created_at',
            'created_by',
            'updated_at',
            'updated_by',
            'deleted_at',
            'deleted_by'
        ]);
        
        $token = $user->createToken('auth_token')->plainTextToken;

        // Simpan token ke dalam remember_token
        $user->update(['remember_token' => $token]);

         // Encode kembali ID sebelum dikirim
        $responseData = $user->toArray();
        $responseData['id'] = Helper::encodeId($user->id);

        // Ambil informasi wilayah
        $kelurahan = $user->wilayah; // Relasi ke SengWilayah
        $kodeWilayah = $kelurahan->id ?? null; // Kode wilayah kelurahan

        if ($kodeWilayah) {
            // Query satu kali dengan LIKE
            $wilayahData = SengWilayah::where('id', 'LIKE', substr($kodeWilayah, 0, 2) . '%')
                ->orWhere('id', 'LIKE', substr($kodeWilayah, 0, 4) . '%')
                ->orWhere('id', 'LIKE', substr($kodeWilayah, 0, 6) . '%')
                ->orWhere('id', $kodeWilayah)
                ->get()
                ->keyBy('id'); // Mengelompokkan hasil berdasarkan ID

            // Masukkan ke responseData
            $responseData['nama_kelurahan'] = $wilayahData[$kodeWilayah]->nama ?? null;
            $responseData['nama_kecamatan'] = $wilayahData[substr($kodeWilayah, 0, 6)]->nama ?? null;
            $responseData['nama_kota'] = $wilayahData[substr($kodeWilayah, 0, 4)]->nama ?? null;
            $responseData['nama_provinsi'] = $wilayahData[substr($kodeWilayah, 0, 2)]->nama ?? null;
        }

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => $responseData,
            'token' => $token,
        ]);
    }


    // Show a single record
    public function show($id)
    {
        // Decode ID dari request
        $decodedId = Helper::decodeId($id);

        // Cari data berdasarkan ID
        $data = User::find($decodedId);

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

    /**
     * Logout User
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully',
        ]);
    }


    public function resetPassword(Request $request)
    {
        // Validasi input
        // $request->validate([
        //     'id' => 'required',
        //     'password_baru' => 'required|string|min:6',
        //     'konfirmasi_password' => 'required|string|same:password_baru',
        // ]);

        // $validator = Validator::make($request->all(), [
        //     'id' => 'required',
        //     'password_baru' => 'required|string|min:6',
        //     'konfirmasi_password' => 'required|string|same:password_baru',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Validasi gagal',
        //         'errors' => $validator->errors()
        //     ], Response::HTTP_UNPROCESSABLE_ENTITY);
        // }

        $id = $request->id;

        // Decode ID
        $decodedId = Helper::decodeId($id);

        // Cari user
        $user = User::find($decodedId);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        // Update password
        // $user->password = Hash::make($request->password_baru);
        $user->password = bcrypt($request->password_baru);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password berhasil direset'
        ]);
    }
}
