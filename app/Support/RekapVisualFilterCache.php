<?php

namespace App\Support;

use App\Models\RvfCacheSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Prewarm cache untuk rekap-visual-filter: hanya Provinsi + Kabkota.
 */
class RekapVisualFilterCache
{
    /** Semua key RVF harus berawalan ini (untuk hapus aman). */
    public const ROOT_PREFIX = 'rvf:';
    public const KEY_PREFIX = 'rvf:prewarm:v1:';
    public const INDEX_KEY = 'rvf:prewarm:keys_index';
    public const META_PREFIX = 'rvf:prewarm:meta:';
    public const LOCK_KEY = 'rvf:prewarm:lock';
    public const SLOT_GUARD_PREFIX = 'rvf:prewarm:slot:';

    /** Jendela eksekusi setelah jam slot (menit) — toleransi jika cron telat. */
    public const SLOT_TOLERANCE_MINUTES = 5;

    public const DEFAULT_SLOTS = [
        ['time' => '06:00', 'enabled' => true],
        ['time' => '12:00', 'enabled' => true],
        ['time' => '18:00', 'enabled' => true],
        ['time' => '00:00', 'enabled' => true],
    ];
    public const DEFAULT_TTL_HOURS = 12;
    public const DEFAULT_TTL_DETAIL_MINUTES = 60;

    public static function settings(): RvfCacheSetting
    {
        $row = RvfCacheSetting::query()->first();
        if ($row) {
            return $row;
        }

        return RvfCacheSetting::query()->create([
            'use_cache' => false,
            'warm_channel' => 'semua',
            'ttl_hours' => self::DEFAULT_TTL_HOURS,
            'ttl_detail_minutes' => self::DEFAULT_TTL_DETAIL_MINUTES,
            'schedule_enabled' => true,
            'schedule_slots' => self::DEFAULT_SLOTS,
            'warm_year' => null,
        ]);
    }

    public static function useCache(): bool
    {
        try {
            return (bool) self::settings()->use_cache;
        } catch (Throwable $e) {
            Log::warning('RVF cache settings unreadable: ' . $e->getMessage());

            return false;
        }
    }

    public static function scheduleEnabled(): bool
    {
        $row = self::settings();
        if ($row->schedule_enabled === null) {
            return true;
        }

        return (bool) $row->schedule_enabled;
    }

    public static function ttlSeconds(): int
    {
        $hours = (int) (self::settings()->ttl_hours ?: self::DEFAULT_TTL_HOURS);

        return max(1, $hours) * 3600;
    }

    /** TTL untuk filter kecamatan/kelurahan (detik). */
    public static function ttlDetailSeconds(): int
    {
        try {
            $minutes = (int) (self::settings()->ttl_detail_minutes ?: self::DEFAULT_TTL_DETAIL_MINUTES);
        } catch (Throwable $e) {
            $minutes = self::DEFAULT_TTL_DETAIL_MINUTES;
        }

        return max(1, $minutes) * 60;
    }

    public static function isDeepFilter(array $filters): bool
    {
        return trim((string) ($filters['kecamatan_id'] ?? '')) !== ''
            || trim((string) ($filters['kelurahan_id'] ?? '')) !== '';
    }

    public static function ttlForFilters(array $filters): int
    {
        return self::isDeepFilter($filters) ? self::ttlDetailSeconds() : self::ttlSeconds();
    }

    public static function warmYear(): int
    {
        $year = self::settings()->warm_year;

        return $year ? (int) $year : (int) date('Y');
    }

    /**
     * Semua slot (termasuk yang off) untuk form admin.
     *
     * @return list<array{time:string,enabled:bool}>
     */
    public static function scheduleSlotDefs(): array
    {
        $slots = self::settings()->schedule_slots;
        if (!is_array($slots) || $slots === []) {
            return self::DEFAULT_SLOTS;
        }

        $out = [];
        foreach ($slots as $slot) {
            if (is_string($slot)) {
                $time = trim($slot);
                if (preg_match('/^\d{2}:\d{2}$/', $time)) {
                    $out[] = ['time' => $time, 'enabled' => true];
                }
                continue;
            }
            if (!is_array($slot)) {
                continue;
            }
            $time = trim((string) ($slot['time'] ?? ''));
            if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
                continue;
            }
            $out[] = [
                'time' => $time,
                'enabled' => (bool) ($slot['enabled'] ?? true),
            ];
        }

        while (count($out) < 4) {
            $out[] = ['time' => '', 'enabled' => false];
        }

