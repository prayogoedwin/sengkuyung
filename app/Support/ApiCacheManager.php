<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class ApiCacheManager
{
    public const INDEX_KEY = 'api_cache_keys_index';

    public const MASTER_TTL_SECONDS = 86400;

    public const DATA_TTL_SECONDS = 1800;

    public const DASHBOARD_TTL_SECONDS = 600;

    // Backward compatibility: old usage points to data TTL.
    public const DEFAULT_TTL_SECONDS = self::DATA_TTL_SECONDS;

    public static function masterTtl(): int
    {
        return self::envTtl('CACHE_TTL_MASTER_SECONDS', self::MASTER_TTL_SECONDS);
    }

    public static function dataTtl(): int
    {
        return self::envTtl('CACHE_TTL_DATA_SECONDS', self::DATA_TTL_SECONDS);
    }

    public static function dashboardTtl(): int
    {
        return self::envTtl('CACHE_TTL_DASHBOARD_SECONDS', self::DASHBOARD_TTL_SECONDS);
    }

    public static function remember(string $key, int $ttlSeconds, Closure $callback): mixed
    {
        self::trackKey($key);

        return Cache::remember($key, $ttlSeconds, $callback);
    }

    public static function forget(string $key): bool
    {
        $forgotten = Cache::forget($key);

        self::untrackKey($key);

        return $forgotten;
    }

    public static function forgetByPrefix(string $prefix): int
    {
        $keys = self::getTrackedKeys();
        $deletedCount = 0;

        foreach ($keys as $key) {
            if (str_starts_with($key, $prefix)) {
                if (Cache::forget($key)) {
                    $deletedCount++;
                }

                self::untrackKey($key);
            }
        }

        return $deletedCount;
    }

    public static function getTrackedKeys(): array
    {
        $keys = Cache::get(self::INDEX_KEY, []);

        if (!is_array($keys)) {
            return [];
        }

        sort($keys);

        return array_values(array_unique($keys));
    }

    private static function trackKey(string $key): void
    {
        $keys = Cache::get(self::INDEX_KEY, []);

        if (!is_array($keys)) {
            $keys = [];
        }

        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever(self::INDEX_KEY, $keys);
        }
    }

    private static function untrackKey(string $key): void
    {
        $keys = Cache::get(self::INDEX_KEY, []);

        if (!is_array($keys) || empty($keys)) {
            return;
        }

        $keys = array_values(array_filter($keys, static fn ($item) => $item !== $key));
        Cache::forever(self::INDEX_KEY, $keys);
    }

    private static function envTtl(string $envKey, int $default): int
    {
        $value = env($envKey, $default);
        $ttl = is_numeric($value) ? (int) $value : $default;

        return $ttl > 0 ? $ttl : $default;
    }
}
