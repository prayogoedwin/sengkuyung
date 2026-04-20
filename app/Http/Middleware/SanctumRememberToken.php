<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class SanctumRememberToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $this->parseBearerToken($request);

        if ($token === '') {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        /*
         * 1) Token Sanctum (format "id|secret") — validasi lewat personal_access_tokens.
         */
        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken) {
            $user = $accessToken->tokenable;
            if (! $user instanceof User) {
                $user = User::query()->find($accessToken->tokenable_id);
            }
            if ($user instanceof User) {
                Auth::guard('sanctum')->setUser($user);
                $request->setUserResolver(static fn () => $user);

                return $next($request);
            }
        }

        /*
         * 2) Fallback: token disimpan utuh di users.remember_token (kompatibilitas lama).
         */
        $user = User::where('remember_token', $token)->first();

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Invalid token.'], Response::HTTP_UNAUTHORIZED);
        }

        Auth::guard('sanctum')->setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }

    private function parseBearerToken(Request $request): string
    {
        $t = $request->bearerToken();
        if (is_string($t)) {
            $t = trim($t);
            $t = preg_replace('/^\xEF\xBB\xBF/', '', $t) ?? $t;
        } else {
            $t = '';
        }

        if ($t !== '') {
            return $t;
        }

        $header = $request->header('Authorization', '');
        if (! is_string($header)) {
            return '';
        }

        if (preg_match('/Bearer\s+(.+)$/i', trim($header), $m)) {
            return trim($m[1], " \t\n\r\0\x0B\"'");
        }

        return '';
    }
}
