<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->integer('status_verifikasi')->default(1)->change();
            $table->integer('status')->default(1)->change();
        });
    }

    public function down()
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->integer('status_verifikasi')->default(null)->change();
            $table->integer('status')->default(null)->change();
        });
    }
};

