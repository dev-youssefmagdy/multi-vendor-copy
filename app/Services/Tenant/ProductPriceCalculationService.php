<?php

namespace App\Services\Tenant;

use App\Models\FixedShippingCost;
use App\Models\Tenant;
use App\Models\Tenant\Product;

class ProductPriceCalculationService
{
    /**
     * Recalculates "Your Price" for every product and variant belonging to the
     * given tenant using a flat percentage profit for every country row, with
     * the exact same formula used by the Product Sell Prices / Variant Sell
     * Prices modal (ProductsList::computeProductPrices/computeVariantPrices):
     * profitAmt = round(base * percentage / 100, 2); price = round(base + profitAmt + shipping, 2).
     *
     * @return array{productsUpdated: int, variantsUpdated: int}
     */
    public function applyProfitPercentage(Tenant $tenant): array
    {
        $productsUpdated = 0;
        $variantsUpdated = 0;
        $profitPercentage = $tenant->profit_percentage;

        $tenant->run(function () use ($profitPercentage, &$productsUpdated, &$variantsUpdated) {
            $shippingCosts = tenancy()->central(function () {
                return FixedShippingCost::query()->where('is_active', true)->get()->keyBy('country_id');
            });

            Product::query()
                ->with('variants')
                ->chunkById(50, function ($products) use ($profitPercentage, $shippingCosts, &$productsUpdated, &$variantsUpdated) {
                    $centralProductIds = $products->pluck('central_product_id')->filter()->unique()->values();

                    $centralProducts = tenancy()->central(function () use ($centralProductIds) {
                        return \App\Models\Product::query()
                            ->with('variants')
                            ->whereIn('id', $centralProductIds)
                            ->get()
                            ->keyBy('id');
                    });

                    $now = now();
                    $productRows = [];
                    $variantRows = [];

                    foreach ($products as $product) {
                        $central = $product->central_product_id ? $centralProducts->get($product->central_product_id) : null;

                        $centralSalePrice = $central
                            ? (float) ($central->sale_price ?? $central->base_price ?? 0)
                            : (float) ($product->default_price ?? 0);

                        $fixedJson = $central ? (array) ($central->fixed_shipping_costs ?? []) : [];
                        $weightGrams = $central ? (int) ($central->weight_grams ?? 0) : 0;

                        $shippingByCountry = $this->buildShippingByCountry($shippingCosts, $fixedJson, $weightGrams);

                        [$prices, $profitJson] = $this->calculatePrices($centralSalePrice, $profitPercentage, $shippingByCountry);

                        $productRows[] = [
                            'id' => $product->id,
                            'price' => json_encode($prices),
                            'default_price' => $prices['default'] ?? (float) ($product->default_price ?? 0),
                            'profit' => json_encode($profitJson),
                            'updated_at' => $now,
                        ];
                        $productsUpdated++;

                        foreach ($product->variants as $variant) {
                            $realPrice = (float) ($variant->real_price ?? 0);

                            [$vPrices, $vProfitJson] = $this->calculatePrices($realPrice, $profitPercentage, $shippingByCountry);

                            $variantRows[] = [
                                'id' => $variant->id,
                                'product_id' => $variant->product_id,
                                'sell_price' => json_encode($vPrices),
                                'default_sell_price' => $vPrices['default'] ?? (float) ($variant->default_sell_price ?? 0),
                                'profit' => json_encode($vProfitJson),
                                'updated_at' => $now,
                            ];
                            $variantsUpdated++;
                        }
                    }

                    if (! empty($productRows)) {
                        Product::query()->upsert($productRows, ['id'], ['price', 'default_price', 'profit', 'updated_at']);
                    }

                    if (! empty($variantRows)) {
                        \App\Models\Tenant\ProductVariant::query()->upsert($variantRows, ['id'], ['sell_price', 'default_sell_price', 'profit', 'updated_at']);
                    }
                });
        });

        return [
            'productsUpdated' => $productsUpdated,
            'variantsUpdated' => $variantsUpdated,
        ];
    }

    protected function buildShippingByCountry($shippingCosts, array $fixedJson, int $weightGrams): array
    {
        $shippingByCountry = ['default' => 0.0];

        foreach ($shippingCosts as $countryId => $record) {
            $key = (string) $countryId;
            $shippingByCountry[$key] = array_key_exists($key, $fixedJson)
                ? (float) $fixedJson[$key]
                : ($weightGrams > 0 ? round((float) $record->price_per_gram * $weightGrams, 2) : 0.0);
        }

        return $shippingByCountry;
    }

    protected function calculatePrices(float $basePrice, float $profitPercentage, array $shippingByCountry): array
    {
        $prices = [];
        $profitJson = [];

        foreach ($shippingByCountry as $key => $shipping) {
            $profitAmt = round($basePrice * $profitPercentage / 100, 2);
            $prices[$key] = round($basePrice + $profitAmt + $shipping, 2);
            $profitJson[$key] = [
                'profit_type' => 'percentage',
                'profit_value' => $profitPercentage,
                'total_profit' => $profitAmt,
            ];
        }

        return [$prices, $profitJson];
    }
}
