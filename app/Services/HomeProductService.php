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

    public function getBestSelling(int $limit, ?int $countryId = null): Collection
    {
        return $this->cacheRemember("best_selling:{$limit}:" . ($countryId ?? 'default'), function () use ($limit, $countryId) {
            $badged = $this->byBadge('best-selling', $limit, $countryId);

            if ($badged->isNotEmpty()) {
                return $badged;
            }

            return $this->baseQuery()
                ->orderByDesc('products.orders_count')
                ->orderByDesc('products.created_at')
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
            $badged = $this->byBadge('recommended', $limit, $countryId);

            if ($badged->isNotEmpty()) {
                return $badged;
            }

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
