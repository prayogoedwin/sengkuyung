<?php

namespace App\Http\Controllers;

use App\Models\SengWilayah;
use App\Support\RekapVisualFilterCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RekapVisualFilterCacheController extends Controller
{
    public function index(): View
    {
        $this->ensureSuperAdmin();

        $settings = RekapVisualFilterCache::settings();
        $keys = RekapVisualFilterCache::listedKeys();
        $kabkotas = SengWilayah::query()
            ->where('id_up', 33)
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return view('backend.rekap-visual-filter-cache.index', [
            'settings' => $settings,
            'keys' => $keys,
            'kabkotas' => $kabkotas,
            'slotDefs' => RekapVisualFilterCache::scheduleSlotDefs(),
            'activeSlots' => RekapVisualFilterCache::scheduleSlots(),
            'defaultSlots' => RekapVisualFilterCache::DEFAULT_SLOTS,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'use_cache' => ['required', 'in:0,1'],
            'schedule_enabled' => ['required', 'in:0,1'],
            'warm_channel' => ['required', 'in:semua,reguler,d2d'],
            'ttl_hours' => ['required', 'integer', 'min:1', 'max:72'],
            'warm_year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'slot1' => ['nullable', 'string', 'max:8'],
            'slot2' => ['nullable', 'string', 'max:8'],
            'slot3' => ['nullable', 'string', 'max:8'],
            'slot4' => ['nullable', 'string', 'max:8'],
            'slot1_enabled' => ['nullable', 'in:0,1'],
            'slot2_enabled' => ['nullable', 'in:0,1'],
            'slot3_enabled' => ['nullable', 'in:0,1'],
            'slot4_enabled' => ['nullable', 'in:0,1'],
        ]);

        $slots = [];
        foreach ([1, 2, 3, 4] as $i) {
            $defaultTime = RekapVisualFilterCache::DEFAULT_SLOTS[$i - 1]['time'] ?? '06:00';
            $v = trim((string) ($validated['slot' . $i] ?? ''));
            $time = $defaultTime;
            if ($v !== '' && preg_match('/^(\d{2}):(\d{2})/', $v, $m)) {
                $time = $m[1] . ':' . $m[2];
            }
            $enabled = (int) ($validated['slot' . $i . '_enabled'] ?? 0) === 1;
            $slots[] = [
                'time' => $time,
                'enabled' => $enabled,
            ];
        }

        $row = RekapVisualFilterCache::settings();
        $row->use_cache = (int) $validated['use_cache'] === 1;
        $row->schedule_enabled = (int) $validated['schedule_enabled'] === 1;
        $row->warm_channel = $validated['warm_channel'];
        $row->ttl_hours = (int) $validated['ttl_hours'];
        $row->warm_year = $validated['warm_year'] !== null && $validated['warm_year'] !== ''
            ? (int) $validated['warm_year']
            : null;
        $row->schedule_slots = $slots;
        $row->save();

        return redirect()
            ->route('rekap-visual-filter-cache.index')
            ->with('success', 'Pengaturan cache rekap-visual-filter disimpan.');
    }

    public function warmNow(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        // Jangan Artisan::call sinkron di HTTP — se-provinsi + semua kabkota sering timeout
        // sebelum key tersimpan, sehingga daftar cache tetap 0 dan dashboard STATS 500.
        if (! RekapVisualFilterCache::dispatchWarmInBackground(true)) {
            return redirect()
                ->route('rekap-visual-filter-cache.index')
                ->with('error', 'Gagal mengantrikan warm. Coba dari CLI: php artisan rvf:warm-cache --force');
        }

        return redirect()
            ->route('rekap-visual-filter-cache.index')
            ->with(
                'success',
                'Warm dijalankan di background. Tunggu beberapa menit, lalu refresh halaman ini — status & daftar cache akan terisi. Log: storage/logs/rvf-warm.log'
            );
    }

    public function clearAll(): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $count = RekapVisualFilterCache::forgetAll();

        return redirect()
            ->route('rekap-visual-filter-cache.index')
            ->with('success', "Berhasil menghapus {$count} cache key prewarm.");
    }

    public function clearSelected(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['required', 'string'],
        ]);

        $deleted = 0;
        foreach ($validated['keys'] as $key) {
            if (!str_starts_with($key, RekapVisualFilterCache::KEY_PREFIX)) {
                continue;
            }
            if (RekapVisualFilterCache::forget($key)) {
                $deleted++;
            }
        }

        return redirect()
            ->route('rekap-visual-filter-cache.index')
            ->with('success', "Berhasil menghapus {$deleted} key terpilih.");
    }

    private function ensureSuperAdmin(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['super-admin', 'superadmin'], 'web'),
            403,
            'Hanya super admin.'
        );
    }
}
