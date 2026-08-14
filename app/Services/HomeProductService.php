<?php

namespace App\Services;

use App\Models\Tenant\Product;
use Illuminate\Support\Collection;

class HomeProductService
{
    public function getNewIn(int $limit): Collection
    {
        $badged = $this->byBadge('new-in', $limit);

        if ($badged->isNotEmpty()) {
            return $badged;
        }

        return $this->baseQuery()
            ->orderByDesc('products.created_at')
            ->limit($limit)
            ->get();
    }

    public function getBestSelling(int $limit): Collection
    {
        $badged = $this->byBadge('best-selling', $limit);

        if ($badged->isNotEmpty()) {
            return $badged;
        }

        return $this->baseQuery()
            ->orderByDesc('products.orders_count')
            ->orderByDesc('products.created_at')
            ->limit($limit)
            ->get();
    }

    public function getFeatured(int $limit): Collection
    {
        $badged = $this->byBadge('featured', $limit);

        if ($badged->isNotEmpty()) {
            return $badged;
        }

        $excludeIds = $this->getNewIn($limit)->pluck('id');

        return $this->baseQuery()
            ->whereNotIn('products.id', $excludeIds)
            ->orderByDesc('products.created_at')
            ->limit($limit)
            ->get();
    }

    public function getRecommended(int $limit): Collection
    {
        $badged = $this->byBadge('recommended', $limit);

        if ($badged->isNotEmpty()) {
            return $badged;
        }

        $excludeIds = $this->getNewIn($limit)
            ->pluck('id')
            ->merge($this->getFeatured($limit)->pluck('id'));

        return $this->baseQuery()
            ->whereNotIn('products.id', $excludeIds)
            ->orderByDesc('products.created_at')
            ->limit($limit)
            ->get();
    }

    protected function byBadge(string $badgeText, int $limit): Collection
    {
        return $this->baseQuery()
            ->join('product_badge_product', 'product_badge_product.product_id', '=', 'products.id')
            ->join('product_badges', function ($join) use ($badgeText) {
                $join->on('product_badges.id', '=', 'product_badge_product.product_badge_id')
                    ->where('product_badges.text', $badgeText)
                    ->where('product_badges.active', true);
            })
            ->orderBy('product_badge_product.created_at')
            ->select('products.*')
            ->limit($limit)
            ->get();
    }

    protected function baseQuery()
    {
        return Product::query()->where('products.active', true);
    }
}
