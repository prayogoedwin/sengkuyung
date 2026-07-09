<?php

namespace App\Http\Controllers;

use App\Models\StatusMaintenance;
use App\Support\MaintenanceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceStatusController extends Controller
{
    public function index(): View
    {
        $this->ensureSuperAdmin();

        $status = StatusMaintenance::query()->firstOrCreate([], [
            'maintenance' => false,
        ]);

        return view('backend.maintenance-status.index', [
            'status' => $status,
            'redisActive' => MaintenanceManager::isActive(),
            'redisKey' => MaintenanceManager::REDIS_KEY,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'maintenance' => ['required', 'boolean'],
        ]);

        MaintenanceManager::set(
            (bool) $validated['maintenance'],
            auth()->id()
        );

        $statusText = (bool) $validated['maintenance'] ? 'aktif' : 'nonaktif';

        return redirect()
            ->route('maintenance-status.index')
            ->with('success', "Status maintenance berhasil diubah menjadi {$statusText}.");
    }

    private function ensureSuperAdmin(): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'));

        abort_unless($isSuperAdmin, 403, 'Akses hanya untuk superadmin.');
    }
}
