<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->string('nama_pembuat_pernyataan')->nullable()->after('status_verifikasi_name');
            $table->tinyInteger('is_selesai')->default(0)->after('nama_pembuat_pernyataan');
        });
    }

    public function down(): void
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->dropColumn(['nama_pembuat_pernyataan', 'is_selesai']);
        });
    }
};