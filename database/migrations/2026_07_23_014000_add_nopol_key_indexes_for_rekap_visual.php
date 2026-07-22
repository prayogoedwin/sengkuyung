<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addNopolKey('seng_bayar_pajak', 'nopol_', 'idx_seng_bayar_pajak_year_nopol_key', ['year', 'nopol_key']);

        $this->addNopolKey('data_tertagih', 'no_polisi', 'idx_data_tertagih_year_nopol_key', ['year', 'nopol_key']);
        $this->addCompositeIfMissing('data_tertagih', 'idx_data_tertagih_year_lokasi', ['year', 'id_lokasi_samsat']);
        $this->addCompositeIfMissing('data_tertagih', 'idx_data_tertagih_year_is_terdata', ['year', 'is_terdata']);

        $this->addNopolKey('data_tertagih_d2d', 'no_polisi', 'idx_data_tertagih_d2d_year_nopol_key', ['year', 'nopol_key']);
        $this->addCompositeIfMissing('data_tertagih_d2d', 'idx_data_tertagih_d2d_year_lokasi', ['year', 'id_lokasi_samsat']);
        $this->addCompositeIfMissing('data_tertagih_d2d', 'idx_data_tertagih_d2d_year_is_terdata', ['year', 'is_terdata']);

        $this->addNopolKey('seng_pendataan_kendaraan', 'nopol', 'idx_seng_pendataan_nopol_key', ['nopol_key']);
        $this->addCompositeIfMissing('seng_pendataan_kendaraan', 'idx_seng_pendataan_created_status', ['created_at', 'status_verifikasi']);

        $this->addNopolKey('seng_pendataan_kendaraan_d2d', 'nopol', 'idx_seng_pendataan_d2d_nopol_key', ['nopol_key']);
        $this->addCompositeIfMissing('seng_pendataan_kendaraan_d2d', 'idx_seng_pendataan_d2d_created_status', ['created_at', 'status_verifikasi']);
    }

    public function down(): void
    {
        $this->dropNopolKey('seng_bayar_pajak', 'idx_seng_bayar_pajak_year_nopol_key');

        $this->dropNopolKey('data_tertagih', 'idx_data_tertagih_year_nopol_key');
        $this->dropIndexIfExists('data_tertagih', 'idx_data_tertagih_year_lokasi');
        $this->dropIndexIfExists('data_tertagih', 'idx_data_tertagih_year_is_terdata');

        $this->dropNopolKey('data_tertagih_d2d', 'idx_data_tertagih_d2d_year_nopol_key');
        $this->dropIndexIfExists('data_tertagih_d2d', 'idx_data_tertagih_d2d_year_lokasi');
        $this->dropIndexIfExists('data_tertagih_d2d', 'idx_data_tertagih_d2d_year_is_terdata');

        $this->dropNopolKey('seng_pendataan_kendaraan', 'idx_seng_pendataan_nopol_key');
        $this->dropIndexIfExists('seng_pendataan_kendaraan', 'idx_seng_pendataan_created_status');

        $this->dropNopolKey('seng_pendataan_kendaraan_d2d', 'idx_seng_pendataan_d2d_nopol_key');
        $this->dropIndexIfExists('seng_pendataan_kendaraan_d2d', 'idx_seng_pendataan_d2d_created_status');
    }

    /**
     * @param  list<string>  $indexColumns
     */
    private function addNopolKey(string $table, string $sourceColumn, string $indexName, array $indexColumns): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $sourceColumn)) {
            return;
        }

        if (! Schema::hasColumn($table, 'nopol_key')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('nopol_key', 50)->nullable();
            });

            // Backfill sekali; update SQL set jauh lebih cepat dari loop PHP.
            DB::statement("
                UPDATE `{$table}`
                SET nopol_key = NULLIF(
                    UPPER(REGEXP_REPLACE(COALESCE(`{$sourceColumn}`, ''), '[^A-Za-z0-9]', '')),
                    ''
                )
                WHERE nopol_key IS NULL
            ");
        }

        $this->addCompositeIfMissing($table, $indexName, $indexColumns);
    }

    /**
     * @param  list<string>  $columns
     */
    private function addCompositeIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $col) {
            if (! Schema::hasColumn($table, $col)) {
                return;
            }
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropNopolKey(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $this->dropIndexIfExists($table, $indexName);

        if (Schema::hasColumn($table, 'nopol_key')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('nopol_key');
            });
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$db, $table, $indexName]
        );

        return (bool) $row;
    }
};
