<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class UserInteractionTracker
{
    private const SESSION_KEY = 'storefront_product_interactions';
    private const MAX_IDS = 30;

    public function trackView(int $productId): void
    {
        $this->push('viewed', $productId);
    }

    public function trackCart(int $productId): void
    {
        $this->push('carted', $productId);
    }

    /** Returns all interacted product IDs, carted ones first (higher weight). */
    public function interactedProductIds(): array
    {
        $data = Session::get(self::SESSION_KEY, ['viewed' => [], 'carted' => []]);
        return array_values(array_unique(array_merge($data['carted'] ?? [], $data['viewed'] ?? [])));
    }

    private function push(string $type, int $productId): void
    {
        $data = Session::get(self::SESSION_KEY, ['viewed' => [], 'carted' => []]);

        $list = $data[$type] ?? [];
        $list = array_filter($list, fn($id) => $id !== $productId);
        array_unshift($list, $productId);
        $data[$type] = array_slice($list, 0, self::MAX_IDS);

        Session::put(self::SESSION_KEY, $data);
    }
}
