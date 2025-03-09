<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatusVerifikasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $statuses = [
            'DIPROSES',
            'DIVERIFIKASI',
            'DITOLAK',
        ];

        $now = Carbon::now();

        foreach ($statuses as $index => $status) {
            DB::table('seng_status_verifikasi')->insert([
                'id' => $index + 1, // Ambil index dan tambah 1 agar id mulai dari 1
                'nama' => $status,
                'keterangan' => '',
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }
    }
}
