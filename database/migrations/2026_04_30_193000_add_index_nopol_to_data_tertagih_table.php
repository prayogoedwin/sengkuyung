<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_tertagih')) {
            return;
        }

        Schema::table('data_tertagih', function (Blueprint $table) {
            if (Schema::hasColumn('data_tertagih', 'no_polisi')) {
                $table->index('no_polisi', 'idx_data_tertagih_no_polisi');
            } elseif (Schema::hasColumn('data_tertagih', 'nopol')) {
                $table->index('nopol', 'idx_data_tertagih_nopol');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_tertagih')) {
            return;
        }

        Schema::table('data_tertagih', function (Blueprint $table) {
            if (Schema::hasColumn('data_tertagih', 'no_polisi')) {
                $table->dropIndex('idx_data_tertagih_no_polisi');
            } elseif (Schema::hasColumn('data_tertagih', 'nopol')) {
                $table->dropIndex('idx_data_tertagih_nopol');
            }
        });
    }
};
