<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenyPetugasWeb
{
    public const MESSAGE = 'Akun petugas hanya untuk aplikasi mobile. Dashboard web tidak tersedia untuk akun ini.';

    /**
     * Blokir akun dengan role petugas dari area dashboard (hanya API/mobile).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('petugas')) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login.form')
                ->withErrors(['login_error' => self::MESSAGE]);
        }

        return $next($request);
    }
}
