<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->string('status_name', 255)->nullable()->change();
            $table->string('status_verifikasi_name', 255)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->string('status_name')->nullable(false)->change();
            $table->string('status_verifikasi_name')->nullable(false)->change();
        });
    }
};
