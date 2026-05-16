<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePetugasD2dApi
{
    public const MESSAGE = 'Akses data tertagih D2D hanya untuk akun petugas D2D.';

    /**
     * API data tertagih D2D hanya untuk role petugas-d2d (guard web), dari user yang sudah login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('petugas-d2d')) {
            return response()->json([
                'status' => false,
                'message' => self::MESSAGE,
            ], 403);
        }

        return $next($request);
    }
}
