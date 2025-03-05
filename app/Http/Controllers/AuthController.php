<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login'); // buat file auth/login.blade.php
    }

    public function login(Request $request)
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}


?>
