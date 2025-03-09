<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->id();
            $table->string('nohp')->nullable();
            $table->string('email')->nullable();
            $table->string('nik')->nullable();
            $table->date('tgl_ctk')->nullable();
            $table->string('nopol')->nullable();
            $table->string('nama')->nullable();
            $table->text('alamat')->nullable();
            $table->string('desa_name')->nullable();
            $table->string('kec_name')->nullable();
            $table->string('kota_name')->nullable();
            $table->string('prov_name')->nullable();
            $table->string('desa')->nullable();
            $table->string('kec')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('merk')->nullable();
            $table->string('tipe')->nullable();
            $table->year('tahun')->nullable();
            $table->string('tnkb')->nullable();
            $table->string('warna')->nullable();
            $table->string('jenis_kbm')->nullable();
            $table->date('jatuh_tempo')->nullable();
            $table->integer('pkb_pokok')->nullable();
            $table->integer('pkb_denda')->nullable();
            $table->integer('pkb')->nullable();
            $table->integer('tanggal_akhir_Pkb')->nullable();
            $table->integer('jr_pokok')->nullable();
            $table->integer('jr_denda')->nullable();
            $table->integer('jr')->nullable();
            $table->integer('pnbp_stnk')->nullable();
            $table->integer('pnbp_tnkb')->nullable();
            $table->integer('pnbp')->nullable();
            $table->integer('is_setuju')->nullable();
            $table->integer('ttd')->nullable();
            $table->integer('status')->nullable();
            $table->integer('status_name')->nullable();
            $table->integer('status_verifikasi')->nullable();
            $table->integer('status_verifikasi_name')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('seng_pendataan_kendaraan');
    }
};