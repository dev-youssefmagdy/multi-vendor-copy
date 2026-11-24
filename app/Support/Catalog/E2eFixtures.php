<?php

namespace App\Support\Catalog;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Isolation of the browser E2E fixtures (dedup plan Phase 8, rollout QA step 4).
 *
 * `php artisan e2e:prepare` and the admin specs write central catalog rows (products with sku `E2E-…`, categories
 * with slug `e2e-…`, coupons with code `E2E…`). Real tenants with no category roots carry "every central row", so
 * those fixtures leaked into them (vendor adopted 8 E2E products, showed the "E2E Gadgets" category and had the
 * E2E10 coupon live during every run). Every place that decides which central rows a tenant sees asks this class
 * for the fixture ids to hide: they are visible only to the dedicated E2E tenants.
 *
 * Production has no such rows, so the lists are empty there (one small indexed query per request, memoised).
 */
final class E2eFixtures
{
    /** Same ids as E2ePrepareCommand::TENANTS. */
    public const TENANTS = ['e2e-sync', 'e2e-central'];

    public const PRODUCT_SKU_PREFIX = 'E2E-';
    public const CATEGORY_SLUG_PREFIX = 'e2e-';
    public const COUPON_CODE_PREFIX = 'E2E';

    /** Long-running workers (queue sync jobs) re-read after this many seconds, so new fixtures are picked up. */
    private const MEMO_TTL = 60;

    /** @var array<string, array{0: int, 1: list<int>}> */
    private static array $memo = [];

    public static function isE2eTenant(?Tenant $tenant): bool
    {
        return $tenant !== null && in_array((string) $tenant->getTenantKey(), self::TENANTS, true);
    }

    /** True when fixtures must be hidden for this tenant (any real tenant; no tenant = central context, nothing hidden). */
    public static function hiddenFor(?Tenant $tenant = null): bool
    {
        $tenant ??= tenant();

        return $tenant instanceof Tenant && ! self::isE2eTenant($tenant);
    }

    /** @return list<int> central product ids to hide from $tenant */
    public static function hiddenProductIds(?Tenant $tenant = null): array
    {
        return self::hiddenFor($tenant) ? self::productIds() : [];
    }

    /** @return list<int> central category ids to hide from $tenant */
    public static function hiddenCategoryIds(?Tenant $tenant = null): array
    {
        return self::hiddenFor($tenant) ? self::categoryIds() : [];
    }

    /** @return list<int> central coupon ids to hide from $tenant */
    public static function hiddenCouponIds(?Tenant $tenant = null): array
    {
        return self::hiddenFor($tenant) ? self::couponIds() : [];
    }

    /** @return list<int> every central E2E product id (trashed included) */
    public static function productIds(): array
    {
        return self::remember('products', fn () => self::ids(fn ($db) => $db->table('products')
            ->where('sku', 'like', self::PRODUCT_SKU_PREFIX . '%')));
    }

    /** @return list<int> every central E2E category id */
    public static function categoryIds(): array
    {
        return self::remember('categories', fn () => self::ids(fn ($db) => $db->table('translations')
            ->where('translatable_type', (new \App\Models\Category())->getMorphClass())
            ->where('field', 'slug')
            ->where('value', 'like', self::CATEGORY_SLUG_PREFIX . '%')
            ->distinct()
            ->select('translatable_id as id')));
    }

    /** @return list<int> every central E2E coupon id */
    public static function couponIds(): array
    {
        return self::remember('coupons', fn () => self::ids(fn ($db) => $db->table('central_coupons')
            ->where('code', 'like', self::COUPON_CODE_PREFIX . '%')));
    }

    /** SQL fragment "`$column` is not an E2E fixture" for raw catalog SQL (integer literals only). */
    public static function notInSql(string $column, array $ids): string
    {
        return $ids === [] ? '1 = 1' : "{$column} NOT IN (" . implode(',', array_map('intval', $ids)) . ')';
    }

    public static function flush(): void
    {
        self::$memo = [];
    }

    /** @return list<int> */
    private static function remember(string $key, \Closure $load): array
    {
        $hit = self::$memo[$key] ?? null;
        if ($hit === null || time() - $hit[0] > self::MEMO_TTL) {
            $hit = self::$memo[$key] = [time(), $load()];
        }

        return $hit[1];
    }

    /** @return list<int> */
    private static function ids(\Closure $query): array
    {
        $db = DB::connection((string) config('tenancy.database.central_connection', 'mysql'));

        return array_values(array_map('intval', $query($db)->pluck('id')->all()));
    }
}
