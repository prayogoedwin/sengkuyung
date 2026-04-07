<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login'); // buat file auth/login.blade.php
    }

    public function loginBak(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|captcha', // Validasi captcha
        ]);

        // Jika validasi captcha dan kredensial login berhasil
        if (Auth::attempt(['email' => $request->username, 'password' => $request->password])) {
            return redirect()->route('dashboard'); // Ganti dengan rute yang sesuai
        }

        return back()->withErrors(['login_error' => 'Invalid credentials or captcha']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|captcha',
            'otp_method' => 'required|in:email,wa',
        ], [
            'otp_method.required' => 'Pilih metode pengiriman OTP',
            'otp_method.in' => 'Metode OTP tidak valid',
        ]);

        // Cari user berdasarkan username (bisa email atau username)
        $user = User::where('email', $request->username)
                    ->first();

        // Validasi kredensial
        if (!$user || !\Hash::check($request->password, $user->password)) {
            return back()->withErrors(['login_error' => 'Username atau password salah']);
        }

        // Generate OTP
        $otp = Helper::generate_otp(6);
        $expired_at = Carbon::now()->addMinutes(5); // OTP berlaku 5 menit

        // Simpan OTP ke database
        $user->update([
            'otp' => $otp,
            'otp_expired_at' => $expired_at,
            'otp_method' => $request->otp_method,
        ]);

        // Simpan user_id di session untuk identifikasi nanti
        Session::put('otp_user_id', $user->id);

        // Kirim OTP sesuai metode yang dipilih
        $sent = false;
        $destination = '';

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
                return back()->withErrors(['login_error' => 'Nomor WhatsApp tidak terdaftar. Silakan gunakan email.']);
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

        if (!$sent) {
            // Hapus OTP dari database jika gagal kirim
            $user->update([
                'otp' => null,
                'otp_expired_at' => null,
                'otp_method' => null,
            ]);
            return back()->withErrors(['login_error' => 'Gagal mengirim kode OTP. Silakan coba lagi.']);
        }

        // Redirect ke halaman input OTP
        return redirect()->route('login.otp.form')
            ->with('success', "Kode OTP telah dikirim ke {$destination}. Silakan cek dan masukkan kode OTP Anda.");
    }

    public function showOtpForm()
    {
        // Cek apakah ada user_id di session
        if (!Session::has('otp_user_id')) {
            return redirect()->route('login.form')
                ->withErrors(['login_error' => 'Silakan login terlebih dahulu']);
        }

        $user = User::find(Session::get('otp_user_id'));

        if (!$user || !$user->otp) {
            Session::forget('otp_user_id');
            return redirect()->route('login.form')
                ->withErrors(['login_error' => 'Silakan login terlebih dahulu']);
        }

        // Cek apakah OTP sudah expired
        if (Carbon::now()->greaterThan($user->otp_expired_at)) {
            // Hapus OTP yang expired
            $user->update([
                'otp' => null,
                'otp_expired_at' => null,
                'otp_method' => null,
            ]);
            Session::forget('otp_user_id');
            return redirect()->route('login.form')
                ->withErrors(['login_error' => 'Kode OTP telah kadaluarsa. Silakan login ulang.']);
        }

        return view('otp', [
            'otp_method' => $user->otp_method,
            'expired_at' => $user->otp_expired_at,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        // Ambil user_id dari session
        if (!Session::has('otp_user_id')) {
            return redirect()->route('login.form')
                ->withErrors(['login_error' => 'Sesi OTP tidak ditemukan. Silakan login ulang.']);
        }

        $user = User::find(Session::get('otp_user_id'));

        if (!$user || !$user->otp) {
            Session::forget('otp_user_id');
            return redirect()->route('login.form')
                ->withErrors(['login_error' => 'Data OTP tidak ditemukan. Silakan login ulang.']);
        }

        // Cek apakah OTP sudah expired
        if (Carbon::now()->greaterThan($user->otp_expired_at)) {
            // Hapus OTP yang expired
            $user->update([
                'otp' => null,
                'otp_expired_at' => null,
                'otp_method' => null,
            ]);
            Session::forget('otp_user_id');
            return redirect()->route('login.form')
                ->withErrors(['login_error' => 'Kode OTP telah kadaluarsa. Silakan login ulang.']);
        }

        // Validasi OTP
        if ($request->otp !== $user->otp) {
            return back()->withErrors(['otp_error' => 'Kode OTP salah. Silakan coba lagi.']);
        }

        // OTP valid, hapus OTP dari database
        $user->update([
            'otp' => null,
            'otp_expired_at' => null,
            'otp_method' => null,
            'last_login' => Carbon::now(),
        ]);

        // Login user
        Auth::login($user);
        Session::forget('otp_user_id');

        return redirect()->route('dashboard')
            ->with('success', 'Login berhasil!');
    }


    public function resendOtp(Request $request)
    {
        if (!Session::has('otp_user_id')) {
            return redirect()->route('login.form')
                ->withErrors(['login_error' => 'Sesi OTP tidak ditemukan. Silakan login ulang.']);
        }

        $user = User::find(Session::get('otp_user_id'));

        if (!$user) {
            Session::forget('otp_user_id');
            return redirect()->route('login.form')
                ->withErrors(['login_error' => 'User tidak ditemukan']);
        }

        // Generate OTP baru
        $otp = Helper::generate_otp(6);
        $expired_at = Carbon::now()->addMinutes(5);

        // Update OTP di database
        $user->update([
            'otp' => $otp,
            'otp_expired_at' => $expired_at,
        ]);

        // Kirim ulang OTP
        $sent = false;
        $destination = '';

        if ($user->otp_method === 'email') {
            $destination = $user->email;
            $subjek = 'Kode OTP Login - ' . config('app.name');
            $text = view('emails.otp', [
                'nama' => $user->name,
                'otp' => $otp,
                'expired_minutes' => 5
            ])->render();
            
            $sent = Helper::fungsi_email($user->email, $subjek, $text);
        } else {
            $no_wa = Helper::format_nomor_wa($user->no_wa);
            $destination = $user->no_wa;
            $subjek = 'Kode OTP Login';
            $text = "Halo {$user->name},\n\n" .
                    "Kode OTP Anda untuk login ke " . config('app.name') . " adalah:\n\n" .
                    "*{$otp}*\n\n" .
                    "Kode ini berlaku selama 5 menit.\n\n" .
                    "Jangan bagikan kode ini kepada siapapun.";
            
            $sent = Helper::fungsi_wa($no_wa, $subjek, $text);
        }

        if (!$sent) {
            return back()->withErrors(['otp_error' => 'Gagal mengirim ulang kode OTP. Silakan coba lagi.']);
        }

        return back()->with('success', "Kode OTP baru telah dikirim ke {$destination}");
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Session::forget('otp_user_id');
        
        return redirect('/login')->with('success', 'Logout berhasil');
    }

}


?>