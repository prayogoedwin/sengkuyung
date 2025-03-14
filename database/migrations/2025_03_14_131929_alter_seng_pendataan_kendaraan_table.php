<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterSengPendataanKendaraanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {

            $table->string('file0')->nullable();
            $table->string('file0_url')->nullable();
            $table->string('file0_ket')->nullable();

            $table->string('file1')->nullable();
            $table->string('file1_url')->nullable();
            $table->string('file1_ket')->nullable();

            $table->string('file2')->nullable();
            $table->string('file2_url')->nullable();
            $table->string('file2_ket')->nullable();

            $table->string('file3')->nullable();
            $table->string('file3_url')->nullable();
            $table->string('file3_ket')->nullable();

            $table->string('file4')->nullable();
            $table->string('file4_url')->nullable();
            $table->string('file4_ket')->nullable();
            
            $table->string('file5')->nullable();
            $table->string('file5_url')->nullable();
            $table->string('file5_ket')->nullable();

            $table->string('file6')->nullable();
            $table->string('file6_url')->nullable();
            $table->string('file6_ket')->nullable();

            $table->string('file7')->nullable();
            $table->string('file7_url')->nullable();
            $table->string('file7_ket')->nullable();

            $table->string('file8')->nullable();
            $table->string('file8_url')->nullable();
            $table->string('file8_ket')->nullable();

            $table->string('file9')->nullable();
            $table->string('file9_url')->nullable();
            $table->string('file9_ket')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            $table->dropColumn([
                'file0', 'file0_url', 'file0_ket',
                'file1', 'file1_url', 'file1_ket',
                'file2', 'file2_url', 'file2_ket',
                'file3', 'file3_url', 'file3_ket',
                'file4', 'file4_url', 'file4_ket',
                'file5', 'file5_url', 'file5_ket',
                'file6', 'file6_url', 'file6_ket',
                'file7', 'file7_url', 'file7_ket',
                'file8', 'file8_url', 'file8_ket',
                'file9', 'file9_url', 'file9_ket',
            ]);
        });
    }
}
