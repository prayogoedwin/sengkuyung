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
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {

            $table->unsignedBigInteger('created_by')->change();
            $table->unsignedBigInteger('updated_by')->change();
            $table->unsignedBigInteger('deleted_by')->change();

            $table->unsignedBigInteger('verified_by')->nullable()->after('created_by');
           
            $table->timestamp('verified_at')->nullable()->after('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seng_pendataan_kendaraan', function (Blueprint $table) {
            
            $table->integer('created_by')->change();
            $table->integer('updated_by')->change();
            $table->integer('deleted_by')->change();

            // Hapus kolom verified_by dan verified_at
            $table->dropColumn(['verified_by', 'verified_at']);

        });
    }
};
