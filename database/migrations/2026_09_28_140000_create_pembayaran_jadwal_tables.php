<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_jadwal', function (Blueprint $table) {
            $table->id();
            $table->boolean('aktif')->default(false);
            $table->string('jam', 5)->default('06:00');
            $table->string('tanggal_sumber', 20)->default('kemarin');
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status', 20)->nullable();
            $table->text('last_message')->nullable();
            $table->date('last_tanggal')->nullable();
            $table->timestamps();
        });

        Schema::create('pembayaran_tarik_logs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->date('tanggal_proses')->nullable();
            $table->unsignedInteger('halaman')->default(1);
            $table->unsignedInteger('total_halaman')->default(0);
            $table->string('status', 20)->default('berjalan');
            $table->unsignedInteger('inserted')->default(0);
            $table->unsignedInteger('skipped_duplicate')->default(0);
            $table->unsignedInteger('skipped_invalid')->default(0);
            $table->text('message')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_tarik_logs');
        Schema::dropIfExists('pembayaran_jadwal');
    }
};
