<?php

namespace App\Services;

use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
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
        $tags = array_merge(self::CACHE_TAGS, $extraTags);
        $version = collect($tags)->map(fn($tag) => CacheVersion::get($tag))->implode('.');
        $fullKey = 'storefront:' . (tenant()?->id ?? 'central') . ":home_products:{$key}:v{$version}";
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

            return $this->baseQuery()
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

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->whereNotIn('orders.status', self::NON_SALE_STATUSES)
            ->whereRaw('COALESCE(order_items.product_id, product_variants.product_id) IS NOT NULL')
            ->groupBy(DB::raw('COALESCE(order_items.product_id, product_variants.product_id)'))
            ->selectRaw(
                'COALESCE(order_items.product_id, product_variants.product_id) as sold_product_id,
                 SUM(order_items.qty) as qty_all,
                 SUM(CASE WHEN orders.created_at >= ? THEN order_items.qty ELSE 0 END) as qty_window',
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
            ->orderByRaw("EXISTS (SELECT 1 FROM product_badge_product pbp JOIN product_badges pbd ON pbd.id = pbp.product_badge_id WHERE pbp.product_id = products.id AND pbd.text = 'best-selling' AND pbd.active = 1) DESC")
            ->orderByDesc('products.created_at')
            ->orderByDesc('products.id');
    }

    public function getBestSelling(int $limit, ?int $countryId = null, int $days = 30): Collection
    {
        return $this->cacheRemember("best_selling:{$limit}:{$days}:" . ($countryId ?? 'default'), function () use ($limit, $days) {
            // Out-of-stock products are hidden here exactly like on the best-selling listing.
            return self::applyBestSellingOrder(\App\Repositories\Tenant\StorefrontRepository::excludeOutOfStock($this->baseQuery()), $days)
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

            return $this->baseQuery()
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

        $categoryIds = \App\Models\Tenant\Product::query()
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

        $products = $this->baseQuery()
            ->whereNotIn('products.id', $interactedIds)
            ->whereHas('categories', fn($q) => $q->whereIn('id', $categoryIds))
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

            return $this->baseQuery()
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

            $trending = $this->trendingByVelocity($limit);

            if ($trending->isNotEmpty()) {
                return $trending;
            }

            $excludeIds = $this->getNewIn($limit, $countryId)->pluck('id');

            return $this->baseQuery()
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
    protected function trendingByVelocity(int $limit, int $windowHours = 72): Collection
    {
        $now = now();
        $windowStart = (clone $now)->subHours($windowHours);

        $salesQuery = OrderItem::query()
            ->selectRaw(
                'COALESCE(order_items.product_id, product_variants.product_id) as product_id,
                 SUM(order_items.qty / (1 + TIMESTAMPDIFF(HOUR, orders.created_at, ?))) as trend_score',
                [$now]
            )
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where(function ($query) {
                $query->whereNotNull('order_items.product_id')
                    ->orWhereNotNull('product_variants.product_id');
            })
            ->where('orders.created_at', '>=', $windowStart)
            ->groupBy(DB::raw('COALESCE(order_items.product_id, product_variants.product_id)'));

        return $this->baseQuery()
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

        return $this->queryBadge($badgeText, $limit, null);
    }

    private function queryBadge(string $badgeText, int $limit, ?int $countryId): Collection
    {
        return $this->baseQuery()
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

    protected function baseQuery()
    {
        return Product::query()->where('products.active', true);
    }
}
