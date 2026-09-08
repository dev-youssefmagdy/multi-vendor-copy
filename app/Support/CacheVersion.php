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
        return (int) Cache::driver('file')->get(self::key($tag), 1);
    }

    public static function bump(string $tag): void
    {
        Cache::driver('file')->forever(self::key($tag), self::get($tag) + 1);
    }

    protected static function key(string $tag): string
    {
        return 'cv:' . (tenant()?->id ?? 'central') . ':' . $tag;
    }
}
