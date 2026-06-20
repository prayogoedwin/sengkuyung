<?php

namespace App\Support;

use App\Models\SengSaamsat;
use App\Models\SengWilayah;
use App\Models\WilayahSamsat;
use Illuminate\Support\Facades\DB;

class PendataanWilayahFilter
{
    /**
     * Role yang lokasi samsat profil terkunci ke filter (tidak bisa ganti di form).
     */
    public static function shouldForceProfileLokasiSamsat(?object $user): bool
    {
        if (!$user || trim((string) ($user->lokasi_samsat ?? '')) === '') {
            return false;
        }

        return $user->hasAnyRole(['kabkota', 'kecamatan', 'kelurahan', 'petugas', 'petugas-d2d']);
    }

    /**
     * UPPD/UPTD & admin boleh filter per samsat dari form; role terkunci pakai profil.
     */
    public static function resolveLokasiSamsatFilterValue(?object $user, ?string $requestLokasiSamsat): string
    {
        if (self::shouldForceProfileLokasiSamsat($user)) {
            return trim((string) $user->lokasi_samsat);
        }

        return trim((string) $requestLokasiSamsat);
    }

    /**
     * Normalisasi users.kota ke id kabkota dagri (3329), bukan id wilayah samsat (27).
     */
    public static function resolveScopedUserKabkotaId(?object $user): ?string
    {
        if (!$user) {
            return null;
        }

        $kota = trim((string) ($user->kota ?? ''));
        if ($kota === '') {
            $kota = trim((string) ($user->lokasi_samsat ?? ''));
        }
        if ($kota === '') {
            return null;
        }

        if (SengWilayah::query()->where('id', $kota)->where('id_up', 33)->exists()) {
            return $kota;
        }

        $fromWilayahSamsat = WilayahSamsat::query()->where('id', $kota)->value('kabkota');
        if ($fromWilayahSamsat !== null && (string) $fromWilayahSamsat !== '') {
            return (string) $fromWilayahSamsat;
        }

        $fromSamsat = SengSaamsat::query()
            ->where(function ($q) use ($kota) {
                $q->where('id', $kota)->orWhere('id_wilayah_samsat', $kota);
            })
            ->value('kabkota');

        return $fromSamsat !== null && (string) $fromSamsat !== ''
            ? (string) $fromSamsat
            : $kota;
    }

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
     * Terapkan filter lokasi dan/atau kecamatan bila diisi (keduanya bisa aktif bersamaan).
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    public static function applyOptionalLokasiAndKecamatanFilters(
        $query,
        ?string $lokasiSamsatId,
        ?string $kecamatanSamsatId
    ): void {
        $lokasiSamsatId = trim((string) $lokasiSamsatId);
        $kecamatanSamsatId = trim((string) $kecamatanSamsatId);

        if ($lokasiSamsatId !== '') {
            self::applyLokasiSamsatFilter($query, $lokasiSamsatId);
        }

        if ($kecamatanSamsatId !== '') {
            self::applyKecamatanFilter($query, $kecamatanSamsatId);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    public static function applyDataTertagihLokasiFilter($query, string $lokasiSamsatId): void
    {
        $query->whereIn('id_lokasi_samsat', SengSaamsat::lokasiFilterVariants($lokasiSamsatId));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    public static function applyDataTertagihKecamatanFilter($query, string $kecamatanSamsatId): void
    {
        $query->whereIn('id_kecamatan', self::samsatCodeVariants($kecamatanSamsatId));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    public static function applyOptionalDataTertagihLokasiAndKecamatanFilters(
        $query,
        ?string $lokasiSamsatId,
        ?string $kecamatanSamsatId
    ): void {
        $lokasiSamsatId = trim((string) $lokasiSamsatId);
        $kecamatanSamsatId = trim((string) $kecamatanSamsatId);

        if ($lokasiSamsatId !== '') {
            self::applyDataTertagihLokasiFilter($query, $lokasiSamsatId);
        }

        if ($kecamatanSamsatId !== '') {
            self::applyDataTertagihKecamatanFilter($query, $kecamatanSamsatId);
        }
    }
}
