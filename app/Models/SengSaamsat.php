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
    /**
     * Semua variant id_lokasi_samsat untuk seluruh samsat (utama + pembantu) di satu kabkota.
     *
     * @return list<string>
     */
    public static function lokasiFilterVariantsByKabkota(?string $kabkotaId): array
    {
        $kabkotaId = trim((string) $kabkotaId);
        if ($kabkotaId === '') {
            return [];
        }

        return self::query()
            ->where('kabkota', $kabkotaId)
            ->get(['id', 'id_wilayah_samsat'])
            ->flatMap(fn ($s) => self::lokasiFilterVariants((string) $s->id))
            ->unique()
            ->values()
            ->all();
    }

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

    /**
     * Nilai kanonik untuk dropdown / profil: id_wilayah_samsat ("20", "01", "02").
     * Bukan PK seng_samsat.id ("1", "2", "3").
     */
    public static function resolveStoredLokasiId(?string $stored): ?string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return null;
        }

        $variants = self::lokasiFilterVariants($stored);
        if (empty($variants)) {
            return $stored;
        }

        $row = self::query()
            ->where(function ($q) use ($variants) {
                foreach ($variants as $variant) {
                    $q->orWhere('id', $variant)->orWhere('id_wilayah_samsat', $variant);
                }
            })
            ->first(['id_wilayah_samsat']);

        if ($row && $row->id_wilayah_samsat !== null && (string) $row->id_wilayah_samsat !== '') {
            return (string) $row->id_wilayah_samsat;
        }

        return $stored;
    }

    public static function dropdownValue(object|array $samsat): string
    {
        $wilayah = trim((string) (is_array($samsat) ? ($samsat['id_wilayah_samsat'] ?? '') : ($samsat->id_wilayah_samsat ?? '')));
        if ($wilayah !== '') {
            return $wilayah;
        }

        return (string) (is_array($samsat) ? ($samsat['id'] ?? '') : ($samsat->id ?? ''));
    }

    public static function profileMatchesSamsat(?string $profileLokasi, object|array $samsat): bool
    {
        $profileLokasi = trim((string) $profileLokasi);
        if ($profileLokasi === '') {
            return true;
        }

        $samsatId = is_array($samsat) ? ($samsat['id'] ?? '') : ($samsat->id ?? '');
        $wilayahId = is_array($samsat) ? ($samsat['id_wilayah_samsat'] ?? '') : ($samsat->id_wilayah_samsat ?? '');

        foreach (self::lokasiFilterVariants($profileLokasi) as $variant) {
            if ($variant === (string) $samsatId || $variant === (string) $wilayahId) {
                return true;
            }
        }

        return false;
    }
}
