<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePetugasApi
{
    public const MESSAGE = 'Akses data tertagih hanya untuk akun petugas.';

    /**
     * API data tertagih hanya untuk role petugas (guard web), dari user yang sudah login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('petugas')) {
            return response()->json([
                'status' => false,
                'message' => self::MESSAGE,
                'debug' => [
                    'authenticated' => (bool) $user,
                    'user_id' => $user?->id,
                    'roles' => $user ? $user->roles()->get(['name', 'guard_name'])->toArray() : [],
                    'expected_role' => 'petugas',
                ],
            ], 403);
        }

        return $next($request);
    }
}
