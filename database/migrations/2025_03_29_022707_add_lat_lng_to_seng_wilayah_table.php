<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seng_wilayah', function (Blueprint $table) {
            $table->decimal('lat', 10, 8)->nullable()->after('id_up');
            $table->decimal('lng', 11, 8)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('seng_wilayah', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};

