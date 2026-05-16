<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName]);

        return $indexes !== [];
    }

    public function up(): void
    {
        if (!Schema::hasTable('seng_pendataan_kendaraan_d2d')) {
            DB::statement('CREATE TABLE seng_pendataan_kendaraan_d2d LIKE seng_pendataan_kendaraan');
        }

        if (Schema::hasColumn('seng_pendataan_kendaraan', 'is_d2d')) {
            $columns = collect(Schema::getColumnListing('seng_pendataan_kendaraan'))
                ->reject(static fn (string $col) => $col === 'is_d2d')
                ->values()
                ->all();

            if ($columns !== []) {
                $columnList = implode(', ', array_map(static fn ($c) => "`{$c}`", $columns));

                DB::statement(
                    "INSERT INTO seng_pendataan_kendaraan_d2d ({$columnList})
                     SELECT {$columnList} FROM seng_pendataan_kendaraan WHERE is_d2d = 1"
                );

                DB::table('seng_pendataan_kendaraan')->where('is_d2d', 1)->delete();
            }

            if ($this->indexExists('seng_pendataan_kendaraan', 'seng_pendataan_kendaraan_is_d2d_index')) {
                Schema::table('seng_pendataan_kendaraan', function ($table) {
                    $table->dropIndex(['is_d2d']);
                });
            }
            Schema::table('seng_pendataan_kendaraan', function ($table) {
                $table->dropColumn('is_d2d');
            });
        }

        if (Schema::hasColumn('seng_pendataan_kendaraan_d2d', 'is_d2d')) {
            Schema::table('seng_pendataan_kendaraan_d2d', function ($table) {
                $table->dropColumn('is_d2d');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('seng_pendataan_kendaraan_d2d')) {
            return;
        }

        if (!Schema::hasColumn('seng_pendataan_kendaraan', 'is_d2d')) {
            Schema::table('seng_pendataan_kendaraan', function ($table) {
                $table->unsignedTinyInteger('is_d2d')->default(0)->index()->after('is_selesai');
            });
        }

        $columns = collect(Schema::getColumnListing('seng_pendataan_kendaraan_d2d'))
            ->reject(static fn (string $col) => $col === 'is_d2d')
            ->values()
            ->all();

        if ($columns !== []) {
            $columnList = implode(', ', array_map(static fn ($c) => "`{$c}`", $columns));

            DB::statement(
                "INSERT INTO seng_pendataan_kendaraan ({$columnList}, is_d2d)
                 SELECT {$columnList}, 1 FROM seng_pendataan_kendaraan_d2d"
            );
        }

        Schema::dropIfExists('seng_pendataan_kendaraan_d2d');
    }
};
