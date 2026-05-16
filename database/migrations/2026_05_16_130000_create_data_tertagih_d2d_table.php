<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_tertagih_d2d', function (Blueprint $table) {
            $table->id();
            $table->string('no_polisi', 50)->nullable()->index('idx_data_tertagih_d2d_no_polisi');
            $table->string('id_lokasi_samsat', 100)->nullable();
            $table->string('lokasi_layanan', 255)->nullable();
            $table->string('id_kecamatan', 100)->nullable();
            $table->string('nm_kecamatan', 255)->nullable();
            $table->string('id_kelurahan', 100)->nullable();
            $table->string('nm_kelurahan', 255)->nullable();
            $table->string('alamat', 500)->nullable();
            $table->string('nama_pemilik', 255)->nullable();
            $table->string('jenis_roda', 50)->nullable();
            $table->tinyInteger('is_terdata')->default(0);
            $table->integer('year')->index();
            $table->dateTime('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_tertagih_d2d');
    }
};
