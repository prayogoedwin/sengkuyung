<?php

namespace App\Http\Controllers;

use App\Support\ApiCacheManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CacheManagementController extends Controller
{
    private const CACHE_GROUPS = [
        'api:seng-status:' => 'API Status',
        'api:seng-status-file:' => 'API Status File',
        'api:seng-status-verifikasi:' => 'API Status Verifikasi',
        'api:seng-wilayah:' => 'API Wilayah',
        'admin:data-tertagih:' => 'Admin Data Tertagih',
    ];

    public function index(): View
    {
        $this->ensureSuperAdmin();

        return view('backend.cache-management.index', [
            'trackedKeys' => ApiCacheManager::getTrackedKeys(),
            'cacheGroups' => self::CACHE_GROUPS,
        ]);
    }

    public function clearSelected(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['required', 'string'],
        ]);

        $deletedCount = 0;
        foreach ($validated['keys'] as $key) {
            if (ApiCacheManager::forget($key)) {
                $deletedCount++;
            }
        }

        return redirect()
            ->route('cache-management.index')
            ->with('success', "Berhasil menghapus {$deletedCount} cache key terpilih.");
    }

    public function clearGroup(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'prefix' => ['required', 'string'],
        ]);

        if (!array_key_exists($validated['prefix'], self::CACHE_GROUPS)) {
            return redirect()
                ->route('cache-management.index')
                ->with('error', 'Prefix cache tidak valid.');
        }

        $deletedCount = ApiCacheManager::forgetByPrefix($validated['prefix']);

        return redirect()
            ->route('cache-management.index')
            ->with('success', "Berhasil menghapus {$deletedCount} cache key dari grup.");
    }

    private function ensureSuperAdmin(): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'));

        abort_unless($isSuperAdmin, 403, 'Akses hanya untuk superadmin.');
    }
}
