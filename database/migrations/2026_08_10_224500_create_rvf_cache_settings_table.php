<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rvf_cache_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('use_cache')->default(true);
            $table->string('warm_channel', 20)->default('semua'); // semua|reguler|d2d
            $table->unsignedInteger('ttl_hours')->default(12);
            $table->boolean('schedule_enabled')->default(true);
            $table->json('schedule_slots')->nullable();
            $table->unsignedSmallInteger('warm_year')->nullable();
            $table->timestamp('last_warm_started_at')->nullable();
            $table->timestamp('last_warm_finished_at')->nullable();
            $table->string('last_warm_status', 40)->nullable();
            $table->text('last_warm_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rvf_cache_settings');
    }
};
