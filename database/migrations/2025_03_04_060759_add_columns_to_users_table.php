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
        Schema::table('users', function (Blueprint $table) {
            // Menambahkan kolom created_by setelah created_at
            $table->unsignedBigInteger('created_by')->after('created_at')->nullable();
            // Menambahkan kolom updated_by setelah updated_at
            $table->unsignedBigInteger('updated_by')->after('updated_at')->nullable();
            // Menambahkan kolom deleted_at untuk soft deletes
            $table->softDeletes(); // Ini akan menambahkan kolom deleted_at
            // Menambahkan kolom deleted_by
            $table->unsignedBigInteger('deleted_by')->after('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Menghapus kolom yang ditambahkan
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
            $table->dropSoftDeletes(); // Ini akan menghapus kolom deleted_at
        });
    }
};
