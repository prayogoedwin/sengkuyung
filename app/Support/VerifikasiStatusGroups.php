<?php

namespace App\Support;

use App\Models\SengStatusVerifikasi;

/**
 * Pusat resolver "bucket" status verifikasi yang dipakai dashboard & halaman verifikasi.
 *
 * - menunggu  : MENUNGGU VERIFIKASI + SUDAH DIPERBAIKI (perlu diverifikasi ulang)
 * - verifikasi: DIVERIFIKASI / TERVERIFIKASI
 * - ditolak   : DITOLAK + REVISI (kembali untuk diperbaiki)
 *
 * Resolve dilakukan berdasarkan nama (case-insensitive) supaya tidak terikat ID hard-code,
 * dengan fallback ke ID default kalau tabel master kosong atau nama tidak match.
 */
class VerifikasiStatusGroups
{
    /**
     * @return array{menunggu: int[], verifikasi: int[], ditolak: int[]}
     */
    public static function all(): array
    {
        $statuses = ApiCacheManager::remember(
            'admin:master:status-verifikasi:all',
            ApiCacheManager::masterTtl(),
            static fn () => SengStatusVerifikasi::select('id', 'nama')->get()
        );

        $byName = [];
        foreach ($statuses as $s) {
            $key = strtoupper(trim((string) ($s->nama ?? '')));
            if ($key === '') continue;
            $byName[$key] = (int) $s->id;
        }

        $pick = static function (array $names) use ($byName): array {
            $ids = [];
            foreach ($names as $name) {
                $key = strtoupper($name);
                if (isset($byName[$key])) {
                    $ids[] = $byName[$key];
                }
            }
            return array_values(array_unique($ids));
        };

        $menungguIds = $pick(['MENUNGGU VERIFIKASI', 'SUDAH DIPERBAIKI']);
        $verifikasiIds = $pick(['DIVERIFIKASI', 'TERVERIFIKASI']);
        $ditolakIds = $pick(['DITOLAK', 'REVISI', 'PERLU REVISI']);

        if (empty($menungguIds))   $menungguIds = [1, 5];
        if (empty($verifikasiIds)) $verifikasiIds = [2];
        if (empty($ditolakIds))    $ditolakIds = [3, 4];

        return [
            'menunggu' => $menungguIds,
            'verifikasi' => $verifikasiIds,
            'ditolak' => $ditolakIds,
        ];
    }

    /** @return int[] */
    public static function menungguIds(): array
    {
        return self::all()['menunggu'];
    }

    /** @return int[] */
    public static function verifikasiIds(): array
    {
        return self::all()['verifikasi'];
    }

    /** @return int[] */
    public static function ditolakIds(): array
    {
        return self::all()['ditolak'];
    }
}
