<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            if (! Schema::hasColumn('seng_pendataan_kendaraan', 'nama_pembuat_pernyataan')) {
                $table->string('nama_pembuat_pernyataan')->nullable()->after('status_verifikasi_name');
            }

            if (! Schema::hasColumn('seng_pendataan_kendaraan', 'is_selesai')) {
                $table->tinyInteger('is_selesai')->default(0)->after('nama_pembuat_pernyataan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['nama_pembuat_pernyataan', 'is_selesai'],
                fn (string $column) => Schema::hasColumn('seng_pendataan_kendaraan', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};