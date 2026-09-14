<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\FixedShippingCost;
use App\Models\Tenant\Product;

final class ProductPriceListService
{
    /**
     * Build the price-list modal state — ported from ProductsList::openPriceListModal().
     */
    public function state(Product $product): array
    {
        $product->loadMissing('variants');

        [$shippingCosts, $centralProduct] = tenancy()->central(function () use ($product) {
            $costs = FixedShippingCost::query()
                ->where('is_active', true)
                ->with('country')
                ->get()
                ->keyBy('country_id');

            $central = $product->central_product_id
                ? \App\Models\Product::query()->with('variants')->find($product->central_product_id)
                : null;

            return [$costs, $central];
        });

        $centralSalePrice = $centralProduct
            ? (float) ($centralProduct->sale_price ?? $centralProduct->base_price ?? 0)
            : 0.0;

        $fixedJson = $centralProduct ? (array) ($centralProduct->fixed_shipping_costs ?? []) : [];
        $weightGrams = $centralProduct ? (int) ($centralProduct->weight_grams ?? 0) : 0;
        $shippingByCountry = ['default' => 0.0];
        foreach ($shippingCosts as $countryId => $record) {
            $key = (string) $countryId;
            $shippingByCountry[$key] = array_key_exists($key, $fixedJson)
                ? (float) $fixedJson[$key]
                : ($weightGrams > 0 ? round((float) $record->price_per_gram * $weightGrams, 2) : 0.0);
        }

        $countryLabels = ['default' => 'Default (no country)'];
        foreach ($shippingCosts as $countryId => $record) {
            $countryLabels[(string) $countryId] = $record->country?->name ?? ('Country #' . $countryId);
        }

        $productProfit = is_array($product->profit) ? $product->profit : [];
        $profitRows = [
            'default' => [
                'type' => $productProfit['default']['profit_type'] ?? 'percentage',
                'value' => (float) ($productProfit['default']['profit_value'] ?? 0),
            ],
        ];
        foreach ($shippingCosts as $countryId => $record) {
            $key = (string) $countryId;
            $profitRows[$key] = [
                'type' => $productProfit[$key]['profit_type'] ?? 'percentage',
                'value' => (float) ($productProfit[$key]['profit_value'] ?? 0),
            ];
        }

        $prices = $this->computePrices($centralSalePrice, $profitRows, $shippingByCountry);

        $centralVariantPrices = collect($centralProduct?->variants ?? [])
            ->keyBy('id')
            ->map(fn ($v) => (float) ($v->price ?? 0));

        $variants = $product->variants->load('centralVariant')->map(function ($variant) use ($centralVariantPrices) {
            $realPrice = (float) ($variant->real_price ?? 0);
            $variantProfit = is_array($variant->profit) ? $variant->profit : [];
            $variantShipping = (array) ($variant->centralVariant?->fixed_shipping_costs ?? []);

            $vProfitRows = [
                'default' => [
                    'type' => $variantProfit['default']['profit_type'] ?? 'percentage',
                    'value' => (float) ($variantProfit['default']['profit_value'] ?? 0),
                ],
            ];
            foreach ($variantShipping as $cId => $amount) {
                $k = (string) $cId;
                $vProfitRows[$k] = [
                    'type' => $variantProfit[$k]['profit_type'] ?? 'percentage',
                    'value' => (float) ($variantProfit[$k]['profit_value'] ?? 0),
                ];
            }

            return [
                'id' => $variant->id,
                'label' => $variant->display_label ?? 'Variant #' . $variant->id,
                'real_price' => $realPrice,
                'shipping' => $variantShipping,
                'central_price' => $centralVariantPrices->get($variant->central_product_variant_id, 0.0),
                'prices' => $this->computePrices($realPrice, $vProfitRows, $variantShipping),
                'profits' => $vProfitRows,
            ];
        })->values()->all();

        return [
            'productId' => $product->id,
            'productName' => $product->translationValue('name') ?? $product->slug ?? 'Product #' . $product->id,
            'prices' => $prices,
            'profits' => $profitRows,
            'variants' => $variants,
            'countryLabels' => $countryLabels,
            'centralSalePrice' => $centralSalePrice,
            'shippingByCountry' => $shippingByCountry,
        ];
    }

