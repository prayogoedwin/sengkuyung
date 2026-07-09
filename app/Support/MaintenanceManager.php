<?php

namespace App\Support;

use App\Models\StatusMaintenance;
use Illuminate\Support\Facades\Redis;

class MaintenanceManager
{
    public const REDIS_KEY = 'system:maintenance';

    public static function isActive(): bool
    {
        return (int) Redis::get(self::REDIS_KEY) === 1;
    }

    public static function set(bool $active, ?int $updatedBy = null): void
    {
        $record = StatusMaintenance::query()->firstOrCreate([]);
        $record->maintenance = $active;
        $record->updated_by = $updatedBy;
        $record->save();

        if ($active) {
            Redis::set(self::REDIS_KEY, 1);

            return;
        }

        Redis::del(self::REDIS_KEY);
    }

    public static function syncFromDb(): void
    {
        $record = StatusMaintenance::query()->first();
        if ($record && $record->maintenance) {
            Redis::set(self::REDIS_KEY, 1);

            return;
        }

        Redis::del(self::REDIS_KEY);
    }

    public static function clearRedisKey(): int
    {
        return (int) Redis::del(self::REDIS_KEY);
    }
}
