<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrasi_settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aplikasi', 100);
            $table->string('client_id', 100);
            $table->text('secret_key');
            $table->text('api_key')->nullable();
            $table->string('base_url', 255);
            $table->timestamps();
        });

        DB::table('integrasi_settings')->insert([
            'nama_aplikasi' => 'Jasa Raharja',
            'client_id' => 'JASA_RAHARJA',
            'secret_key' => 'jasa_raharja_faa4175f71a04a58808fb07db649d8de',
            'api_key' => null,
            'base_url' => 'https://samsat.jatengprov.go.id',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('integrasi_settings');
    }
};
