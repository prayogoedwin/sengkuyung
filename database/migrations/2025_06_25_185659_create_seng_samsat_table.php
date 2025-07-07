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
        Schema::create('seng_samsat', function (Blueprint $table) {
            $table->id();
            $table->string('lokasi');
            $table->string('lokasi_singkat');
            $table->text('alamat');
            $table->string('telp');
            $table->string('fax');
            $table->string('lat');
            $table->string('lng');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seng_samsat');
    }
};