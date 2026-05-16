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
        Schema::table('data_tertagih', function (Blueprint $table) {
            if (!Schema::hasColumn('data_tertagih', 'nama_pemilik')) {
                $table->string('nama_pemilik', 255)->nullable();
            }
            if (!Schema::hasColumn('data_tertagih', 'jenis_roda')) {
                $table->string('jenis_roda', 50)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_tertagih', function (Blueprint $table) {
            $table->dropColumn(['nama_pemilik', 'jenis_roda']);
        });
    }
};
