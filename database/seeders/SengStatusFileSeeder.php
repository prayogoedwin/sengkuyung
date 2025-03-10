<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SengStatusFileSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            1 => 'DIMILIKI',
            2 => 'GANTI KEPEMILIKAN',
            3 => 'RUSAK BERAT',
            4 => 'HILANG',
            5 => 'MENINGGAL DUNIA TANPA AHLI WARIS',
            6 => 'MENUTUP USAHA / PAILIT',
            7 => 'DICABUT REGISTRASINYA',
            8 => 'TERKENA BENCANA ALAM',
            9 => 'TIDAK PUNYA KEKAYAAN LAGI',
            10 => 'TIDAK DIKETAHUI ALAMAT / KEDUDUKANNYA'
        ];

        $files = [
            ['nama_file' => 'ktp', 'keterangan_file' => 'KTP'],
            ['nama_file' => 'foto_kendaraan', 'keterangan_file' => 'FOTO KENDARAAN']
        ];

        $data = [];

        foreach ($statuses as $id_status => $status) {
            foreach ($files as $file) {
                $data[] = [
                    'id_status' => $id_status,
                    'nama_file' => $file['nama_file'],
                    'type_file' => 'jpg',
                    'ukuran_file' => '1',
                    'keterangan_file' => $file['keterangan_file'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('seng_status_file')->insert($data);
    }
}

