<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJasaRaharjaApi
{
    public const MESSAGE = 'Akses API ini hanya untuk akun Jasa Raharja.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('jasa_raharja')) {
            return response()->json([
                'status' => false,
                'message' => self::MESSAGE,
            ], 403);
        }

        return $next($request);
    }
}
