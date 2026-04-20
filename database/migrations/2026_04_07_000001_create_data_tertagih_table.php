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
        Schema::create('data_tertagih', function (Blueprint $table) {
            $table->id();
            $table->string('no_polisi', 50)->nullable();
            $table->string('id_lokasi_samsat', 100)->nullable();
            $table->string('lokasi_layanan', 255)->nullable();
            $table->string('id_kecamatan', 100)->nullable();
            $table->string('nm_kecamatan', 255)->nullable();
            $table->string('id_kelurahan', 100)->nullable();
            $table->string('nm_kelurahan', 255)->nullable();
            $table->tinyInteger('is_terdata')->default(0);
            $table->integer('year')->index();
            $table->dateTime('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_tertagih');
    }
};
