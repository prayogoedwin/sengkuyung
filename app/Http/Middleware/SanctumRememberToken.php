<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class SanctumRememberToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken(); // Ambil token dari request

        if (!$token) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        // Cari user berdasarkan remember_token
        $user = User::where('remember_token', $token)->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Invalid token.'], Response::HTTP_UNAUTHORIZED);
        }

        // Set user yang ditemukan ke dalam request
        auth()->setUser($user);

        return $next($request);
    }
}
