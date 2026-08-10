<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rvf_cache_settings', function (Blueprint $table) {
            $table->unsignedInteger('ttl_detail_minutes')->default(60)->after('ttl_hours');
        });
    }

    public function down(): void
    {
        Schema::table('rvf_cache_settings', function (Blueprint $table) {
            $table->dropColumn('ttl_detail_minutes');
        });
    }
};
