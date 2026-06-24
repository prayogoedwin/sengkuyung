<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VersionController extends Controller
{
    public function index(): View
    {
        $this->ensureSuperAdmin();

        return view('backend.version.index', [
            'versions' => AppVersion::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'nama_aplikasi' => ['required', 'string', 'max:100'],
            'versi' => ['required', 'string', 'max:50'],
            'alias' => ['nullable', 'string', 'max:50'],
        ]);

        AppVersion::query()->create($validated);

        return redirect()->route('version.index')->with('success', 'Data versi berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'nama_aplikasi' => ['required', 'string', 'max:100'],
            'versi' => ['required', 'string', 'max:50'],
            'alias' => ['nullable', 'string', 'max:50'],
        ]);

        $version = AppVersion::query()->findOrFail($id);
        $version->update($validated);

        return redirect()->route('version.index')->with('success', 'Data versi berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $version = AppVersion::query()->findOrFail($id);
        $version->delete();

        return redirect()->route('version.index')->with('success', 'Data versi berhasil dihapus.');
    }

    private function ensureSuperAdmin(): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'));

        abort_unless($isSuperAdmin, 403, 'Akses hanya untuk superadmin.');
    }
}
