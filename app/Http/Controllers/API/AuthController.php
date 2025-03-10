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
            $kecamatan = SengWilayah::where('id', substr($kodeWilayah, 0, 6))->first();
            $kota = SengWilayah::where('id', substr($kodeWilayah, 0, 4))->first();
            $provinsi = SengWilayah::where('id', substr($kodeWilayah, 0, 2))->first();

            $responseData['nama_kelurahan'] = $kelurahan->nama ?? null;
            $responseData['nama_kecamatan'] = $kecamatan->nama ?? null;
            $responseData['nama_kota'] = $kota->nama ?? null;
            $responseData['nama_provinsi'] = $provinsi->nama ?? null;
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
}
