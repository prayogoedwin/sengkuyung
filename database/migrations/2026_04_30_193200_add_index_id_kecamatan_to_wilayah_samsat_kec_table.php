<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wilayah_samsat_kec')) {
            return;
        }

        Schema::table('wilayah_samsat_kec', function (Blueprint $table) {
            if (Schema::hasColumn('wilayah_samsat_kec', 'id_kecamatan')) {
                $table->index('id_kecamatan', 'idx_wilayah_samsat_kec_id_kec');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('wilayah_samsat_kec')) {
            return;
        }

        Schema::table('wilayah_samsat_kec', function (Blueprint $table) {
            if (Schema::hasColumn('wilayah_samsat_kec', 'id_kecamatan')) {
                $table->dropIndex('idx_wilayah_samsat_kec_id_kec');
            }
        });
    }
};