        return array_slice($out, 0, 4);
    }

    /**
     * Jam yang aktif saja (dipakai scheduler).
     *
     * @return list<string>
     */
    public static function scheduleSlots(): array
    {
        if (! self::scheduleEnabled()) {
            return [];
        }

        $out = [];
        foreach (self::scheduleSlotDefs() as $slot) {
            if (!($slot['enabled'] ?? false)) {
                continue;
            }
            $time = trim((string) ($slot['time'] ?? ''));
            if (preg_match('/^\d{2}:\d{2}$/', $time)) {
                $out[] = $time;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return list<string>
     */
    public static function channelsToWarm(): array
    {
        $mode = strtolower(trim((string) self::settings()->warm_channel));

        return match ($mode) {
            'reguler' => ['reguler'],
            'd2d' => ['d2d'],
            default => ['reguler', 'd2d'],
        };
    }

    public static function isProvOrKabOnly(array $filters): bool
    {
        return ! self::isDeepFilter($filters);
    }

    public static function statsKey(
        string $channel,
        int $year,
        string $kabkotaId = '',
        string $kecamatanId = '',
        string $kelurahanId = ''
    ): string {
        return self::KEY_PREFIX . $channel . ':' . $year . ':stats:' . self::scopeSuffix($kabkotaId, $kecamatanId, $kelurahanId);
    }

    public static function breakdownKey(
        string $channel,
        int $year,
        string $kabkotaId = '',
        string $kecamatanId = '',
        string $kelurahanId = ''
    ): string {
        return self::KEY_PREFIX . $channel . ':' . $year . ':breakdown:' . self::scopeSuffix($kabkotaId, $kecamatanId, $kelurahanId);
    }

    public static function mapKey(string $channel, int $year): string
    {
        return self::KEY_PREFIX . $channel . ':' . $year . ':map';
    }

    private static function scopeSuffix(string $kabkotaId, string $kecamatanId = '', string $kelurahanId = ''): string
    {
        $kabkotaId = trim($kabkotaId);
        $kecamatanId = trim($kecamatanId);
        $kelurahanId = trim($kelurahanId);

        if ($kabkotaId === '') {
            return 'prov';
        }

        $suffix = 'kab:' . $kabkotaId;
        if ($kecamatanId !== '') {
            $suffix .= ':kec:' . $kecamatanId;
        }
        if ($kelurahanId !== '') {
            $suffix .= ':kel:' . $kelurahanId;
        }

        return $suffix;
    }

    public static function isRvfKey(string $key): bool
    {
        return str_starts_with($key, self::ROOT_PREFIX);
    }

    public static function put(string $key, mixed $value, ?int $ttlSeconds = null): bool
    {
        try {
            $ttl = $ttlSeconds ?? self::ttlSeconds();
            Cache::put($key, $value, $ttl);
            self::trackKey($key);
            Cache::put(self::META_PREFIX . $key, [
                'stored_at' => now('Asia/Jakarta')->toDateTimeString(),
                'ttl_seconds' => $ttl,
            ], $ttl);

            return true;
        } catch (Throwable $e) {
            Log::warning('RVF cache put gagal [' . $key . ']: ' . $e->getMessage());

            return false;
        }
    }

    public static function get(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (Throwable $e) {
            Log::warning('RVF cache get gagal [' . $key . ']: ' . $e->getMessage());

            return null;
        }
    }

    public static function forget(string $key): bool
    {
        try {
            Cache::forget(self::META_PREFIX . $key);
            $ok = Cache::forget($key);
            self::untrackKey($key);

            return $ok;
        } catch (Throwable $e) {
            Log::warning('RVF cache forget gagal [' . $key . ']: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Jalankan warm di proses terpisah agar request HTTP tidak timeout.
     */
    public static function dispatchWarmInBackground(bool $force = true): bool
    {
        try {
            self::markWarmStart('Warm background diantrikan');
        } catch (Throwable $e) {
            Log::warning('RVF markWarmStart gagal: ' . $e->getMessage());
        }

        $forceFlag = $force ? ' --force' : '';
        $artisan = base_path('artisan');
        $logFile = storage_path('logs/rvf-warm.log');
        $php = 'php';
        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '' && ! str_contains(PHP_BINARY, 'php-fpm')) {
            $php = PHP_BINARY;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        $canExec = function_exists('exec') && ! in_array('exec', $disabled, true);

        if ($canExec) {
            if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
                $cmd = 'start /B "" ' . escapeshellarg($php) . ' ' . escapeshellarg($artisan)
                    . ' rvf:warm-cache' . $forceFlag . ' >> ' . escapeshellarg($logFile) . ' 2>&1';
            } else {
                $cmd = 'nohup ' . escapeshellarg($php) . ' ' . escapeshellarg($artisan)
                    . ' rvf:warm-cache' . $forceFlag . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &';
            }
            try {
                exec($cmd);

                return true;
            } catch (Throwable $e) {
                Log::warning('RVF exec warm gagal, fallback afterResponse: ' . $e->getMessage());
            }
        }

        try {
            dispatch(static function () use ($force) {
                \Illuminate\Support\Facades\Artisan::call('rvf:warm-cache', [
                    '--force' => $force,
                ]);
            })->afterResponse();

            return true;
        } catch (Throwable $e) {
            Log::error('RVF dispatch warm gagal: ' . $e->getMessage());

            return false;
        }
    }

    public static function forgetAll(): int
    {
        $count = 0;
        foreach (self::trackedKeys() as $key) {
            if (! self::isRvfKey($key)) {
                continue;
            }
            if (self::forget($key)) {
                $count++;
            }
        }

        // Bersihkan index & meta orphan berawalan rvf:
        try {
            Cache::forget(self::INDEX_KEY);
        } catch (Throwable $e) {
            // ignore
        }

        return $count;
    }

    /**
     * @return list<string>
     */
    public static function trackedKeys(): array
    {
        $keys = Cache::get(self::INDEX_KEY, []);
        if (!is_array($keys)) {
            return [];
        }
        sort($keys);

        return array_values(array_unique($keys));
    }

    /**
     * @return list<array{key:string,stored_at:?string,ttl_seconds:?int,exists:bool}>
     */
    public static function listedKeys(): array
    {
        $out = [];
        foreach (self::trackedKeys() as $key) {
            $meta = Cache::get(self::META_PREFIX . $key, []);
            $out[] = [
                'key' => $key,
                'stored_at' => is_array($meta) ? ($meta['stored_at'] ?? null) : null,
                'ttl_seconds' => is_array($meta) ? ($meta['ttl_seconds'] ?? null) : null,
                'exists' => Cache::has($key),
            ];
        }

        return $out;
    }

    private static function trackKey(string $key): void
    {
        $keys = Cache::get(self::INDEX_KEY, []);
        if (!is_array($keys)) {
            $keys = [];
        }
        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever(self::INDEX_KEY, $keys);
        }
    }

    private static function untrackKey(string $key): void
    {
        $keys = Cache::get(self::INDEX_KEY, []);
        if (!is_array($keys)) {
            return;
        }
        $keys = array_values(array_filter($keys, static fn ($item) => $item !== $key));
        Cache::forever(self::INDEX_KEY, $keys);
    }

    /**
     * Slot aktif yang sedang dalam jendela toleransi (null jika tidak ada).
     */
    public static function matchingScheduleSlot(?Carbon $now = null): ?string
    {
        if (! self::scheduleEnabled()) {
            return null;
        }

        $activeSlots = self::scheduleSlots();
        if ($activeSlots === []) {
            return null;
        }

        $now = ($now ?? Carbon::now('Asia/Jakarta'))->timezone('Asia/Jakarta');
        $date = $now->format('Y-m-d');

        foreach ($activeSlots as $slotHm) {
            $slotStart = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $slotHm, 'Asia/Jakarta');
            if ($slotStart === false) {
                continue;
            }
            $slotEnd = $slotStart->copy()->addMinutes(self::SLOT_TOLERANCE_MINUTES);
            if ($now->gte($slotStart) && $now->lt($slotEnd)) {
                return $slotHm;
            }
        }

        return null;
    }

    public static function shouldDispatchWarm(): bool
    {
        $slotHm = self::matchingScheduleSlot();
        if ($slotHm === null) {
            return false;
        }

        $now = Carbon::now('Asia/Jakarta');
        $guard = self::SLOT_GUARD_PREFIX . $now->format('Y-m-d') . ':' . $slotHm;
        if (Cache::has($guard)) {
            return false;
        }

        Cache::put($guard, 1, 7200);

        return true;
    }

    public static function markWarmStart(string $message = 'Berjalan'): void
    {
        $row = self::settings();
        $row->last_warm_started_at = now('Asia/Jakarta');
        $row->last_warm_finished_at = null;
        $row->last_warm_status = 'running';
        $row->last_warm_message = $message;
        $row->save();
    }

    public static function markWarmFinish(string $status, string $message): void
    {
        $row = self::settings();
        $row->last_warm_finished_at = now('Asia/Jakarta');
        $row->last_warm_status = $status;
        $row->last_warm_message = $message;
        $row->save();
    }
}
