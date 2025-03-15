<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('wilayah_samsat', function (Blueprint $table) {
            $table->string('id', 2)->primary();
            $table->string('nama', 100);
            $table->integer('kabkota');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wilayah_samsat');
    }
};

