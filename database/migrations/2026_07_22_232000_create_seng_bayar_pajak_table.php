<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seng_bayar_pajak', function (Blueprint $table) {
            $table->id();
            $table->string('nopol', 50)->nullable()->comment('Nopol asli dari file');
            $table->string('nopol_', 50)->nullable()->index()->comment('Nopol dinormalisasi H-1234-XX');
            $table->string('nopol_lama', 50)->nullable();
            $table->date('tgl_bayar')->nullable()->index();
            $table->unsignedBigInteger('pkb_provinsi_jalan')->nullable();
            $table->unsignedBigInteger('pkb_provinsi_tunggakan')->nullable();
            $table->unsignedBigInteger('pkb_opsen_jalan')->nullable();
            $table->unsignedBigInteger('pkb_opsen_tunggakan')->nullable();
            $table->integer('year')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['year', 'nopol_']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seng_bayar_pajak');
    }
};
