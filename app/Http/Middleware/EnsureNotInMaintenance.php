<?php

namespace App\Http\Middleware;

use App\Support\MaintenanceManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotInMaintenance
{
    public const MESSAGE = 'Sistem sedang dalam maintenance. Silakan coba beberapa saat lagi.';

    public function handle(Request $request, Closure $next): Response
    {
        if (!MaintenanceManager::isActive()) {
            return $next($request);
        }

        if ($request->isMethod('GET') && in_array($request->path(), ['/', 'login'], true)) {
            return $next($request);
        }

        if ($request->is('otp') || $request->is('otp/*') || $request->is('act_login')) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'))) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => self::MESSAGE,
            ], 503);
        }

        return response()->view('maintenance', [
            'message' => self::MESSAGE,
        ], 503);
    }
}
