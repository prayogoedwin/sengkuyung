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
        Schema::create('seng_wilayah', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('kode');
            $table->string('nama')->nullable();
            $table->integer('id_up')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seng_wilayah');
    }
};
