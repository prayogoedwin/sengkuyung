<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            if (!Schema::hasColumn('seng_pendataan_kendaraan', 'is_d2d')) {
                $table->unsignedTinyInteger('is_d2d')->default(0)->index()->after('is_selesai');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            if (Schema::hasColumn('seng_pendataan_kendaraan', 'is_d2d')) {
                $table->dropIndex(['is_d2d']);
                $table->dropColumn('is_d2d');
            }
        });
    }
};
