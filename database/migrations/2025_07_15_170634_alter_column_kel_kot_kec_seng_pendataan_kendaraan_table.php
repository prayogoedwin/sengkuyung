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
            $table->string('kel_dagri', 225)->nullable()->change();
            $table->string('kec_dagri', 225)->nullable()->change();
            $table->string('kota_dagri', 225)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            //
        });
    }
};
