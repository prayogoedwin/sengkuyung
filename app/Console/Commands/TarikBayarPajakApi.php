<?php

namespace App\Console\Commands;

use App\Services\SengBayarPajakApiImporter;
use Illuminate\Console\Command;
use Throwable;

class TarikBayarPajakApi extends Command
{
    protected $signature = 'bayar-pajak:tarik
                            {tanggal : Tanggal penetapan (Y-m-d)}
                            {--user= : ID user untuk created_by}';

    protected $description = 'Isi seng_bayar_pajak dari API dataTransaksi Jasa Raharja untuk satu tanggal penetapan';

    public function handle(SengBayarPajakApiImporter $importer): int
    {
        @set_time_limit(0);

        $tanggal = (string) $this->argument('tanggal');
        $userId = $this->option('user');
        $userId = $userId === null || $userId === '' ? null : (int) $userId;

        try {
            $result = $importer->importTanggal($tanggal, $userId);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(
            'Selesai '.$tanggal
            .'. Masuk: '.$result['inserted']
            .'. Duplikat: '.$result['skipped_duplicate']
            .'. Tidak valid: '.$result['skipped_invalid'].'.'
        );

        return self::SUCCESS;
    }
}
