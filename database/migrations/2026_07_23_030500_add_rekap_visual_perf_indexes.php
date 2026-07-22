<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Index saja (tanpa nopol_key / UPDATE berat) untuk rekap visual D2D & reguler.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('data_tertagih', 'idx_data_tertagih_year_lokasi', ['year', 'id_lokasi_samsat']);
        $this->addIndexIfMissing('data_tertagih', 'idx_data_tertagih_year_is_terdata', ['year', 'is_terdata']);
        $this->addIndexIfMissing('data_tertagih', 'idx_data_tertagih_year_nopol', ['year', 'no_polisi']);

        $this->addIndexIfMissing('data_tertagih_d2d', 'idx_data_tertagih_d2d_year_lokasi', ['year', 'id_lokasi_samsat']);
        $this->addIndexIfMissing('data_tertagih_d2d', 'idx_data_tertagih_d2d_year_is_terdata', ['year', 'is_terdata']);
        $this->addIndexIfMissing('data_tertagih_d2d', 'idx_data_tertagih_d2d_year_nopol', ['year', 'no_polisi']);

        $this->addIndexIfMissing('seng_pendataan_kendaraan', 'idx_seng_pendataan_created_status', ['created_at', 'status_verifikasi']);
        $this->addIndexIfMissing('seng_pendataan_kendaraan', 'idx_seng_pendataan_nopol_created', ['nopol', 'created_at']);

        $this->addIndexIfMissing('seng_pendataan_kendaraan_d2d', 'idx_seng_pendataan_d2d_created_status', ['created_at', 'status_verifikasi']);
        $this->addIndexIfMissing('seng_pendataan_kendaraan_d2d', 'idx_seng_pendataan_d2d_nopol_created', ['nopol', 'created_at']);
    }

    public function down(): void
    {
        foreach ([
            ['data_tertagih', 'idx_data_tertagih_year_lokasi'],
            ['data_tertagih', 'idx_data_tertagih_year_is_terdata'],
            ['data_tertagih', 'idx_data_tertagih_year_nopol'],
            ['data_tertagih_d2d', 'idx_data_tertagih_d2d_year_lokasi'],
            ['data_tertagih_d2d', 'idx_data_tertagih_d2d_year_is_terdata'],
            ['data_tertagih_d2d', 'idx_data_tertagih_d2d_year_nopol'],
            ['seng_pendataan_kendaraan', 'idx_seng_pendataan_created_status'],
            ['seng_pendataan_kendaraan', 'idx_seng_pendataan_nopol_created'],
            ['seng_pendataan_kendaraan_d2d', 'idx_seng_pendataan_d2d_created_status'],
            ['seng_pendataan_kendaraan_d2d', 'idx_seng_pendataan_d2d_nopol_created'],
        ] as [$table, $index]) {
            $this->dropIndexIfExists($table, $index);
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        foreach ($columns as $col) {
            if (! Schema::hasColumn($table, $col)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
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
