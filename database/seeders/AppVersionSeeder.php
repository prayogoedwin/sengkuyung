<?php

namespace Database\Seeders;

use App\Models\AppVersion;
use Illuminate\Database\Seeder;

class AppVersionSeeder extends Seeder
{
    public function run(): void
    {
        AppVersion::query()->upsert([
            [
                'id' => 1,
                'nama_aplikasi' => 'web',
                'versi' => '2.0.5',
                'alias' => 'beta',
            ],
            [
                'id' => 2,
                'nama_aplikasi' => 'mobile',
                'versi' => '1.0.8',
                'alias' => 'alpha',
            ],
        ], ['id'], ['nama_aplikasi', 'versi', 'alias', 'updated_at']);
    }
}
