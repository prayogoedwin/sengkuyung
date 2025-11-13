<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            for ($i = 0; $i <= 9; $i++) {
                $table->boolean("file{$i}_encrypted")->default(0)->after("file{$i}_ket");
                $table->string("file{$i}_original_ext", 10)->nullable()->after("file{$i}_encrypted");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            //
        });
    }
};
