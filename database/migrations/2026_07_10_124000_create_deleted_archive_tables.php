<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, array{0: string, 1: string}>
     */
    private const TABLE_PAIRS = [
        ['data_tertagih', 'data_tertagih_del'],
        ['data_tertagih_d2d', 'data_tertagih_d2d_del'],
        ['seng_pendataan_kendaraan', 'seng_pendataan_kendaraan_del'],
        ['seng_pendataan_kendaraan_d2d', 'seng_pendataan_kendaraan_d2d_del'],
    ];

    private function createLikeIfMissing(string $sourceTable, string $targetTable): void
    {
        if (!Schema::hasTable($sourceTable) || Schema::hasTable($targetTable)) {
            return;
        }

        DB::statement("CREATE TABLE `{$targetTable}` LIKE `{$sourceTable}`");
    }

    public function up(): void
    {
        foreach (self::TABLE_PAIRS as [$sourceTable, $targetTable]) {
            $this->createLikeIfMissing($sourceTable, $targetTable);
        }
    }

    public function down(): void
    {
        foreach (self::TABLE_PAIRS as [, $targetTable]) {
            Schema::dropIfExists($targetTable);
        }
    }
};
