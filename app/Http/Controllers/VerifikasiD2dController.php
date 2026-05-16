<?php

namespace App\Http\Controllers;

use App\Models\SengPendataanKendaraanD2d;
use Illuminate\Support\Facades\Auth;

class VerifikasiD2dController extends VerifikasiController
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!self::userCanAccessVerifikasiD2d()) {
                abort(403, 'Menu Verifikasi D2D hanya untuk UPPD/UPTD ke atas.');
            }

            return $next($request);
        });
    }

    public static function userCanAccessVerifikasiD2d(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['super-admin', 'superadmin', 'admin', 'adminprov', 'uptd', 'uppd']);
    }

    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraanD2d::class;
    }

    protected function verifikasiRouteIndex(): string
    {
        return 'verifikasi-d2d.index';
    }

    protected function verifikasiRouteDetail(): string
    {
        return 'verifikasi-d2d-detail.index';
    }

    protected function verifikasiRouteStatus(): string
    {
        return 'verifikasi-d2d.status';
    }

    protected function verifikasiViewIndex(): string
    {
        return 'backend.verifikasis-d2d.index';
    }

    protected function verifikasiViewShow(): string
    {
        return 'backend.verifikasis-d2d.show';
    }

    protected function verifikasiPageTitle(): string
    {
        return 'Verifikasi D2D';
    }
}
