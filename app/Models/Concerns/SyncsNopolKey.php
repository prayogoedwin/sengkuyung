<?php

namespace App\Models\Concerns;

use App\Support\NopolFormatter;

trait SyncsNopolKey
{
    public static function bootSyncsNopolKey(): void
    {
        static::saving(function ($model) {
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
