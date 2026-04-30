<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seng_pendataan_samsat')) {
            Schema::table('seng_pendataan_samsat', function (Blueprint $table) {
                if (Schema::hasColumn('seng_pendataan_samsat', 'nopol')) {
                    $table->index('nopol', 'idx_seng_pendataan_samsat_nopol');
                }
            });

            return;
        }

        // Fallback untuk skema saat ini di repo.
        if (Schema::hasTable('seng_pendataan_kendaraan')) {
            Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
                if (Schema::hasColumn('seng_pendataan_kendaraan', 'nopol')) {
                    $table->index('nopol', 'idx_seng_pendataan_kendaraan_nopol');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seng_pendataan_samsat')) {
            Schema::table('seng_pendataan_samsat', function (Blueprint $table) {
                if (Schema::hasColumn('seng_pendataan_samsat', 'nopol')) {
                    $table->dropIndex('idx_seng_pendataan_samsat_nopol');
                }
            });

            return;
        }

        if (Schema::hasTable('seng_pendataan_kendaraan')) {
            Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
                if (Schema::hasColumn('seng_pendataan_kendaraan', 'nopol')) {
                    $table->dropIndex('idx_seng_pendataan_kendaraan_nopol');
                }
            });
        }
    }
};
