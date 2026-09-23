<?php

namespace App\Http\Controllers;

use App\Models\IntegrasiSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrasiSettingController extends Controller
{
    public function index(): View
    {
        $this->ensureSuperAdmin();

        return view('backend.integrasi-setting.index', [
            'settings' => IntegrasiSetting::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        IntegrasiSetting::query()->create($this->validated($request));

        return redirect()->route('integrasi-setting.index')->with('success', 'Setting integrasi berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $setting = IntegrasiSetting::query()->findOrFail($id);
        $validated = $this->validated($request, $setting);
        $setting->update($validated);

        return redirect()->route('integrasi-setting.index')->with('success', 'Setting integrasi berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->ensureSuperAdmin();

        IntegrasiSetting::query()->findOrFail($id)->delete();

        return redirect()->route('integrasi-setting.index')->with('success', 'Setting integrasi berhasil dihapus.');
    }

    /**
     * @return array{nama_aplikasi: string, client_id: string, secret_key: string, api_key: ?string, base_url: string}
     */
    private function validated(Request $request, ?IntegrasiSetting $existing = null): array
    {
        $validated = $request->validate([
            'nama_aplikasi' => ['required', 'string', 'max:100'],
            'client_id' => ['required', 'string', 'max:100'],
            'secret_key' => [$existing ? 'nullable' : 'required', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'base_url' => ['required', 'url', 'max:255'],
        ]);

        $secret = trim((string) ($validated['secret_key'] ?? ''));
        if ($secret === '' && $existing) {
            $validated['secret_key'] = $existing->secret_key;
        } else {
            $validated['secret_key'] = $secret;
        }

        $apiKey = trim((string) ($validated['api_key'] ?? ''));
        $validated['api_key'] = $apiKey === '' ? null : $apiKey;
        $validated['base_url'] = rtrim($validated['base_url'], '/');

        return $validated;
    }

    private function ensureSuperAdmin(): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'));

        abort_unless($isSuperAdmin, 403, 'Akses hanya untuk superadmin.');
    }
}
