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
        Schema::table('seng_status_file', function (Blueprint $table) {
            $table->string('nama_file')->nullable()->change();
            $table->string('type_file')->nullable()->change();
            $table->text('keterangan_file')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seng_status_file', function (Blueprint $table) {
            $table->binary('nama_file')->change(); // Kembalikan ke tipe sebelumnya jika bukan string
            $table->binary('type_file')->change(); // Kembalikan ke tipe sebelumnya jika bukan string
            $table->dropColumn('keterangan_file');
        });
    }
};
