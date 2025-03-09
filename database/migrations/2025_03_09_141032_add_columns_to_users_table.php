<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->after('email');
            $table->unsignedBigInteger('uptd_id')->nullable()->after('whatsapp');
            $table->string('provinsi')->nullable()->after('uptd_id');
            $table->string('kota')->nullable()->after('provinsi');
            $table->string('kecamatan')->nullable()->after('kota');
            $table->string('kelurahan')->nullable()->after('kecamatan');
            $table->string('rw')->nullable()->after('kelurahan');
            $table->string('rt')->nullable()->after('rw');
            $table->text('alamat_lengkap')->nullable()->after('rt');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp',
                'uptd_id',
                'provinsi',
                'kota',
                'kecamatan',
                'kelurahan',
                'rw',
                'rt',
                'alamat_lengkap'
            ]);
        });
    }
};
