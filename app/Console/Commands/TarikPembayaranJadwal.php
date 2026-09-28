<?php

namespace App\Console\Commands;

use App\Models\PembayaranTarikLog;
use App\Services\SengBayarPajakApiImporter;
use App\Support\PembayaranTarik;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class TarikPembayaranJadwal extends Command
{
    protected $signature = 'pembayaran:tarik-jadwal';

    protected $description = 'Tarik pembayaran harian sesuai jam jadwal (hari ini atau kemarin)';

    public function handle(SengBayarPajakApiImporter $importer): int
    {
        @set_time_limit(0);

        $jadwal = PembayaranTarik::settings();
        if (! $jadwal->aktif) {
            return self::SUCCESS;
        }

        $now = Carbon::now('Asia/Jakarta');
        $jam = (string) $jadwal->jam;
        $guard = PembayaranTarik::guardKey($now->toDateString(), $jam);
        if (! Cache::add($guard, 1, now()->addHours(20))) {
            return self::SUCCESS;
        }

        $owner = 'jadwal';
        if (! PembayaranTarik::acquire($owner)) {
            Cache::forget($guard);
            $this->warn('Tarikan lain masih berjalan. Jadwal ditunda ke menit berikutnya dalam jendela jam.');

            return self::SUCCESS;
        }

        $tanggal = PembayaranTarik::targetDate($now);
        $log = PembayaranTarikLog::query()->create([
            'jenis' => 'jadwal',
            'tanggal_mulai' => $tanggal,
            'tanggal_selesai' => $tanggal,
            'tanggal_proses' => $tanggal,
            'halaman' => 1,
            'status' => 'berjalan',
            'started_at' => now(),
            'message' => 'Jadwal mulai menarik '.$tanggal.'.',
        ]);

        $jadwal->last_run_at = now();
        $jadwal->last_status = 'berjalan';
        $jadwal->last_tanggal = $tanggal;
        $jadwal->last_message = $log->message;
        $jadwal->save();

        try {
            $page = 1;
            do {
                $result = $importer->importPage($tanggal, $page, null);
                $log->inserted += $result['inserted'];
                $log->skipped_duplicate += $result['skipped_duplicate'];
                $log->skipped_invalid += $result['skipped_invalid'];
                $log->total_halaman = $result['total_pages'];
                $log->halaman = $result['page'];
                $log->message = $tanggal.' halaman '.$result['page'].'/'.$result['total_pages']
                    .'. Masuk: '.$log->inserted.'.';
                $log->save();
                PembayaranTarik::acquire($owner);
                $this->line($log->message);
                $page++;
            } while (! $result['done']);

            $log->status = 'selesai';
            $log->finished_at = now();
            $log->message = 'Jadwal selesai '.$tanggal
                .'. Masuk: '.$log->inserted
                .'. Duplikat: '.$log->skipped_duplicate
                .'. Tidak valid: '.$log->skipped_invalid.'.';
            $log->save();

            $jadwal->last_status = 'selesai';
            $jadwal->last_message = $log->message;
            $jadwal->save();
            $this->info($log->message);

            return self::SUCCESS;
        } catch (Throwable $e) {
            Cache::forget($guard);
            $log->status = 'gagal';
            $log->finished_at = now();
            $log->message = $e->getMessage();
            $log->save();

            $jadwal->last_status = 'gagal';
            $jadwal->last_message = $e->getMessage();
            $jadwal->save();
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            PembayaranTarik::release($owner);
        }
    }
}
