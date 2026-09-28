<?php

namespace App\Support;

use App\Models\PembayaranJadwal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PembayaranTarik
{
    public const LOCK_KEY = 'pembayaran:tarik:active';

    public const BG_STOP_KEY = 'pembayaran:tarik:bg-stop';

    public const SLOT_TOLERANCE_MINUTES = 5;

    public const MAX_RANGE_DAYS = 1100;

    public static function settings(): PembayaranJadwal
    {
        $row = PembayaranJadwal::query()->first();
        if ($row) {
            return $row;
        }

        return PembayaranJadwal::query()->create([
            'aktif' => false,
            'jam' => '06:00',
            'tanggal_sumber' => 'kemarin',
        ]);
    }

    public static function targetDate(?Carbon $now = null): string
    {
        $now = ($now ?? Carbon::now('Asia/Jakarta'))->timezone('Asia/Jakarta');
        $row = self::settings();

        if ($row->tanggal_sumber === 'hari_ini') {
            return $now->toDateString();
        }

        return $now->copy()->subDay()->toDateString();
    }

    public static function shouldDispatch(?Carbon $now = null): bool
    {
        $row = self::settings();
        if (! $row->aktif) {
            return false;
        }

        $slot = self::matchingSlot((string) $row->jam, $now);
        if ($slot === null) {
            return false;
        }

        $now = ($now ?? Carbon::now('Asia/Jakarta'))->timezone('Asia/Jakarta');

        return ! Cache::has(self::guardKey($now->toDateString(), $slot));
    }

    public static function guardKey(string $date, string $jam): string
    {
        return 'pembayaran:jadwal:'.$date.':'.$jam;
    }

    public static function matchingSlot(string $jam, ?Carbon $now = null): ?string
    {
        if (preg_match('/^\d{2}:\d{2}$/', $jam) !== 1) {
            return null;
        }

        $now = ($now ?? Carbon::now('Asia/Jakarta'))->timezone('Asia/Jakarta');
        $start = Carbon::createFromFormat('Y-m-d H:i', $now->format('Y-m-d').' '.$jam, 'Asia/Jakarta');
        if ($start === false) {
            return null;
        }

        $end = $start->copy()->addMinutes(self::SLOT_TOLERANCE_MINUTES);
        if ($now->greaterThanOrEqualTo($start) && $now->lessThan($end)) {
            return $jam;
        }

        return null;
    }

    public static function acquire(string $owner): bool
    {
        $current = Cache::get(self::LOCK_KEY);
        if (is_string($current) && $current !== '' && $current !== $owner) {
            return false;
        }

        Cache::put(self::LOCK_KEY, $owner, now()->addMinutes(20));

        return true;
    }

    public static function release(string $owner): void
    {
        if (Cache::get(self::LOCK_KEY) === $owner) {
            Cache::forget(self::LOCK_KEY);
        }
    }

    public static function pidPath(): string
    {
        return storage_path('app/pembayaran-tarik-bg.pid');
    }

    public static function backgroundPid(): ?int
    {
        $path = self::pidPath();
        if (! is_file($path)) {
            return null;
        }

        $pid = (int) trim((string) file_get_contents($path));

        return $pid > 0 ? $pid : null;
    }

    public static function pidAlive(?int $pid): bool
    {
        if ($pid === null || $pid <= 0) {
            return false;
        }

        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        return is_dir('/proc/'.$pid);
    }

    public static function writePid(int $pid): void
    {
        file_put_contents(self::pidPath(), (string) $pid);
    }

    public static function clearPid(int $pid): void
    {
        if (self::backgroundPid() === $pid && is_file(self::pidPath())) {
            unlink(self::pidPath());
        }
    }
}
