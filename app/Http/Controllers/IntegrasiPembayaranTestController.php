<?php

namespace App\Http\Controllers;

use App\Models\IntegrasiSetting;
use App\Services\JrTransaksiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class IntegrasiPembayaranTestController extends Controller
{
    public function index(): View
    {
        $this->ensureSuperAdmin();

        return view('backend.integrasi-pembayaran.index', [
            'settings' => IntegrasiSetting::query()->orderBy('nama_aplikasi')->get(),
            'result' => null,
        ]);
    }

    public function hit(Request $request, JrTransaksiClient $client): View
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'integrasi_id' => ['required', 'integer', 'exists:integrasi_settings,id'],
            'tanggal_penetapan' => ['required', 'date_format:Y-m-d'],
        ]);

        $setting = IntegrasiSetting::query()->findOrFail($validated['integrasi_id']);
        $settings = IntegrasiSetting::query()->orderBy('nama_aplikasi')->get();

        try {
            $fetched = $client->fetch($setting, $validated['tanggal_penetapan']);
            $response = $fetched['response'];

            $result = [
                'nama_aplikasi' => $setting->nama_aplikasi,
                'url' => $fetched['url'],
                'client_id' => $setting->client_id,
                'timestamp' => $fetched['timestamp'],
                'signature' => $fetched['signature'],
                'status' => $response->status(),
                'body' => $response->body(),
                'error' => null,
            ];
        } catch (Throwable $e) {
            $result = [
                'nama_aplikasi' => $setting->nama_aplikasi,
                'url' => $client->transaksiUrl((string) $setting->base_url, $validated['tanggal_penetapan']),
                'client_id' => $setting->client_id,
                'timestamp' => null,
                'signature' => null,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }

        return view('backend.integrasi-pembayaran.index', [
            'settings' => $settings,
            'result' => $result,
            'selectedId' => $setting->id,
            'tanggalPenetapan' => $validated['tanggal_penetapan'],
        ]);
    }

    private function ensureSuperAdmin(): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'));

        abort_unless($isSuperAdmin, 403, 'Akses hanya untuk superadmin.');
    }
}
