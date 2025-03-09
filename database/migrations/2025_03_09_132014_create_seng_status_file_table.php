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
        Schema::create('seng_status_file', function (Blueprint $table) {
            $table->id();
            $table->integer('id_status');
            $table->integer('nama_file')->nullable();
            $table->integer('type_file')->nullable();
            $table->integer('ukuran_file')->nullable();
            $table->integer('keterangan_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seng_status_file');
    }
};
