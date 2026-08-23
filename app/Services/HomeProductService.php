<?php

namespace App\Services;

use App\Models\Tenant\Product;
use Illuminate\Support\Collection;

class HomeProductService
{
    public function getNewIn(int $limit, ?int $countryId = null): Collection
    {
        $badged = $this->byBadge('new-in', $limit, $countryId);

        if ($badged->isNotEmpty()) {
            return $badged;
        }

        return $this->baseQuery()
            ->orderByDesc('products.created_at')
            ->limit($limit)
            ->get();
    }

    public function getBestSelling(int $limit, ?int $countryId = null): Collection
    {
        $badged = $this->byBadge('best-selling', $limit, $countryId);

        if ($badged->isNotEmpty()) {
            return $badged;
        }

        return $this->baseQuery()
            ->orderByDesc('products.orders_count')
            ->orderByDesc('products.created_at')
            ->limit($limit)
            ->get();
    }

    public function getFeatured(int $limit, ?int $countryId = null): Collection
    {
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
    }

    public function getRecommended(int $limit, ?int $countryId = null): Collection
    {
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
