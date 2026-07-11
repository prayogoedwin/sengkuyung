<?php

namespace App\Services;

use App\Models\DataTertagih;
use App\Models\DataTertagihD2d;
use App\Models\DataTertagihDel;
use App\Models\DataTertagihD2dDel;
use App\Models\SengPendataanKendaraan;
use App\Models\SengPendataanKendaraanD2d;
use App\Models\SengPendataanKendaraanDel;
use App\Models\SengPendataanKendaraanD2dDel;
use App\Support\ApiCacheManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ForceDeletePendataanService
{
    /**
     * Force delete dari data_tertagih:
     * archive tertagih + pendataan terkait (by nopol), lalu hapus keduanya.
     *
     * @return array{tertagih: int, pendataan: int}
     */
    public function forceDeleteFromTertagih(int $id, bool $isD2d = false): array
    {
        $tertagihClass = $isD2d ? DataTertagihD2d::class : DataTertagih::class;
        $tertagihDelClass = $isD2d ? DataTertagihD2dDel::class : DataTertagihDel::class;
        $pendataanClass = $isD2d ? SengPendataanKendaraanD2d::class : SengPendataanKendaraan::class;
        $pendataanDelClass = $isD2d ? SengPendataanKendaraanD2dDel::class : SengPendataanKendaraanDel::class;

        return DB::transaction(function () use ($id, $tertagihClass, $tertagihDelClass, $pendataanClass, $pendataanDelClass, $isD2d) {
            /** @var Model $tertagih */
            $tertagih = $tertagihClass::query()->findOrFail($id);
            $nopol = (string) ($tertagih->no_polisi ?? '');

            $this->archiveModel($tertagih, $tertagihDelClass);

            $pendataanDeleted = 0;
            if (trim($nopol) !== '') {
                $pendataanRows = $this->findByNopol($pendataanClass, 'nopol', $nopol);
                foreach ($pendataanRows as $row) {
                    $this->archiveModel($row, $pendataanDelClass);
                    $this->hardDelete($row);
                    $pendataanDeleted++;
                }
            }

            $tertagih->delete();

            $this->forgetCaches($isD2d);

            return [
                'tertagih' => 1,
                'pendataan' => $pendataanDeleted,
            ];
        });
    }

    /**
     * Force delete dari verifikasi/pendataan:
     * archive pendataan + tertagih terkait (by nopol), lalu hapus keduanya.
     *
     * @return array{tertagih: int, pendataan: int}
     */
    public function forceDeleteFromPendataan(int $id, bool $isD2d = false): array
    {
        $tertagihClass = $isD2d ? DataTertagihD2d::class : DataTertagih::class;
        $tertagihDelClass = $isD2d ? DataTertagihD2dDel::class : DataTertagihDel::class;
        $pendataanClass = $isD2d ? SengPendataanKendaraanD2d::class : SengPendataanKendaraan::class;
        $pendataanDelClass = $isD2d ? SengPendataanKendaraanD2dDel::class : SengPendataanKendaraanDel::class;

        return DB::transaction(function () use ($id, $tertagihClass, $tertagihDelClass, $pendataanClass, $pendataanDelClass, $isD2d) {
            /** @var Model $pendataan */
            $pendataan = $pendataanClass::query()->findOrFail($id);
            $nopol = (string) ($pendataan->nopol ?? '');

            $this->archiveModel($pendataan, $pendataanDelClass);

            $tertagihDeleted = 0;
            if (trim($nopol) !== '') {
                $tertagihRows = $this->findByNopol($tertagihClass, 'no_polisi', $nopol);
                foreach ($tertagihRows as $row) {
                    $this->archiveModel($row, $tertagihDelClass);
                    $row->delete();
                    $tertagihDeleted++;
                }
            }

            $this->hardDelete($pendataan);

            $this->forgetCaches($isD2d);

            return [
                'tertagih' => $tertagihDeleted,
                'pendataan' => 1,
            ];
        });
    }

    /**
     * @param class-string<Model> $modelClass
     * @return \Illuminate\Support\Collection<int, Model>
     */
    private function findByNopol(string $modelClass, string $column, string $nopol)
    {
        $rawNopol = trim($nopol);
        $normalizedNopol = strtoupper(preg_replace('/\s+/', '', $rawNopol) ?? '');

        $query = $modelClass::query();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        $query->where(function ($q) use ($column, $rawNopol, $normalizedNopol) {
            $q->where($column, $rawNopol);

            if ($normalizedNopol !== '') {
                $q->orWhereRaw("REPLACE(UPPER(`{$column}`), ' ', '') = ?", [$normalizedNopol]);
            }
        });

        return $query->get();
    }

    /**
     * @param class-string<Model> $archiveClass
     */
    private function archiveModel(Model $model, string $archiveClass): void
    {
        $attributes = $model->getAttributes();

        // Hindari bentrok PK bila id sudah pernah diarsipkan.
        $existing = $archiveClass::query()->find($attributes['id'] ?? null);
        if ($existing) {
            unset($attributes['id']);
        }

        $archiveClass::query()->create($attributes);
    }

    private function hardDelete(Model $model): void
    {
        if (method_exists($model, 'forceDelete')) {
            $model->forceDelete();

            return;
        }

        $model->delete();
    }

    private function forgetCaches(bool $isD2d): void
    {
        if ($isD2d) {
            ApiCacheManager::forgetByPrefix('admin:data-tertagih-d2d:');
            ApiCacheManager::forgetByPrefix('admin:dashboard:');
            ApiCacheManager::forgetByPrefix('admin:rekap:');

            return;
        }

        ApiCacheManager::forgetByPrefix('admin:data-tertagih:');
        ApiCacheManager::forgetByPrefix('admin:dashboard:');
        ApiCacheManager::forgetByPrefix('admin:rekap:');
    }
}
