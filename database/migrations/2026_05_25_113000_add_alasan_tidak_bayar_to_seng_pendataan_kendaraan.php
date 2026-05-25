<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seng_pendataan_kendaraan')
            && !Schema::hasColumn('seng_pendataan_kendaraan', 'alasan_tidak_bayar')) {
            Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
                $table->tinyInteger('alasan_tidak_bayar')
                    ->default(0)
                    ->after('is_selesai')
                    ->comment('Master: App\\Helpers\\Helper::getAlasanTidakBayarPajak()');
            });
        }

        if (Schema::hasTable('seng_pendataan_kendaraan_d2d')
            && !Schema::hasColumn('seng_pendataan_kendaraan_d2d', 'alasan_tidak_bayar')) {
            Schema::table('seng_pendataan_kendaraan_d2d', function (Blueprint $table) {
                $table->tinyInteger('alasan_tidak_bayar')
                    ->default(0)
                    ->after('is_selesai')
                    ->comment('Master: App\\Helpers\\Helper::getAlasanTidakBayarPajak()');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seng_pendataan_kendaraan')
            && Schema::hasColumn('seng_pendataan_kendaraan', 'alasan_tidak_bayar')) {
            Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
                $table->dropColumn('alasan_tidak_bayar');
            });
        }

        if (Schema::hasTable('seng_pendataan_kendaraan_d2d')
            && Schema::hasColumn('seng_pendataan_kendaraan_d2d', 'alasan_tidak_bayar')) {
            Schema::table('seng_pendataan_kendaraan_d2d', function (Blueprint $table) {
                $table->dropColumn('alasan_tidak_bayar');
            });
        }
    }
};
