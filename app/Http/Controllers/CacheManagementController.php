<?php

namespace App\Http\Controllers;

use App\Support\ApiCacheManager;
use App\Support\MaintenanceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CacheManagementController extends Controller
{
    private const API_CACHE_GROUPS = [
        'api:master:' => 'API Master Data',
        'api:master:status:' => 'API Status',
        'api:master:status-file:' => 'API Status File',
        'api:master:status-verifikasi:' => 'API Status Verifikasi',
        'api:master:wilayah:' => 'API Wilayah',
        'api:cek-versi:' => 'API Cek Versi App',
    ];

    private const ADMIN_CACHE_GROUPS = [
        'admin:dashboard:' => 'Admin Dashboard (statistik kartu)',
        'admin:rekap:' => 'Admin Rekap (grafik & ringkasan)',
        'admin:data-tertagih:' => 'Admin Data Tertagih',
        'admin:data-tertagih-d2d:' => 'Admin Data Tertagih D2D',
        'admin:master:' => 'Admin Master Data',
        'system:maintenance' => 'System Maintenance',
    ];

    private const SCOPE_PREFIXES = [
        'api' => 'api:',
        'admin' => 'admin:',
    ];

    public function index(): RedirectResponse
    {
        return redirect()->route('cache-management.scope', ['scope' => 'admin']);
    }

    public function scope(string $scope): View
    {
        $this->ensureSuperAdmin();

        $scope = $this->normalizeScope($scope);
        $cacheGroups = $this->getGroupsByScope($scope);
        $prefix = self::SCOPE_PREFIXES[$scope];
        $trackedKeys = array_values(array_filter(
            ApiCacheManager::getTrackedKeys(),
            static fn (string $key) => str_starts_with($key, $prefix)
        ));

        if ($scope === 'admin' && MaintenanceManager::isActive()) {
            $trackedKeys[] = MaintenanceManager::REDIS_KEY;
        }
        $trackedKeys = array_values(array_unique($trackedKeys));

        return view('backend.cache-management.index', [
            'trackedKeys' => $trackedKeys,
            'cacheGroups' => $cacheGroups,
            'scope' => $scope,
            'scopeLabel' => strtoupper($scope),
        ]);
    }

    public function clearSelected(Request $request, string $scope): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $scope = $this->normalizeScope($scope);
        $prefix = self::SCOPE_PREFIXES[$scope];

        $validated = $request->validate([
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['required', 'string'],
        ]);

        $deletedCount = 0;
        foreach ($validated['keys'] as $key) {
            if ($scope === 'admin' && $key === MaintenanceManager::REDIS_KEY) {
                $deletedCount += MaintenanceManager::clearRedisKey();
                continue;
            }

            if (!str_starts_with($key, $prefix)) {
                continue;
            }

            if (ApiCacheManager::forget($key)) {
                $deletedCount++;
            }
        }

        return redirect()
            ->route('cache-management.scope', ['scope' => $scope])
            ->with('success', "Berhasil menghapus {$deletedCount} cache key terpilih.");
    }

    public function clearGroup(Request $request, string $scope): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $scope = $this->normalizeScope($scope);
        $cacheGroups = $this->getGroupsByScope($scope);

        $validated = $request->validate([
            'prefix' => ['required', 'string'],
        ]);

        if (!array_key_exists($validated['prefix'], $cacheGroups)) {
            return redirect()
                ->route('cache-management.scope', ['scope' => $scope])
                ->with('error', 'Prefix cache tidak valid.');
        }

        if ($validated['prefix'] === MaintenanceManager::REDIS_KEY) {
            $deletedCount = MaintenanceManager::clearRedisKey();
        } else {
            $deletedCount = ApiCacheManager::forgetByPrefix($validated['prefix']);
        }

        return redirect()
            ->route('cache-management.scope', ['scope' => $scope])
            ->with('success', "Berhasil menghapus {$deletedCount} cache key dari grup.");
    }

    public function clearAll(string $scope): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $scope = $this->normalizeScope($scope);
        $prefix = self::SCOPE_PREFIXES[$scope];

        $deletedCount = ApiCacheManager::forgetByPrefix($prefix);
        if ($scope === 'admin') {
            $deletedCount += MaintenanceManager::clearRedisKey();
        }

        $label = strtoupper($scope);

        return redirect()
            ->route('cache-management.scope', ['scope' => $scope])
            ->with('success', "Berhasil menghapus semua cache {$label} ({$deletedCount} key).");
    }

    private function getGroupsByScope(string $scope): array
    {
        return $scope === 'api' ? self::API_CACHE_GROUPS : self::ADMIN_CACHE_GROUPS;
    }

    private function normalizeScope(string $scope): string
    {
        if (!array_key_exists($scope, self::SCOPE_PREFIXES)) {
            abort(404, 'Scope cache tidak ditemukan.');
        }

        return $scope;
    }

    private function ensureSuperAdmin(): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'));

        abort_unless($isSuperAdmin, 403, 'Akses hanya untuk superadmin.');
    }
}
