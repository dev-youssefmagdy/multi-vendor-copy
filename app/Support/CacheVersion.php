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
    /**
     * Tags for central (landlord) data that tenant caches depend on. They are
     * stored once at `cv:global:{Tag}` regardless of the current tenancy, so a
     * central edit (bumped in central context) invalidates every tenant's keys.
     */
    public const GLOBAL_TAGS = ['HomeVariant', 'BladeTheme', 'CentralCategory', 'Country', 'Template'];

    /** Every tag the storefront caches are keyed on (see bumpStorefront()). */
    public const STOREFRONT_TAGS = [
        'Banner', 'Category', 'Currency', 'File', 'FlashSale', 'Language', 'Order', 'OrderItem',
        'Product', 'ProductBadge', 'ProductVariant', 'Setting', 'SocialLink', 'TenantHomeVariant',
        'TenantPageSection', 'TenantThemeColor', 'Theme', 'Translation',
    ];

    /**
     * Per-request memo of version reads, keyed by the full cache key (so it is
     * naturally tenant-scoped). Only used for HTTP requests: console processes
     * (queue workers, schedulers) are long-lived and must always see bumps made
     * by other processes.
     *
     * @var array<string, int>
     */
    protected static array $memo = [];

    public static function get(string $tag): int
    {
        $key = self::key($tag);

        if (! self::memoEnabled()) {
            return (int) static::store()->get($key, 1);
        }

        return static::$memo[$key] ??= (int) static::store()->get($key, 1);
    }

    public static function bump(string $tag): void
    {
        self::bumpMany([$tag]);
    }

    /**
     * Bumps several tags at once. Keys are resolved now (in the caller's tenancy
     * context) but written only after the surrounding DB transaction commits, so a
     * concurrent visitor can't cache pre-commit data under the new version; a
     * rolled-back transaction bumps nothing. Outside a transaction it runs at once.
     *
     * @param  string[]  $tags
     */
    public static function bumpMany(array $tags): void
    {
        $keys = array_map(fn (string $tag) => self::key($tag), array_unique($tags));

        if ($keys === []) {
            return;
        }

        $write = function () use ($keys) {
            $store = static::store();

            foreach ($keys as $key) {
                $store->forever($key, (int) $store->get($key, 1) + 1);
                unset(static::$memo[$key]);
            }
        };

        // The transactions manager is shared by every connection (central and
        // tenant), so this defers until whichever transaction is open commits.
        app()->bound('db.transactions') ? app('db.transactions')->addCallback($write) : $write();
    }

    /** Invalidates every storefront cache of the current tenant (manual "clear cache"). */
    public static function bumpStorefront(): void
    {
        self::bumpMany(self::STOREFRONT_TAGS);
    }

    /** Drops the in-process memo (tests / long-running callers). */
    public static function flushMemo(): void
    {
        static::$memo = [];
    }

    private static function memoEnabled(): bool
    {
        return ! app()->runningInConsole();
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
        $scope = in_array($tag, self::GLOBAL_TAGS, true) ? 'global' : (tenant()?->id ?? 'central');

        return 'cv:' . $scope . ':' . $tag;
    }
}
