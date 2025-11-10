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
        Schema::table('users', function (Blueprint $table) {
            // Kolom untuk menyimpan kode OTP
            if (!Schema::hasColumn('users', 'otp')) {
                $table->string('otp', 10)->nullable()->after('password');
            }
            
            // Kolom untuk menyimpan waktu expired OTP
            if (!Schema::hasColumn('users', 'otp_expired_at')) {
                $table->timestamp('otp_expired_at')->nullable()->after('otp');
            }
            
            // Kolom untuk menyimpan metode OTP yang dipilih (email/wa)
            if (!Schema::hasColumn('users', 'otp_method')) {
                $table->enum('otp_method', ['email', 'wa'])->nullable()->after('otp_expired_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'otp')) {
                $table->dropColumn('otp');
            }
            
            if (Schema::hasColumn('users', 'otp_expired_at')) {
                $table->dropColumn('otp_expired_at');
            }
            
            if (Schema::hasColumn('users', 'otp_method')) {
                $table->dropColumn('otp_method');
            }
        });
    }
};