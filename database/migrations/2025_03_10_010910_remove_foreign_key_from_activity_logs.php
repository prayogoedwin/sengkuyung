<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Hapus foreign key
        });

        Schema::disableForeignKeyConstraints();
        Schema::table('users', function (Blueprint $table) {
            \DB::statement('TRUNCATE TABLE users'); // Hapus semua data users
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down()
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};