    /**
     * Preview endpoint — recompute product + variant prices from candidate profit
     * config without persisting, using the exact same PHP arithmetic as save().
     */
    public function preview(Product $product, float $centralSalePrice, array $profits, array $shippingByCountry, array $variantsInput): array
    {
        $prices = $this->computePrices($centralSalePrice, $profits, $shippingByCountry);

        $variantPrices = [];
        foreach ($variantsInput as $variantData) {
            $realPrice = (float) ($variantData['real_price'] ?? 0);
            $shipping = (array) ($variantData['shipping'] ?? $shippingByCountry);
            $variantPrices[$variantData['id']] = $this->computePrices($realPrice, $variantData['profits'] ?? [], $shipping);
        }

        return [
            'prices' => $prices,
            'variant_prices' => $variantPrices,
        ];
    }

    public function save(Product $product, array $profits, array $variantsInput, float $centralSalePrice, array $shippingByCountry): array
    {
        $product->loadMissing('variants');

        $profitJson = [];
        $prices = [];
        foreach ($profits as $key => $row) {
            $type = ($row['type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage';
            $value = max(0, round((float) ($row['value'] ?? 0), 4));
            $shipping = (float) ($shippingByCountry[(string) $key] ?? 0);
            $prices[(string) $key] = Product::computeFinalPrice($centralSalePrice, $type, $value, $shipping);
            $profitAmt = $type === 'fixed' ? round($value, 2) : round($centralSalePrice * $value / 100, 2);
            $profitJson[(string) $key] = ['profit_type' => $type, 'profit_value' => $value, 'total_profit' => $profitAmt];
        }

        $product->price = $prices;
        $product->default_price = $prices['default'] ?? (float) ($product->default_price ?? 0);
        $product->profit = $profitJson;
        $product->saveQuietly();

        foreach ($variantsInput as $variantData) {
            $variant = $product->variants->firstWhere('id', $variantData['id']);
            if (!$variant) {
                continue;
            }

            $realPrice = (float) ($variantData['real_price'] ?? $variant->real_price ?? 0);
            $vProfitJson = [];
            $vPrices = [];
            foreach (($variantData['profits'] ?? []) as $key => $row) {
                $type = ($row['type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage';
                $value = max(0, round((float) ($row['value'] ?? 0), 4));
                $shipping = (float) ($shippingByCountry[(string) $key] ?? 0);
                $vPrices[(string) $key] = Product::computeFinalPrice($realPrice, $type, $value, $shipping);
                $profitAmt = $type === 'fixed' ? round($value, 2) : round($realPrice * $value / 100, 2);
                $vProfitJson[(string) $key] = ['profit_type' => $type, 'profit_value' => $value, 'total_profit' => $profitAmt];
            }

            $variant->sell_price = $vPrices;
            $variant->default_sell_price = $vPrices['default'] ?? (float) ($variant->default_sell_price ?? 0);
            $variant->profit = $vProfitJson;
            $variant->saveQuietly();
        }

        $variantPrices = [];
        foreach ($product->variants as $variant) {
            $variantPrices[$variant->id] = is_array($variant->sell_price) ? $variant->sell_price : [];
        }

        return [
            'prices' => $prices,
            'variant_prices' => $variantPrices,
        ];
    }

    /** @param array<string, array{type:string,value:float}> $profitRows */
    private function computePrices(float $basePrice, array $profitRows, array $shippingByCountry): array
    {
        $prices = [];
        foreach ($profitRows as $key => $row) {
            $type = ($row['type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage';
            $value = max(0, (float) ($row['value'] ?? 0));
            $shipping = (float) ($shippingByCountry[(string) $key] ?? 0);
            $prices[(string) $key] = Product::computeFinalPrice($basePrice, $type, $value, $shipping);
        }

        return $prices;
    }
}
