<?php

namespace App\Models\Concerns;

use App\Support\NopolFormatter;
use Illuminate\Support\Facades\Schema;

trait SyncsNopolKey
{
    /** @var array<string, bool> */
    private static array $nopolKeyColumnExists = [];

    public static function bootSyncsNopolKey(): void
    {
        static::saving(function ($model) {
            $table = $model->getTable();

            if (! array_key_exists($table, self::$nopolKeyColumnExists)) {
                self::$nopolKeyColumnExists[$table] = Schema::hasColumn($table, 'nopol_key');
            }

            if (! self::$nopolKeyColumnExists[$table]) {
                // Kolom belum ada di DB (migrasi nopol_key belum dijalankan) — jangan set attribute.
                unset($model->nopol_key);

                return;
            }

            $source = $model->nopol_
                ?? $model->nopol
                ?? $model->no_polisi
                ?? null;

            if ($source === null || $source === '') {
                return;
            }

            $model->nopol_key = NopolFormatter::matchKey((string) $source) ?: null;
        });
    }
}
