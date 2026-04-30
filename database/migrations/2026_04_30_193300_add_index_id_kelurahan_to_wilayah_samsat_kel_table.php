<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wilayah_samsat_kel')) {
            return;
        }

        Schema::table('wilayah_samsat_kel', function (Blueprint $table) {
            if (Schema::hasColumn('wilayah_samsat_kel', 'id_kelurahan')) {
                $table->index('id_kelurahan', 'idx_wilayah_samsat_kel_id_kel');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('wilayah_samsat_kel')) {
            return;
        }

        Schema::table('wilayah_samsat_kel', function (Blueprint $table) {
            if (Schema::hasColumn('wilayah_samsat_kel', 'id_kelurahan')) {
                $table->dropIndex('idx_wilayah_samsat_kel_id_kel');
            }
        });
    }
};
