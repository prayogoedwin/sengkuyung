<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->decimal('lat', 10, 8)->nullable()->after('ttd');
            $table->decimal('lng', 11, 8)->nullable()->after('lat');
        });
    }

    public function down()
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};

