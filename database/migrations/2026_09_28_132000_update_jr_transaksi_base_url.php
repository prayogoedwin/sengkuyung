<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('integrasi_settings')
            ->where('base_url', 'like', '%samsat.jatengprov.go.id%')
            ->update([
                'base_url' => 'https://jr-data-transaksi.bapendajateng.web.id',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('integrasi_settings')
            ->where('base_url', 'https://jr-data-transaksi.bapendajateng.web.id')
            ->update([
                'base_url' => 'https://samsat.jatengprov.go.id',
                'updated_at' => now(),
            ]);
    }
};
