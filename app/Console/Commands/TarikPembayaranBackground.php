<?php

namespace App\Console\Commands;

use App\Models\PembayaranTarikLog;
use App\Services\SengBayarPajakApiImporter;
use App\Support\PembayaranTarik;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class TarikPembayaranBackground extends Command
{
    protected $signature = 'pembayaran:tarik-bg
                            {mulai? : Tanggal mulai Y-m-d}
                            {selesai? : Tanggal selesai Y-m-d}
                            {--lanjut : Lanjutkan tarikan background yang berhenti}
                            {--stop : Hentikan proses yang sedang berjalan, di batas halaman}
                            {--id= : ID riwayat yang dilanjutkan}
                            {--user= : ID user untuk created_by}
                            {--detach : Lepas dari terminal (nohup)}';

    protected $description = 'Tarik pembayaran rentang tanggal di background, bisa berhenti dan dilanjut';

    private bool $stopRequested = false;

    public function handle(SengBayarPajakApiImporter $importer): int
    {
        @set_time_limit(0);

        if ($this->option('stop')) {
            return $this->requestStop();
        }

        if ($this->option('detach')) {
            return $this->detach();
        }

        $this->listenForStop();

        if ($this->option('lanjut') || $this->option('id')) {
            $log = $this->findResumableLog();
            if ($log === null) {
                $this->error('Tidak ada tarikan background yang bisa dilanjut.');

                return self::FAILURE;
            }
        } else {
            $mulai = (string) $this->argument('mulai');
            $selesai = (string) $this->argument('selesai');
            $error = $this->validateRange($mulai, $selesai);
            if ($error !== null) {
                $this->error($error);

                return self::FAILURE;
            }

            if ($this->runningLog() !== null) {
                $this->error('Masih ada tarikan background yang berjalan. Hentikan dulu: php artisan pembayaran:tarik-bg --stop');

                return self::FAILURE;
            }

            $stuck = PembayaranTarikLog::query()
                ->where('jenis', 'background')
                ->where('status', 'berjalan')
                ->orderByDesc('id')
                ->first();
            if ($stuck !== null) {
                $posisi = $stuck->tanggal_proses?->toDateString() ?? '-';
                $this->error('Riwayat #'.$stuck->id.' terputus di '.$posisi.' halaman '.$stuck->halaman.'. Lanjutkan: php artisan pembayaran:tarik-bg --lanjut');

                return self::FAILURE;
            }

            $userId = $this->option('user');
            $log = PembayaranTarikLog::query()->create([
                'jenis' => 'background',
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'tanggal_proses' => $mulai,
                'halaman' => 1,
                'status' => 'berjalan',
                'created_by' => $userId === null || $userId === '' ? null : (int) $userId,
                'started_at' => now(),
                'message' => 'Background mulai '.$mulai.' s/d '.$selesai.'.',
            ]);
        }

        return $this->runLog($importer, $log);
    }

    private function requestStop(): int
    {
        $log = $this->runningLog();
        Cache::put(PembayaranTarik::BG_STOP_KEY, 1, now()->addDay());

        $pid = PembayaranTarik::backgroundPid();
        if ($pid !== null && PembayaranTarik::pidAlive($pid) && function_exists('posix_kill')) {
            posix_kill($pid, SIGTERM);
        }

        if ($log === null && ($pid === null || ! PembayaranTarik::pidAlive($pid))) {
            Cache::forget(PembayaranTarik::BG_STOP_KEY);
            $this->warn('Tidak ada proses background yang sedang berjalan.');

            return self::SUCCESS;
        }

        $this->info('Permintaan berhenti dikirim. Proses selesai di halaman yang sedang dikerjakan, status menjadi berhenti.');

        return self::SUCCESS;
    }

    private function detach(): int
    {
        if ($this->option('stop')) {
            return $this->requestStop();
        }

        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $logFile = storage_path('logs/pembayaran-tarik-bg.log');
        $args = [escapeshellarg($php), escapeshellarg($artisan), 'pembayaran:tarik-bg'];

        if ($this->option('lanjut') || $this->option('id')) {
            $args[] = '--lanjut';
            if ($this->option('id')) {
                $args[] = '--id='.(int) $this->option('id');
            }
        } else {
            $mulai = (string) $this->argument('mulai');
            $selesai = (string) $this->argument('selesai');
            $error = $this->validateRange($mulai, $selesai);
            if ($error !== null) {
                $this->error($error);

                return self::FAILURE;
            }
            $args[] = escapeshellarg($mulai);
            $args[] = escapeshellarg($selesai);
        }

        if ($this->option('user') !== null && $this->option('user') !== '') {
            $args[] = '--user='.(int) $this->option('user');
        }

        $command = 'nohup '.implode(' ', $args).' >> '.escapeshellarg($logFile).' 2>&1 & echo $!';
        $pid = trim((string) shell_exec($command));
        if ($pid === '' || ! ctype_digit($pid)) {
            $this->error('Gagal melepas proses. Jalankan tanpa --detach di dalam screen/tmux.');

            return self::FAILURE;
        }

        $this->info('Tarikan background jalan. PID '.$pid);
        $this->line('Log: '.$logFile);
        $this->line('Stop: php artisan pembayaran:tarik-bg --stop');
        $this->line('Lanjut: php artisan pembayaran:tarik-bg --lanjut');

        return self::SUCCESS;
    }

    private function runLog(SengBayarPajakApiImporter $importer, PembayaranTarikLog $log): int
    {
        $owner = 'bg:'.$log->id;
        if (! PembayaranTarik::acquire($owner)) {
            $this->error('Tarikan lain masih berjalan.');

            return self::FAILURE;
        }

        Cache::forget(PembayaranTarik::BG_STOP_KEY);
        $pid = getmypid() ?: 0;
        PembayaranTarik::writePid($pid);

        $log->status = 'berjalan';
        $log->finished_at = null;
        $log->message = 'Background berjalan. PID '.$pid.'.';
        $log->save();

        $userId = $log->created_by !== null ? (int) $log->created_by : null;
        $tanggal = $log->tanggal_proses?->toDateString() ?? $log->tanggal_mulai->toDateString();
        $page = max(1, (int) $log->halaman);
        $akhir = $log->tanggal_selesai->toDateString();

        try {
            while ($tanggal <= $akhir) {
                if ($this->shouldStop()) {
                    return $this->markStopped($log, $owner, $pid, $tanggal, $page);
                }

                $result = $importer->importPage($tanggal, $page, $userId);
                $log->inserted += $result['inserted'];
                $log->skipped_duplicate += $result['skipped_duplicate'];
                $log->skipped_invalid += $result['skipped_invalid'];
                $log->total_halaman = $result['total_pages'];
                $log->message = $tanggal.' halaman '.$result['page'].'/'.$result['total_pages']
                    .'. Masuk: '.$log->inserted
                    .'. Duplikat: '.$log->skipped_duplicate.'.';

                if ($result['done']) {
                    $next = Carbon::parse($tanggal)->addDay()->toDateString();
                    if ($next <= $akhir) {
                        $tanggal = $next;
                        $page = 1;
                        $log->tanggal_proses = $tanggal;
                        $log->halaman = 1;
                    } else {
                        $log->tanggal_proses = $tanggal;
                        $log->halaman = $result['page'];
                        $log->status = 'selesai';
                        $log->finished_at = now();
                        $log->message = 'Background selesai '.$log->tanggal_mulai->toDateString().' s/d '.$akhir
                            .'. Masuk: '.$log->inserted
                            .'. Duplikat: '.$log->skipped_duplicate
                            .'. Tidak valid: '.$log->skipped_invalid.'.';
                        $log->save();
                        $this->info($log->message);

                        return self::SUCCESS;
                    }
                } else {
                    $page = $result['page'] + 1;
                    $log->tanggal_proses = $tanggal;
                    $log->halaman = $page;
                }

                $log->save();
                PembayaranTarik::acquire($owner);
                $this->line($log->message);
            }
        } catch (Throwable $e) {
            $log->status = 'berhenti';
            $log->finished_at = now();
            $log->tanggal_proses = $tanggal;
            $log->halaman = $page;
            $log->message = 'Berhenti karena error di '.$tanggal.' halaman '.$page.': '.$e->getMessage()
                .'. Lanjutkan: php artisan pembayaran:tarik-bg --lanjut';
            $log->save();
            $this->error($log->message);

            return self::FAILURE;
        } finally {
            PembayaranTarik::release($owner);
            PembayaranTarik::clearPid($pid);
            Cache::forget(PembayaranTarik::BG_STOP_KEY);
        }

        return self::SUCCESS;
    }

    private function markStopped(PembayaranTarikLog $log, string $owner, int $pid, string $tanggal, int $page): int
    {
        $log->status = 'berhenti';
        $log->finished_at = now();
        $log->tanggal_proses = $tanggal;
        $log->halaman = $page;
        $log->message = 'Berhenti di '.$tanggal.' halaman '.$page
            .'. Masuk: '.$log->inserted
            .'. Duplikat: '.$log->skipped_duplicate
            .'. Lanjutkan: php artisan pembayaran:tarik-bg --lanjut';
        $log->save();
        PembayaranTarik::release($owner);
        PembayaranTarik::clearPid($pid);
        Cache::forget(PembayaranTarik::BG_STOP_KEY);
        $this->warn($log->message);

        return self::SUCCESS;
    }

    private function shouldStop(): bool
    {
        return $this->stopRequested || Cache::has(PembayaranTarik::BG_STOP_KEY);
    }

    private function listenForStop(): void
    {
        if (! $this->laravel->runningInConsole()) {
            return;
        }

        if (! function_exists('pcntl_async_signals')) {
            return;
        }

        $this->trap([SIGINT, SIGTERM], function () {
            $this->stopRequested = true;
            $this->warn('Sinyal berhenti diterima. Selesai di halaman ini.');
        });
    }

    private function findResumableLog(): ?PembayaranTarikLog
    {
        $id = $this->option('id');
        $query = PembayaranTarikLog::query()->where('jenis', 'background');
        if ($id !== null && $id !== '') {
            $log = $query->find((int) $id);
        } else {
            $log = $query
                ->whereIn('status', ['berhenti', 'berjalan'])
                ->orderByDesc('id')
                ->first();
        }

        if ($log === null) {
            return null;
        }

        if (! in_array($log->status, ['berhenti', 'berjalan'], true)) {
            $this->error('Riwayat #'.$log->id.' berstatus '.$log->status.' dan tidak bisa dilanjut.');

            return null;
        }

        $pid = PembayaranTarik::backgroundPid();
        if ($log->status === 'berjalan' && $pid !== null && PembayaranTarik::pidAlive($pid) && $pid !== getmypid()) {
            $this->error('Tarikan #'.$log->id.' masih berjalan (PID '.$pid.').');

            return null;
        }

        $posisi = $log->tanggal_proses?->toDateString() ?? '-';
        $this->info('Melanjutkan riwayat #'.$log->id.' dari '.$posisi.' halaman '.$log->halaman.'.');

        return $log;
    }

    private function runningLog(): ?PembayaranTarikLog
    {
        $log = PembayaranTarikLog::query()
            ->where('jenis', 'background')
            ->where('status', 'berjalan')
            ->orderByDesc('id')
            ->first();

        if ($log === null) {
            return null;
        }

        $pid = PembayaranTarik::backgroundPid();
        if ($pid !== null && PembayaranTarik::pidAlive($pid)) {
            return $log;
        }

        return null;
    }

    private function validateRange(string $mulai, string $selesai): ?string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $mulai) !== 1 || preg_match('/^\d{4}-\d{2}-\d{2}$/', $selesai) !== 1) {
            return 'Tanggal mulai dan selesai wajib, format Y-m-d.';
        }

        if ($selesai < $mulai) {
            return 'Tanggal selesai harus sama atau setelah tanggal mulai.';
        }

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        if ($mulai > $today || $selesai > $today) {
            return 'Tanggal tidak boleh lewat hari ini.';
        }

        $days = Carbon::parse($mulai)->startOfDay()->diff(Carbon::parse($selesai)->startOfDay())->days + 1;
        if ($days > PembayaranTarik::MAX_RANGE_DAYS) {
            return 'Rentang maksimal '.PembayaranTarik::MAX_RANGE_DAYS.' hari.';
        }

        return null;
    }
}
