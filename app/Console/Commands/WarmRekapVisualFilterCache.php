<?php

namespace App\Console\Commands;

use App\Http\Controllers\RekapVisualFilterController;
use App\Http\Controllers\RekapVisualFilterD2dController;
use App\Models\SengWilayah;
use App\Support\MoneyShortFormatter;
use App\Support\RekapVisualFilterCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class WarmRekapVisualFilterCache extends Command
{
    protected $signature = 'rvf:warm-cache
                            {--channel= : reguler|d2d|semua (override setting)}
                            {--year= : Tahun (override setting)}
                            {--only= : provinsi|kabkota|all (default all)}
                            {--force : Abaikan lock jika ada}';

    protected $description = 'Warm cache RVF bertahap: Provinsi semua→reguler→d2d, baru Kabkota';

    private float $startedAt = 0;

    public function handle(): int
    {
        @set_time_limit(0);
        $this->startedAt = microtime(true);

        try {
            DB::statement('SET SESSION default_tmp_storage_engine = InnoDB');
        } catch (Throwable $e) {
            // ignore
        }

        $lock = Cache::lock(RekapVisualFilterCache::LOCK_KEY, 7200);
        if (! $this->option('force') && ! $lock->get()) {
            $this->warn('Warm cache masih berjalan (lock aktif).');

            return self::SUCCESS;
        }

        try {
            $year = $this->option('year') !== null && $this->option('year') !== ''
                ? (int) $this->option('year')
                : RekapVisualFilterCache::warmYear();

            $only = strtolower(trim((string) ($this->option('only') ?? 'all')));
            if (! in_array($only, ['provinsi', 'kabkota', 'all'], true)) {
                $only = 'all';
            }

            $channelOpt = strtolower(trim((string) ($this->option('channel') ?? '')));
            $baseChannels = match ($channelOpt) {
                'reguler' => ['reguler'],
                'd2d' => ['d2d'],
                'semua' => ['reguler', 'd2d'],
                default => RekapVisualFilterCache::channelsToWarm(),
            };

            $wantReguler = in_array('reguler', $baseChannels, true);
            $wantD2d = in_array('d2d', $baseChannels, true);
            $wantSemua = $wantReguler && $wantD2d;

            RekapVisualFilterCache::markWarmStart(
                'Mulai warm tahun ' . $year . ' · only=' . $only
                . ' · ' . implode(',', $baseChannels)
            );

            $this->info('Warm mulai · tahun=' . $year . ' · only=' . $only);
            $this->comment('Satu proses otomatis: REGULER → D2D → SEMUA (merge), lalu Kabkota. Tiap tahap langsung disimpan.');

            $reguler = app(RekapVisualFilterController::class);
            $d2d = app(RekapVisualFilterD2dController::class);
            $totalKeys = 0;

            if ($only === 'provinsi' || $only === 'all') {
                $this->line('');
                $this->info('=== FASE 1: PROVINSI ===');
                $this->updateWarmStatus('running', 'Fase Provinsi: menghitung semua → reguler → d2d…');
                $totalKeys += $this->warmProvinsiPhase($year, $reguler, $d2d, $wantReguler, $wantD2d, $wantSemua);
                $this->updateWarmStatus('running', "Fase Provinsi selesai ({$totalKeys} key). Lanjut Kabkota…");
            }

            if ($only === 'kabkota' || $only === 'all') {
                $this->line('');
                $this->info('=== FASE 2: KABKOTA ===');
                $this->updateWarmStatus('running', 'Fase Kabkota: berjalan…');
                $totalKeys += $this->warmKabkotaPhase($year, $reguler, $d2d, $wantReguler, $wantD2d, $wantSemua);
            }

            $msg = "Selesai. {$totalKeys} key di-warm (tahun {$year}, only={$only}).";
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

    private function tick(string $msg): void
    {
        $sec = (int) round(microtime(true) - $this->startedAt);
        $line = sprintf('%s (+%ds)', $msg, $sec);
        $this->line('  ' . $line);
        $this->updateWarmStatus('running', $line);
    }

    private function updateWarmStatus(string $status, string $message): void
    {
        try {
            $row = RekapVisualFilterCache::settings();
            $row->last_warm_status = $status;
            $row->last_warm_message = $message;
            if ($status === 'running' && ! $row->last_warm_started_at) {
                $row->last_warm_started_at = now('Asia/Jakarta');
            }
            $row->save();
        } catch (Throwable $e) {
            // jangan gagalkan warm hanya karena gagal update status
        }
    }

    private function filters(int $year, string $kabkotaId = ''): array
    {
        return [
            'year' => $year,
            'kabkota_id' => $kabkotaId,
            'kecamatan_id' => '',
            'kelurahan_id' => '',
        ];
    }

    /**
     * Per channel selesai utuh dulu: stats + breakdown (ringkasan kabkota) + map.
     * Urutan: REGULER lengkap → D2D lengkap → SEMUA (merge) → Kabkota.
     * Ringkasan "Seluruh Provinsi" = key breakdown:prov (bukan 1 key per kabkota).
     *
     * @return int jumlah key tertulis
     */
    private function warmProvinsiPhase(
        int $year,
        RekapVisualFilterController $reguler,
        RekapVisualFilterD2dController $d2d,
        bool $wantReguler,
        bool $wantD2d,
        bool $wantSemua
    ): int {
        $written = 0;
        $filters = $this->filters($year);

        $statsReg = null;
        $statsD2d = null;
        $bdReg = null;
        $bdD2d = null;
        $mapReg = null;
        $mapD2d = null;

        if ($wantReguler) {
            $this->tick('Provinsi REGULER stats · hitung + simpan…');
            $statsReg = $reguler->warmBuildStats($filters);
            RekapVisualFilterCache::put(RekapVisualFilterCache::statsKey('reguler', $year, ''), $statsReg);
            $written++;

            $this->tick('Provinsi REGULER breakdown/ringkasan kabkota · hitung + simpan…');
            $bdReg = $reguler->warmBuildBreakdown($filters);
            RekapVisualFilterCache::put(RekapVisualFilterCache::breakdownKey('reguler', $year, ''), $bdReg);
            $written++;

            $this->tick('Map REGULER · hitung + simpan…');
            $mapReg = $reguler->warmBuildMap($year);
            RekapVisualFilterCache::put(RekapVisualFilterCache::mapKey('reguler', $year), $mapReg);
            $written++;

            $this->info('Provinsi REGULER lengkap — stats + ringkasan kabkota + map siap dipakai.');
        }

        if ($wantD2d) {
            $this->tick('Provinsi D2D stats · hitung + simpan…');
            $statsD2d = $d2d->warmBuildStats($filters);
            RekapVisualFilterCache::put(RekapVisualFilterCache::statsKey('d2d', $year, ''), $statsD2d);
            $written++;

            $this->tick('Provinsi D2D breakdown/ringkasan kabkota · hitung + simpan…');
            $bdD2d = $d2d->warmBuildBreakdown($filters);
            RekapVisualFilterCache::put(RekapVisualFilterCache::breakdownKey('d2d', $year, ''), $bdD2d);
            $written++;

            $this->tick('Map D2D · hitung + simpan…');
            $mapD2d = $d2d->warmBuildMap($year);
            RekapVisualFilterCache::put(RekapVisualFilterCache::mapKey('d2d', $year), $mapD2d);
            $written++;

            $this->info('Provinsi D2D lengkap.');
        }

        if ($wantSemua && $statsReg && $statsD2d) {
            $this->tick('Provinsi SEMUA stats · merge + simpan…');
            RekapVisualFilterCache::put(
                RekapVisualFilterCache::statsKey('semua', $year, ''),
                $this->mergeStats($statsReg, $statsD2d)
            );
            $written++;
        }
        if ($wantSemua && $bdReg && $bdD2d) {
            $this->tick('Provinsi SEMUA breakdown · merge + simpan…');
            RekapVisualFilterCache::put(
                RekapVisualFilterCache::breakdownKey('semua', $year, ''),
                $this->mergeBreakdown($bdReg, $bdD2d)
            );
            $written++;
        }
        if ($wantSemua && $mapReg !== null && $mapD2d !== null) {
            $this->tick('Map SEMUA · merge + simpan…');
            RekapVisualFilterCache::put(
                RekapVisualFilterCache::mapKey('semua', $year),
                $this->mergeRows($mapReg, $mapD2d)
            );
            $written++;
        }

        $this->info("Fase Provinsi selesai ({$written} key). Lanjut Kabkota…");

        return $written;
    }

    /**
     * @return int
     */
    private function warmKabkotaPhase(
        int $year,
        RekapVisualFilterController $reguler,
        RekapVisualFilterD2dController $d2d,
        bool $wantReguler,
        bool $wantD2d,
        bool $wantSemua
    ): int {
        $kabkotas = SengWilayah::query()
            ->where('id_up', 33)
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $written = 0;
        $failed = 0;
        $i = 0;
        $total = $kabkotas->count();

        foreach ($kabkotas as $kab) {
            $i++;
            $kabId = (string) $kab->id;
            $nama = (string) $kab->nama;
            $filters = $this->filters($year, $kabId);

            $this->line("— Kabkota {$i}/{$total}: {$nama}");

            try {
                $statsReg = null;
                $statsD2d = null;
                if ($wantReguler) {
                    $key = RekapVisualFilterCache::statsKey('reguler', $year, $kabId);
                    if (is_array(RekapVisualFilterCache::get($key))) {
                        $this->tick('  stats REGULER · skip (sudah ada)');
                        $statsReg = RekapVisualFilterCache::get($key);
                    } else {
                        $this->tick('  stats REGULER · hitung + simpan…');
                        $statsReg = $reguler->warmBuildStats($filters);
                        RekapVisualFilterCache::put($key, $statsReg);
                        $written++;
                    }
                }
                if ($wantD2d) {
                    $key = RekapVisualFilterCache::statsKey('d2d', $year, $kabId);
                    if (is_array(RekapVisualFilterCache::get($key))) {
                        $this->tick('  stats D2D · skip (sudah ada)');
                        $statsD2d = RekapVisualFilterCache::get($key);
                    } else {
                        $this->tick('  stats D2D · hitung + simpan…');
                        $statsD2d = $d2d->warmBuildStats($filters);
                        RekapVisualFilterCache::put($key, $statsD2d);
                        $written++;
                    }
                }
                if ($wantSemua && $statsReg && $statsD2d) {
                    $key = RekapVisualFilterCache::statsKey('semua', $year, $kabId);
                    if (! is_array(RekapVisualFilterCache::get($key))) {
                        RekapVisualFilterCache::put($key, $this->mergeStats($statsReg, $statsD2d));
                        $written++;
                        $this->tick('  stats SEMUA · merge tersimpan');
                    }
                }

                $bdReg = null;
                $bdD2d = null;
                if ($wantReguler) {
                    $key = RekapVisualFilterCache::breakdownKey('reguler', $year, $kabId);
                    if (is_array(RekapVisualFilterCache::get($key))) {
                        $this->tick('  breakdown REGULER · skip (sudah ada)');
                        $bdReg = RekapVisualFilterCache::get($key);
                    } else {
                        $this->tick('  breakdown REGULER · hitung + simpan…');
                        $bdReg = $reguler->warmBuildBreakdown($filters);
                        RekapVisualFilterCache::put($key, $bdReg);
                        $written++;
                    }
                }
                if ($wantD2d) {
                    $key = RekapVisualFilterCache::breakdownKey('d2d', $year, $kabId);
                    if (is_array(RekapVisualFilterCache::get($key))) {
                        $this->tick('  breakdown D2D · skip (sudah ada)');
                        $bdD2d = RekapVisualFilterCache::get($key);
                    } else {
                        $this->tick('  breakdown D2D · hitung + simpan…');
                        $bdD2d = $d2d->warmBuildBreakdown($filters);
                        RekapVisualFilterCache::put($key, $bdD2d);
                        $written++;
                    }
                }
                if ($wantSemua && $bdReg && $bdD2d) {
                    $key = RekapVisualFilterCache::breakdownKey('semua', $year, $kabId);
                    if (! is_array(RekapVisualFilterCache::get($key))) {
                        RekapVisualFilterCache::put($key, $this->mergeBreakdown($bdReg, $bdD2d));
                        $written++;
                        $this->tick('  breakdown SEMUA · merge tersimpan');
                    }
                }
            } catch (Throwable $e) {
                $failed++;
                $msg = "Kabkota {$nama} gagal: " . $e->getMessage();
                $this->error('  ! ' . $msg);
                $this->updateWarmStatus('running', $msg . ' — lanjut kabkota berikutnya');
            }
        }

        if ($failed > 0) {
            $this->warn("Fase Kabkota: {$failed} kabkota gagal, {$written} key baru tersimpan. Jalankan ulang untuk melanjutkan (yang sudah ada di-skip).");
        }

        return $written;
    }

    /**
     * @param  array{stats:array,bayar:array}  $a
     * @param  array{stats:array,bayar:array}  $b
     * @return array{stats:array,bayar:array}
     */
    private function mergeStats(array $a, array $b): array
    {
        $sa = $a['stats'] ?? [];
        $sb = $b['stats'] ?? [];
        $ba = $a['bayar'] ?? [];
        $bb = $b['bayar'] ?? [];

        $sum = static fn ($x, $y) => (int) ($x ?? 0) + (int) ($y ?? 0);

        $stats = [
            'jumlah_tunggakan' => $sum($sa['jumlah_tunggakan'] ?? 0, $sb['jumlah_tunggakan'] ?? 0),
            'jumlah_sudah_pendataan' => $sum($sa['jumlah_sudah_pendataan'] ?? 0, $sb['jumlah_sudah_pendataan'] ?? 0),
            'jumlah_belum_pendataan' => $sum($sa['jumlah_belum_pendataan'] ?? 0, $sb['jumlah_belum_pendataan'] ?? 0),
            'menunggu_verifikasi' => $sum($sa['menunggu_verifikasi'] ?? 0, $sb['menunggu_verifikasi'] ?? 0),
            'verifikasi' => $sum($sa['verifikasi'] ?? 0, $sb['verifikasi'] ?? 0),
            'ditolak' => $sum($sa['ditolak'] ?? 0, $sb['ditolak'] ?? 0),
        ];
        $stats['pct_dikunjungi'] = $stats['jumlah_tunggakan'] > 0
            ? round(($stats['jumlah_sudah_pendataan'] / $stats['jumlah_tunggakan']) * 100, 2)
            : 0.0;

        $bayar = [
            'jumlah_terbayar' => $sum($ba['jumlah_terbayar'] ?? 0, $bb['jumlah_terbayar'] ?? 0),
            'nominal_provinsi' => $sum($ba['nominal_provinsi'] ?? 0, $bb['nominal_provinsi'] ?? 0),
            'nominal_opsen' => $sum($ba['nominal_opsen'] ?? 0, $bb['nominal_opsen'] ?? 0),
            'nominal_total' => $sum($ba['nominal_total'] ?? 0, $bb['nominal_total'] ?? 0),
            'sebelum_pendataan' => $sum($ba['sebelum_pendataan'] ?? 0, $bb['sebelum_pendataan'] ?? 0),
            'sesudah_pendataan' => $sum($ba['sesudah_pendataan'] ?? 0, $bb['sesudah_pendataan'] ?? 0),
            'sebelum_pendataan_provinsi' => $sum($ba['sebelum_pendataan_provinsi'] ?? 0, $bb['sebelum_pendataan_provinsi'] ?? 0),
            'sebelum_pendataan_opsen' => $sum($ba['sebelum_pendataan_opsen'] ?? 0, $bb['sebelum_pendataan_opsen'] ?? 0),
            'sesudah_pendataan_provinsi' => $sum($ba['sesudah_pendataan_provinsi'] ?? 0, $bb['sesudah_pendataan_provinsi'] ?? 0),
            'sesudah_pendataan_opsen' => $sum($ba['sesudah_pendataan_opsen'] ?? 0, $bb['sesudah_pendataan_opsen'] ?? 0),
            'potensi_total' => $sum($ba['potensi_total'] ?? 0, $bb['potensi_total'] ?? 0),
            'potensi_provinsi' => $sum($ba['potensi_provinsi'] ?? 0, $bb['potensi_provinsi'] ?? 0),
            'potensi_opsen' => $sum($ba['potensi_opsen'] ?? 0, $bb['potensi_opsen'] ?? 0),
        ];
        $bayar['nominal_provinsi_fmt'] = MoneyShortFormatter::format($bayar['nominal_provinsi']);
        $bayar['nominal_opsen_fmt'] = MoneyShortFormatter::format($bayar['nominal_opsen']);
        $bayar['nominal_total_fmt'] = MoneyShortFormatter::format($bayar['nominal_total']);
        $bayar['sebelum_pendataan_provinsi_fmt'] = MoneyShortFormatter::format($bayar['sebelum_pendataan_provinsi']);
        $bayar['sebelum_pendataan_opsen_fmt'] = MoneyShortFormatter::format($bayar['sebelum_pendataan_opsen']);
        $bayar['sesudah_pendataan_provinsi_fmt'] = MoneyShortFormatter::format($bayar['sesudah_pendataan_provinsi']);
        $bayar['sesudah_pendataan_opsen_fmt'] = MoneyShortFormatter::format($bayar['sesudah_pendataan_opsen']);
        $bayar['potensi_total_fmt'] = MoneyShortFormatter::format($bayar['potensi_total']);
        $bayar['potensi_provinsi_fmt'] = MoneyShortFormatter::format($bayar['potensi_provinsi']);
        $bayar['potensi_opsen_fmt'] = MoneyShortFormatter::format($bayar['potensi_opsen']);
        $bayar['pct_bayar_vs_potensi'] = $bayar['potensi_total'] > 0
            ? round(($bayar['nominal_total'] / $bayar['potensi_total']) * 100, 2)
            : 0.0;

        return ['stats' => $stats, 'bayar' => $bayar];
    }

    /**
     * @param  array{level:string,rows:list<array>}  $a
     * @param  array{level:string,rows:list<array>}  $b
     * @return array{level:string,rows:list<array>}
     */
    private function mergeBreakdown(array $a, array $b): array
    {
        return [
            'level' => $a['level'] ?? $b['level'] ?? 'kabkota',
            'rows' => $this->mergeRows($a['rows'] ?? [], $b['rows'] ?? []),
        ];
    }

    /**
     * @param  list<array<string,mixed>>  $rowsA
     * @param  list<array<string,mixed>>  $rowsB
     * @return list<array<string,mixed>>
     */
    private function mergeRows(array $rowsA, array $rowsB): array
    {
        $map = [];
        foreach ([$rowsA, $rowsB] as $rows) {
            foreach ($rows as $row) {
                $id = (string) ($row['id'] ?? '');
                if ($id === '') {
                    continue;
                }
                if (! isset($map[$id])) {
                    $map[$id] = [
                        'id' => $id,
                        'nama' => $row['nama'] ?? $id,
                        'tagihan' => 0,
                        'pendataan' => 0,
                        'bayar' => 0,
                        'bayar_sesudah' => 0,
                        'lat' => $row['lat'] ?? null,
                        'lng' => $row['lng'] ?? null,
                    ];
                }
                $map[$id]['tagihan'] += (int) ($row['tagihan'] ?? 0);
                $map[$id]['pendataan'] += (int) ($row['pendataan'] ?? 0);
                $map[$id]['bayar'] += (int) ($row['bayar'] ?? 0);
                $map[$id]['bayar_sesudah'] += (int) ($row['bayar_sesudah'] ?? 0);
                if (($map[$id]['lat'] ?? null) === null && isset($row['lat'])) {
                    $map[$id]['lat'] = $row['lat'];
                }
                if (($map[$id]['lng'] ?? null) === null && isset($row['lng'])) {
                    $map[$id]['lng'] = $row['lng'];
                }
                if (empty($map[$id]['nama']) && ! empty($row['nama'])) {
                    $map[$id]['nama'] = $row['nama'];
                }
            }
        }

        $out = [];
        foreach ($map as $r) {
            $tagihan = (int) $r['tagihan'];
            $pendataan = (int) $r['pendataan'];
            $bayar = (int) $r['bayar'];
            $bayarSesudah = (int) $r['bayar_sesudah'];
            $r['bayar_pct'] = $tagihan > 0 ? round(($bayar / $tagihan) * 100, 2) : 0.0;
            $r['success_rate'] = $pendataan > 0 ? round(($bayarSesudah / $pendataan) * 100, 2) : 0.0;
            $out[] = $r;
        }

        return $out;
    }
}
