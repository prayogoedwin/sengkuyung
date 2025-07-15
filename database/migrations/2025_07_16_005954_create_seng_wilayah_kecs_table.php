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
        Schema::create('wilayah_samsat_kec', function (Blueprint $table) {
            $table->id();
            $table->string('id_kecamatan', 255);
            $table->string('id_lokasi_samsat', 255);
            $table->string('kecamatan', 255);
            $table->string('kode_dagri', 255);
            $table->integer('kode_dagri_kota');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wilayah_samsat_kec');
    }
};