<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFieldOfficerApi
{
    public const MESSAGE = 'Akses API ini hanya untuk akun petugas atau petugas D2D.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['petugas', 'petugas-d2d'])) {
            return response()->json([
                'status' => false,
                'message' => self::MESSAGE,
            ], 403);
        }

        return $next($request);
    }
}
