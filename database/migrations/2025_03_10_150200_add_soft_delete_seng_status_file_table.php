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
            $table->softDeletes(); // Menambahkan kolom deleted_at
            $table->unsignedBigInteger('deleted_by')->after('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seng_status_file', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Menghapus kolom deleted_at saat rollback
            $table->dropColumn(['deleted_by']);
        });
    }
};
