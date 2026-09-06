<?php

namespace App\Services;

use App\Models\Tenant\Product;
use App\Support\CacheVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
