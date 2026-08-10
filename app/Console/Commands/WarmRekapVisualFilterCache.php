<?php

namespace App\Console\Commands;

use App\Http\Controllers\RekapVisualFilterController;
use App\Http\Controllers\RekapVisualFilterD2dController;
use App\Support\RekapVisualFilterCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class WarmRekapVisualFilterCache extends Command
{
    protected $signature = 'rvf:warm-cache
                            {--channel= : reguler|d2d|semua (override setting)}
                            {--year= : Tahun (override setting)}
                            {--force : Abaikan lock jika ada}';

    protected $description = 'Warm cache rekap-visual-filter (Provinsi + Kabkota) secara berurutan';

    public function handle(): int
    {
        @set_time_limit(0);

        $lock = Cache::lock(RekapVisualFilterCache::LOCK_KEY, 7200);
        if (! $this->option('force') && ! $lock->get()) {
            $this->warn('Warm cache masih berjalan (lock aktif).');

            return self::SUCCESS;
        }

        try {
            $year = $this->option('year') !== null && $this->option('year') !== ''
                ? (int) $this->option('year')
                : RekapVisualFilterCache::warmYear();

            $channelOpt = strtolower(trim((string) ($this->option('channel') ?? '')));
            $channels = match ($channelOpt) {
                'reguler' => ['reguler'],
                'd2d' => ['d2d'],
                'semua' => ['reguler', 'd2d'],
                default => RekapVisualFilterCache::channelsToWarm(),
            };

            RekapVisualFilterCache::markWarmStart(
                'Mulai warm tahun ' . $year . ' channel: ' . implode(',', $channels)
            );
            $this->info('Warm mulai · tahun=' . $year . ' · channel=' . implode(',', $channels));

            $totalKeys = 0;
            foreach ($channels as $channel) {
                $controller = $channel === 'd2d'
                    ? app(RekapVisualFilterD2dController::class)
                    : app(RekapVisualFilterController::class);

                $this->line('— Channel ' . strtoupper($channel));
                $keys = $controller->warmPrewarmForYear($year, function (string $msg) {
                    $this->line('  ' . $msg);
                });
                $totalKeys += count($keys);
            }

            $msg = "Selesai. {$totalKeys} key di-warm (tahun {$year}).";
            RekapVisualFilterCache::markWarmFinish('success', $msg);
            $this->info($msg);

            return self::SUCCESS;
        } catch (Throwable $e) {
            RekapVisualFilterCache::markWarmFinish('failed', $e->getMessage());
            $this->error('Warm gagal: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            if (isset($lock)) {
                try {
                    $lock->release();
                } catch (Throwable $e) {
                    // ignore
                }
            }
        }
    }
}
