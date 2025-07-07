<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->decimal('kel_dagri', 10, 8)->nullable()->after('desa');
            $table->decimal('kec_dagri', 10, 8)->nullable()->after('kec');
            $table->decimal('kota_dagri', 11, 8)->nullable()->after('kota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->dropColumn(['kel_dagri', 'kec_dagri', 'kota_dagri']);
        });
    }
};
