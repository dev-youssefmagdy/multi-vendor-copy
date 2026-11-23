<?php

namespace App\Services;

use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Repositories\Tenant\Catalog\LabelCatalog;
use App\Support\CacheVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeProductService
{
    /** Model tags every list here depends on. */
    protected const CACHE_TAGS = ['Product', 'ProductVariant', 'ProductBadge'];

    protected function cacheRemember(string $key, \Closure $callback, array $extraTags = [])
    {
        // Dedup phase 2: central badge assignments / labels are read live, so central edits must invalidate (global
        // tags; QA D-P2-4 for labels).
        $tags = array_merge(
            self::CACHE_TAGS,
            $extraTags,
            BadgeCatalog::enabled() ? [BadgeCatalog::CACHE_TAG] : [],
            LabelCatalog::enabled() ? [LabelCatalog::CACHE_TAG] : [],
        );
        $version = collect($tags)->map(fn($tag) => CacheVersion::get($tag))->implode('.');
        // The products mode is part of the key: sync lists hold tenant ids, central lists merged keys.
        $mode = \App\Repositories\Tenant\Catalog\ProductCatalog::enabled() ? ':merged' : '';
        $fullKey = 'storefront:' . (tenant()?->id ?? 'central') . ":home_products:{$key}{$mode}:v{$version}";
        $ttl = (int) config('cache.storefront.home_products_ttl', 3600);

        return Cache::driver('file')->remember($fullKey, $ttl, $callback);
    }

    public function getNewIn(int $limit, ?int $countryId = null): Collection
    {
        return $this->cacheRemember("new_in:{$limit}:" . ($countryId ?? 'default'), function () use ($limit, $countryId) {
            $badged = $this->byBadge('new-in', $limit, $countryId);

            if ($badged->isNotEmpty()) {
                return $badged;
            }

            return $this->baseQuery($countryId)
                ->orderByDesc('products.created_at')
                ->limit($limit)
                ->get();
        });
    }

    /** Order statuses whose lines no longer count as a sale (see OrderStatus::isTerminal()). */
    public const NON_SALE_STATUSES = ['cancelled', 'rejected', 'refunded'];

    /**
     * Units sold per product: SUM(order_items.qty) over orders that were not
     * cancelled / rejected / refunded. `qty_window` only counts orders placed in
     * the last $days days, `qty_all` is lifetime. Lines are matched to a product
     * through order_items.product_id, falling back to the variant's product.
     */
    public static function salesSubquery(int $days = 30): \Illuminate\Database\Query\Builder
    {
        $since = now()->subDays(max(1, $days))->toDateTimeString();
        // Same key space as products.id (merged key in central products mode, tenant id otherwise).
        $key = \App\Repositories\Tenant\Catalog\ProductCatalog::orderLineKeySql();

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->whereNotIn('orders.status', self::NON_SALE_STATUSES)
            ->whereRaw("{$key} IS NOT NULL")
            ->groupBy(DB::raw($key))
            ->selectRaw(
                "{$key} as sold_product_id,
                 SUM(order_items.qty) as qty_all,
                 SUM(CASE WHEN orders.created_at >= ? THEN order_items.qty ELSE 0 END) as qty_window",
                [$since]
            );
    }

    /**
     * Real best-seller ranking shared by the home section and the listing page:
     * units sold in the last $days days, then lifetime units, then merchandiser
     * "best-selling" badge, then newest, then id (deterministic). Products with
     * no sales therefore never outrank sold ones.
     */
    public static function applyBestSellingOrder($query, int $days = 30)
    {
        return $query
            ->leftJoinSub(self::salesSubquery($days), 'bs_sales', 'bs_sales.sold_product_id', '=', 'products.id')
            ->select('products.*')
            ->orderByRaw('COALESCE(bs_sales.qty_window, 0) DESC')
            ->orderByRaw('COALESCE(bs_sales.qty_all, 0) DESC')
            ->orderByRaw(self::bestSellingBadgeSql() . ' DESC')
            ->orderByDesc('products.created_at')
            ->orderByDesc('products.id');
    }

    /** "has an active best-selling badge" (synced pivot, or the merged membership in badges = central mode). */
    protected static function bestSellingBadgeSql(): string
    {
        if (BadgeCatalog::enabled()) {
            return '(EXISTS (SELECT 1 FROM (' . BadgeCatalog::membershipSql() . ") pbp WHERE pbp.product_id = products.id AND pbp.text = 'best-selling' AND pbp.active = 1))";
        }

        return "EXISTS (SELECT 1 FROM product_badge_product pbp JOIN product_badges pbd ON pbd.id = pbp.product_badge_id WHERE pbp.product_id = products.id AND pbd.text = 'best-selling' AND pbd.active = 1)";
    }

    public function getBestSelling(int $limit, ?int $countryId = null, int $days = 30): Collection
    {
        return $this->cacheRemember("best_selling:{$limit}:{$days}:" . ($countryId ?? 'default'), function () use ($limit, $days, $countryId) {
            // Out-of-stock products are hidden here exactly like on the best-selling listing.
            return self::applyBestSellingOrder(\App\Repositories\Tenant\StorefrontRepository::excludeOutOfStock($this->baseQuery($countryId)), $days)
                ->limit($limit)
                ->get();
        }, ['Order', 'OrderItem']);
    }

    public function getFeatured(int $limit, ?int $countryId = null): Collection
    {
        return $this->cacheRemember("featured:{$limit}:" . ($countryId ?? 'default'), function () use ($limit, $countryId) {
            $badged = $this->byBadge('featured', $limit, $countryId);

            if ($badged->isNotEmpty()) {
                return $badged;
            }

            $excludeIds = $this->getNewIn($limit, $countryId)->pluck('id');

            return $this->baseQuery($countryId)
                ->whereNotIn('products.id', $excludeIds)
                ->orderByDesc('products.created_at')
                ->limit($limit)
                ->get();
        });
    }

    public function getRecommended(int $limit, ?int $countryId = null): Collection
    {
        return $this->cacheRemember("recommended:{$limit}:" . ($countryId ?? 'default'), function () use ($limit, $countryId) {
            return $this->byBadge('recommended', $limit, $countryId);
        });
    }

    /**
     * Personalized recommendations based on the current user's session interactions
     * (viewed and carted products). Returns products from the same categories as
     * the interacted products, excluding the ones already interacted with.
     * Falls back to newest products when no interactions exist.
     */
    public function getInteractionBased(int $limit, ?int $countryId = null): Collection
    {
        $interactedIds = app(\App\Services\UserInteractionTracker::class)->interactedProductIds();

        if (empty($interactedIds)) {
            return $this->getStaticFallback($limit, $countryId);
        }

        $categoryIds = \App\Repositories\Tenant\Catalog\ProductCatalog::query()
            ->whereIn('id', $interactedIds)
            ->where('active', true)
            ->with('categories:id')
            ->get()
            ->flatMap(fn($p) => $p->categories->pluck('id'))
            ->unique()
            ->values()
            ->all();

        if (empty($categoryIds)) {
            return $this->getStaticFallback($limit, $countryId);
        }

        $products = $this->baseQuery($countryId)
            ->whereNotIn('products.id', $interactedIds)
            // Qualified: category_product has its own surrogate `id` since dedup phase 5 (unqualified `id` was ambiguous
            // and 500'd the home page for any visitor with viewed products; Phase 8 E2E finding).
            ->whereHas('categories', fn($q) => $q->whereIn($q->getModel()->qualifyColumn('id'), $categoryIds))
            ->orderByDesc('products.orders_count')
            ->orderByDesc('products.created_at')
            ->limit($limit)
            ->get();

        if ($products->isEmpty()) {
            return $this->getStaticFallback($limit, $countryId);
        }

        return $products;
    }

    protected function getStaticFallback(int $limit, ?int $countryId = null): Collection
    {
        return $this->cacheRemember("recommended_fallback:{$limit}:" . ($countryId ?? 'default'), function () use ($limit, $countryId) {
            $excludeIds = $this->getNewIn($limit, $countryId)
                ->pluck('id')
                ->merge($this->getFeatured($limit, $countryId)->pluck('id'));

            return $this->baseQuery($countryId)
                ->whereNotIn('products.id', $excludeIds)
                ->orderByDesc('products.created_at')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * "Trending Now" — merchandiser curation (the badge) wins when set.
     *
     * When no products are manually assigned, momentum is computed from recent
     * order velocity with a recency decay so a fresh burst of sales in the last
     * few hours outranks a slow trickle from days ago. If nothing has sold
     * recently either, it falls back to the newest catalog products so the
     * section is never empty — the same guarantee every other badge section
     * gives.
     */
    public function getTrendingNow(int $limit, ?int $countryId = null): Collection
    {
        return $this->cacheRemember("trending_now:{$limit}:" . ($countryId ?? 'default'), function () use ($limit, $countryId) {
            $badged = $this->byBadge('trending-now', $limit, $countryId);

            if ($badged->isNotEmpty()) {
                return $badged;
            }

            $trending = $this->trendingByVelocity($limit, $countryId);

            if ($trending->isNotEmpty()) {
                return $trending;
            }

            $excludeIds = $this->getNewIn($limit, $countryId)->pluck('id');

            return $this->baseQuery($countryId)
                ->whereNotIn('products.id', $excludeIds)
                ->orderByDesc('products.created_at')
                ->limit($limit)
                ->get();
        }, ['Order', 'OrderItem']);
    }

    /**
     * Sales-velocity score over a short recent window, decayed by recency so an
     * order placed an hour ago outweighs one from three days ago.
     */
    protected function trendingByVelocity(int $limit, ?int $countryId = null, int $windowHours = 72): Collection
    {
        $now = now();
        $windowStart = (clone $now)->subHours($windowHours);

        // Same key space as products.id (merged key in central products mode, tenant id otherwise).
        $key = \App\Repositories\Tenant\Catalog\ProductCatalog::orderLineKeySql();

        $salesQuery = OrderItem::query()
            ->selectRaw(
                "{$key} as product_id,
                 SUM(order_items.qty / (1 + TIMESTAMPDIFF(HOUR, orders.created_at, ?))) as trend_score",
                [$now]
            )
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereRaw("{$key} IS NOT NULL")
            ->where('orders.created_at', '>=', $windowStart)
            ->groupBy(DB::raw($key));

        return $this->baseQuery($countryId)
            ->joinSub($salesQuery, 'trend_totals', fn($join) => $join->on('trend_totals.product_id', '=', 'products.id'))
            ->select('products.*')
            ->orderByDesc('trend_totals.trend_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Country-aware badge product query.
     *
     * Priority: country-specific rows if $countryId is given and any exist,
     * otherwise the default rows (country_id IS NULL).
     */
    protected function byBadge(string $badgeText, int $limit, ?int $countryId = null): Collection
    {
        if ($countryId !== null) {
            $results = $this->queryBadge($badgeText, $limit, $countryId);
            if ($results->isNotEmpty()) {
                return $results;
            }
        }

        return $this->queryBadge($badgeText, $limit, null, $countryId);
    }

    private function queryBadge(string $badgeText, int $limit, ?int $countryId, ?int $visitorCountryId = null): Collection
    {
        if (BadgeCatalog::enabled()) {
            // Merged membership (central pairs + tenant attaches + own badges, sort override applied), same filters.
            return $this->baseQuery($visitorCountryId ?? $countryId)
                ->joinSub(app(BadgeCatalog::class)->membershipQuery('mbp'), 'product_badge_product', 'product_badge_product.product_id', '=', 'products.id')
                ->where('product_badge_product.text', $badgeText)
                ->where('product_badge_product.active', true)
                ->when(
                    $countryId === null,
                    fn($q) => $q->whereNull('product_badge_product.country_id'),
                    fn($q) => $q->where('product_badge_product.country_id', $countryId),
                )
                ->orderBy('product_badge_product.sort_order')
                ->select('products.*')
                ->limit($limit)
                ->get();
        }

        return $this->baseQuery($visitorCountryId ?? $countryId)
            ->join('product_badge_product', 'product_badge_product.product_id', '=', 'products.id')
            ->join('product_badges', function ($join) use ($badgeText) {
                $join->on('product_badges.id', '=', 'product_badge_product.product_badge_id')
                    ->where('product_badges.text', $badgeText)
                    ->where('product_badges.active', true);
            })
            ->when(
                $countryId === null,
                fn($q) => $q->whereNull('product_badge_product.country_id'),
                fn($q) => $q->where('product_badge_product.country_id', $countryId),
            )
            ->orderBy('product_badge_product.sort_order')
            ->select('products.*')
            ->limit($limit)
            ->get();
    }

    /** Active products the visitor's country may see (own products' "sold in" targeting). */
    protected function baseQuery(?int $countryId = null)
    {
        return \App\Repositories\Tenant\Catalog\ProductCatalog::query()->where('products.active', true)->availableInCountry($countryId);
    }
}
