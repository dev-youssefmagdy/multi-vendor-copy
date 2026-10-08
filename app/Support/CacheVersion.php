<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Tenant-scoped cache-busting counters. Each tag (usually a model's class
 * basename) has its own monotonically increasing version; bumping it is
 * cheaper than tagging/flushing the cache store (the default `file` driver
 * doesn't support tags) and lets cache keys stay valid until the exact data
 * they depend on actually changes.
 */
class CacheVersion
{
    public static function get(string $tag): int
    {
        return (int) static::store()->get(self::key($tag), 1);
    }

    public static function bump(string $tag): void
    {
        static::store()->forever(self::key($tag), self::get($tag) + 1);
    }

    /**
     * Returns the cache store to use. Defaults to the file driver for
     * production (file-based cache doesn't support tags, so we manage
     * our own version counters). In test environments where CACHE_STORE
     * is set to 'array', we use that instead so tests don't need a
     * writable filesystem cache directory.
     */
    private static function store(): \Illuminate\Contracts\Cache\Repository
    {
        $configured = config('cache.default');
        return Cache::driver(app()->runningUnitTests() && $configured !== 'file' ? $configured : 'file');
    }

    protected static function key(string $tag): string
    {
        return 'cv:' . (tenant()?->id ?? 'central') . ':' . $tag;
    }
}
