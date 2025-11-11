<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\SengWilayah;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Carbon\Carbon;

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
        $keamatan = $user->wilayah_kec; // Relasi ke SengWilayah
        $kodeWilayah = $keamatan->id ?? null; // Kode wilayah kelurahan

        if ($kodeWilayah) {
            // Query satu kali dengan LIKE
            $wilayahData = SengWilayah::where('id', 'LIKE', substr($kodeWilayah, 0, 2) . '%')
                ->orWhere('id', 'LIKE', substr($kodeWilayah, 0, 4) . '%')
                ->orWhere('id', 'LIKE', substr($kodeWilayah, 0, 6) . '%')
                ->orWhere('id', $kodeWilayah)
                ->get()
                ->keyBy('id'); // Mengelompokkan hasil berdasarkan ID

            // Masukkan ke responseData
            // $responseData['nama_kelurahan'] = $wilayahData[$kodeWilayah]->nama ?? null;
            $responseData['nama_kelurahan'] = $user->kelurahan;
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

     public function login_otp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'otp_method' => 'required|in:email,wa',
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

         // Generate OTP
        $otp = Helper::generate_otp(6);
        $expired_at = Carbon::now()->addMinutes(5); // OTP berlaku 5 menit

         // Simpan OTP ke database
        $user->update([
            'otp' => $otp,
            'otp_expired_at' => $expired_at,
            'otp_method' => $request->otp_method,
        ]);

        if ($request->otp_method === 'email') {
            $destination = $user->email;
            $subjek = 'Kode OTP Login - ' . config('app.name');
            $text = view('emails.otp', [
                'nama' => $user->name,
                'otp' => $otp,
                'expired_minutes' => 5
            ])->render();
            
            $sent = Helper::fungsi_email($user->email, $subjek, $text);
        } else {
            
            // Pastikan user punya nomor WA
            if (empty($user->whatsapp)) {
                // return back()->withErrors(['login_error' => 'Nomor WhatsApp tidak terdaftar. Silakan gunakan email.']);
                return response()->json([
                    'status' => false,
                    'message' => 'Nomor WhatsApp tidak terdaftar. Silakan gunakan email.',
                ], 401);
            }

            $no_wa = Helper::format_nomor_wa($user->whatsapp);
            $destination = $user->whatsapp;
            $subjek = 'Kode OTP Login';
            $text = "Halo {$user->name},\n\n" .
                    "Kode OTP Anda untuk login ke " . config('app.name') . " adalah:\n\n" .
                    "*{$otp}*\n\n" .
                    "Kode ini berlaku selama 5 menit.\n\n" .
                    "Jangan bagikan kode ini kepada siapapun.";
            
            $sent = Helper::fungsi_wa($no_wa, $subjek, $text);
        }

        $datas = array(
            'email' => $user->email,
            // 'otp'   => $otp,
            'expired_minutes' => '5'
        );

        return response()->json([
            'status' => true,
            'message' => 'OTP Tergenerate, dengan expire 5 menit',
            'data' => $datas,
        ]);
    }

      public function verifyOtp(Request $request)
        {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User Tidak Ditemukan, Login Ulang',
                ], 401);
            }

            $user->makeHidden([
                'email_verified_at',
                'created_at',
                'created_by',
                'updated_at',
                'updated_by',
                'deleted_at',
                'deleted_by'
            ]);

             if (!$user->otp) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data OTP tidak ditemukan. Silakan login ulang.',
                ], 401);
            }

            // Cek apakah OTP sudah expired
            if (Carbon::now()->greaterThan($user->otp_expired_at)) {
                // Hapus OTP yang expired
                $user->update([
                    'otp' => null,
                    'otp_expired_at' => null,
                    'otp_method' => null,
                ]);

                 return response()->json([
                    'status' => false,
                    'message' => 'Kode OTP telah kadaluarsa. Silakan login ulang.',
                ], 401);
            }

            // Validasi OTP
            if ($request->otp !== $user->otp) {
                // return back()->withErrors(['otp_error' => 'Kode OTP salah. Silakan coba lagi.']);
                 return response()->json([
                    'status' => false,
                    'message' => 'Kode OTP salah. Silakan coba lagi.',
                ], 401);
                
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // OTP valid, hapus OTP dari database
            $user->update([
                'otp' => null,
                'otp_expired_at' => null,
                'otp_method' => null,
                'last_login' => Carbon::now(),
                'remember_token' => $token
            ]);

            // Encode kembali ID sebelum dikirim
            $responseData = $user->toArray();
            $responseData['id'] = Helper::encodeId($user->id);

            // Ambil informasi wilayah
            $keamatan = $user->wilayah_kec; // Relasi ke SengWilayah
            $kodeWilayah = $keamatan->id ?? null; // Kode wilayah kelurahan

            if ($kodeWilayah) {
                // Query satu kali dengan LIKE
                $wilayahData = SengWilayah::where('id', 'LIKE', substr($kodeWilayah, 0, 2) . '%')
                    ->orWhere('id', 'LIKE', substr($kodeWilayah, 0, 4) . '%')
                    ->orWhere('id', 'LIKE', substr($kodeWilayah, 0, 6) . '%')
                    ->orWhere('id', $kodeWilayah)
                    ->get()
                    ->keyBy('id'); // Mengelompokkan hasil berdasarkan ID

                // Masukkan ke responseData
                // $responseData['nama_kelurahan'] = $wilayahData[$kodeWilayah]->nama ?? null;
                $responseData['nama_kelurahan'] = $user->kelurahan;
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
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'password_baru' => 'required|string|min:6',
            'konfirmasi_password' => 'required|string|same:password_baru',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        

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
