<?php

namespace App\Support;

use App\Models\SengSaamsat;
use Illuminate\Support\Facades\DB;

class PendataanWilayahFilter
{
    /**
     * @return list<string>
     */
    public static function samsatCodeVariants(?string $value): array
    {
        $v = trim((string) $value);
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

    public static function resolveKecamatanDagriValue(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $row = DB::table('wilayah_samsat_kec')
            ->select('kode_dagri')
            ->where('id_kecamatan', $value)
            ->first();

        return isset($row->kode_dagri) && $row->kode_dagri !== null && $row->kode_dagri !== ''
            ? (string) $row->kode_dagri
            : null;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    public static function applyKecamatanFilter($query, string $kecamatanSamsatId): void
    {
        $kecVariants = self::samsatCodeVariants($kecamatanSamsatId);
        $kecDagri = self::resolveKecamatanDagriValue($kecamatanSamsatId);
        $kecDagriVariants = $kecDagri !== null && $kecDagri !== ''
            ? self::samsatCodeVariants($kecDagri)
            : [];

        $query->where(function ($q) use ($kecVariants, $kecDagriVariants) {
            $q->whereIn('kec', $kecVariants);
            if (!empty($kecDagriVariants)) {
                $q->orWhereIn('kec_dagri', $kecDagriVariants);
            }
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    public static function applyLokasiSamsatFilter($query, string $lokasiSamsatId): void
    {
        $query->whereIn('kota', SengSaamsat::lokasiFilterVariants($lokasiSamsatId));
    }

    /**
     * Lokasi samsat form tidak mempersempit query jika kecamatan dipilih (daftar kecamatan kabkota-wide).
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    public static function applyOptionalLokasiAndKecamatanFilters(
        $query,
        ?string $lokasiSamsatId,
        ?string $kecamatanSamsatId,
        bool $forceLokasi = false
    ): void {
        $lokasiSamsatId = trim((string) $lokasiSamsatId);
        $kecamatanSamsatId = trim((string) $kecamatanSamsatId);

        if ($lokasiSamsatId !== '' && ($forceLokasi || $kecamatanSamsatId === '')) {
            self::applyLokasiSamsatFilter($query, $lokasiSamsatId);
        }

        if ($kecamatanSamsatId !== '') {
            self::applyKecamatanFilter($query, $kecamatanSamsatId);
        }
    }
}
