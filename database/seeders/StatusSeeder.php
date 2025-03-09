<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            'DIMILIKI',
            'GANTI KEPEMILIKAN',
            'RUSAK BERAT',
            'HILANG',
            'MENINGGAL DUNIA TANPA AHLI WARIS',
            'MENUTUP USAHA / PAILIT',
            'DICABUT REGISTRASINYA',
            'TERKENA BENCANA ALAM',
            'TIDAK PUNYA KEKAYAAN LAGI',
            'TIDAK DIKETAHUI ALAMAT / KEDUDUKANNYA'
        ];

        $now = Carbon::now();

        foreach ($statuses as $index => $status) {
            DB::table('seng_status')->insert([
                'id' => $index + 1, // Ambil index dan tambah 1 agar id mulai dari 1
                'nama' => $status,
                'keterangan' => '',
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }
    }
}
