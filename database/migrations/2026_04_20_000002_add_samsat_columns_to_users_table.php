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
        Schema::table('users', function (Blueprint $table) {
            $table->string('lokasi_samsat')->nullable()->after('kelurahan');
            $table->string('kecamatan_samsat')->nullable()->after('lokasi_samsat');
            $table->string('kelurahan_samsat')->nullable()->after('kecamatan_samsat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'lokasi_samsat',
                'kecamatan_samsat',
                'kelurahan_samsat',
            ]);
        });
    }
};
