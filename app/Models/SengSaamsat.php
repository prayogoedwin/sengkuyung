<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SengSaamsat extends Model
{
    use HasFactory;

    protected $table = 'seng_samsat'; // Nama tabel di database
    protected $primaryKey = 'id'; // Primary key menggunakan string
    public $incrementing = false; // Karena primary key bukan auto-increment
    protected $keyType = 'string'; // Primary key bertipe string

    protected $fillable = [
        'id',
        'lokasi',
        'id_wilayah_samsat',
        'kabkota',
        'lokasi_singkat',
        'alamat',
        'telp',
        'fax',
        'lat',
        'lng',
        'created_at',
        'updated_at'
    ];

    /**
     * Variants kode untuk filter lokasi samsat dari pilihan dropdown (id seng_samsat atau legacy id_wilayah).
     * Untuk samsat pembantu, ikut sertakan id_wilayah_samsat induk agar data lama tetap ketemu.
     *
     * @return list<string>
     */
    public static function lokasiFilterVariants(?string $selected): array
    {
        $v = trim((string) $selected);
        if ($v === '') {
            return [];
        }

        $codes = [$v];

        $row = self::query()
            ->where(function ($q) use ($v) {
                $q->where('id', $v)->orWhere('id_wilayah_samsat', $v);
            })
            ->first(['id', 'id_wilayah_samsat']);

        if ($row) {
            if ($row->id !== null && (string) $row->id !== '') {
                $codes[] = (string) $row->id;
            }
            if ($row->id_wilayah_samsat !== null && (string) $row->id_wilayah_samsat !== '') {
                $codes[] = (string) $row->id_wilayah_samsat;
            }
        }

        $out = [];
        foreach (array_unique($codes) as $code) {
            foreach (self::codeVariants($code) as $variant) {
                $out[] = $variant;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return list<string>
     */
    public static function codeVariants(string $value): array
    {
        $v = trim($value);
        if ($v === '') {
            return [];
        }

        $out = [$v];

        if (ctype_digit($v)) {
            $stripped = ltrim($v, '0');
            $stripped = $stripped === '' ? '0' : $stripped;
            $out[] = $stripped;
            $out[] = (string) (int) $v;
        }

        return array_values(array_unique($out));
    }
}
