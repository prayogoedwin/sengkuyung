<?php

namespace App\Http\Controllers;

use App\Models\DataTertagihD2d;
use App\Models\SengPendataanKendaraanD2d;
use App\Models\SengSaamsat;
use App\Models\SengWilayah;
use App\Models\User;
use App\Support\ApiCacheManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RekapD2dController extends RekapController
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!self::userCanAccessRekapPelaporanD2d()) {
                abort(403, 'Akses Rekap D2D ditolak.');
            }

            return $next($request);
        });
    }

    public static function userCanAccessRekapPelaporanD2d(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['super-admin', 'superadmin', 'admin', 'adminprov', 'uppd'], 'web');
    }

    public function index(Request $request)
    {
        $user = User::findOrFail(auth()->id());
        $this->applyUppdScope($request, $user);

        return parent::index($request);
    }

    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraanD2d::class;
    }

    protected function dataTertagihModelClass(): string
    {
        return DataTertagihD2d::class;
    }

    protected function rekapViewName(): string
    {
        return 'backend.rekap-d2d.index';
    }

    protected function rekapRouteIndex(): string
    {
        return 'rekap-d2d.index';
    }

    protected function rekapCacheKeyPrefix(): string
    {
        return 'admin:rekap-d2d:page:';
    }

    protected function rekapKabkotas(User $user)
    {
        if (!$user->hasRole('uppd')) {
            return parent::rekapKabkotas($user);
        }

        $lokasiSamsat = (string) ($user->lokasi_samsat ?? '');
        $kabkotaId = (string) ($user->kota ?: $this->resolveKabkotaFromLokasiSamsat($lokasiSamsat) ?: '');

        if ($kabkotaId === '') {
            return parent::rekapKabkotas($user);
        }

        return ApiCacheManager::remember(
            'admin:master:kabkota:rekap-d2d-scope:' . $kabkotaId,
            ApiCacheManager::masterTtl(),
            static function () use ($kabkotaId) {
                return SengWilayah::query()
                    ->where('id_up', 33)
                    ->where('id', $kabkotaId)
                    ->get();
            }
        );
    }

    protected function rekapExtraViewData(Request $request, User $user): array
    {
        $lokasiSamsat = (string) ($user->lokasi_samsat ?? '');
        $kabkotaBySamsat = $this->resolveKabkotaFromLokasiSamsat($lokasiSamsat);

        $isUppdScoped = $user->hasRole('uppd');

        return [
            'rekapPageTitle' => 'Rekap D2D',
            'isUppdScoped' => $isUppdScoped,
            'selectedKabkotaId' => $isUppdScoped ? (string) ($user->kota ?: $kabkotaBySamsat ?: '') : '',
            'userLokasiSamsat' => $isUppdScoped ? $lokasiSamsat : '',
        ];
    }

    private function applyUppdScope(Request $request, User $user): void
    {
        if (!$user->hasRole('uppd')) {
            return;
        }

        $lokasiSamsat = (string) ($user->lokasi_samsat ?? '');
        $kabkotaBySamsat = $this->resolveKabkotaFromLokasiSamsat($lokasiSamsat);
        $kabkotaScoped = (string) ($user->kota ?: $kabkotaBySamsat ?: '');

        if ($kabkotaScoped !== '') {
            $request->merge(['kabkota_id' => $kabkotaScoped]);
        }
        if ($lokasiSamsat !== '') {
            $request->merge(['lokasi_samsat' => $lokasiSamsat]);
        }
    }

    private function resolveKabkotaFromLokasiSamsat(?string $lokasiSamsatId): ?string
    {
        if (empty($lokasiSamsatId)) {
            return null;
        }

        $samsat = SengSaamsat::query()
            ->select('kabkota')
            ->where('id_wilayah_samsat', (string) $lokasiSamsatId)
            ->orWhere('id', (string) $lokasiSamsatId)
            ->first();

        return $samsat?->kabkota ? (string) $samsat->kabkota : null;
    }
}
