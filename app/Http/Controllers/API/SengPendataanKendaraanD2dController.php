<?php

namespace App\Http\Controllers\API;

use App\Models\DataTertagihD2d;
use App\Models\SengPendataanKendaraanD2d;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SengPendataanKendaraanD2dController extends SengPendataanKendaraanController
{
    protected function pendataanModelClass(): string
    {
        return SengPendataanKendaraanD2d::class;
    }

    protected function dataTertagihModelClass(): string
    {
        return DataTertagihD2d::class;
    }

    protected function findPendataan(int $id): ?SengPendataanKendaraanD2d
    {
        return $this->pendataanModelClass()::find($id);
    }

    protected function markDataTertagihAsTerdata(string $nopol, ?User $user): int
    {
        $rawNopol = trim($nopol);

        if ($rawNopol === '') {
            return 0;
        }

        $tertagihUpdate = [
            'is_terdata' => 1,
            'updated_by' => $user?->id,
            'updated_at' => now(),
        ];

        $affectedRows = DataTertagihD2d::query()
            ->where('no_polisi', $rawNopol)
            ->update($tertagihUpdate);

        if ($affectedRows === 0) {
            $normalizedNopol = strtoupper(preg_replace('/\s+/', '', $rawNopol));

            if ($normalizedNopol !== '') {
                $affectedRows = DataTertagihD2d::query()
                    ->whereRaw("REPLACE(UPPER(no_polisi), ' ', '') = ?", [$normalizedNopol])
                    ->update($tertagihUpdate);
            }
        }

        if ($affectedRows === 0) {
            Log::warning('Data tertagih D2D tidak ter-update setelah pendataan D2D tersimpan.', [
                'nopol' => $rawNopol,
                'user_id' => $user?->id,
            ]);
        }

        return $affectedRows;
    }

    protected function secureFileBasePath(): string
    {
        return '/api/secure-file-d2d';
    }

    protected function suratPernyataanBasePath(): string
    {
        return '/surat_pernyataan_d2d';
    }
}
