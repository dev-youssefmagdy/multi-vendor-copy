<?php

namespace Tests\Unit;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Vendor-own products are priced from the vendor's form (docs/ORDER_FORMULAS.md section 1):
 * sale price when set, else base price; same in every country; no central cost / profit / fixed shipping.
 * In-memory models only: no DB access (the own path never looks up FixedShippingCost).
 */
class OwnProductStorefrontPricingTest extends TestCase
{
    private function own(array $attributes = [], array $variants = []): Product
    {
        $product = new Product(array_merge([
            'price' => ['default' => 60],
            'default_price' => 60,
            'sale_price' => 49,
            'is_own_product' => true,
            'weight_grams' => 200,
            // Leftovers that must be ignored for own products.
            'profit' => ['default' => ['profit_type' => 'fixed', 'profit_value' => 5]],
            'fixed_shipping_costs' => ['116' => 20],
        ], $attributes));
        $product->setRelation('variants', new Collection($variants));
        $product->setRelation('flashSales', new Collection());
        $product->setRelation('centralProduct', null);

        return $product;
    }

    public function test_sale_price_is_shown_in_every_country_with_base_as_strikethrough(): void
    {
        foreach ([116, 52, 150, 142, null] as $countryId) {
            $pricing = $this->own()->storefrontPricing(null, $countryId);

            $this->assertSame(49.0, $pricing['current_price'], "country {$countryId}");
            $this->assertSame(60.0, $pricing['original_price']);
            $this->assertTrue($pricing['has_discount']);
            $this->assertSame(0.0, $pricing['cost']);
            $this->assertSame(49.0, $pricing['profit_amount']);
            $this->assertSame(0.0, $pricing['fixed_shipping']);
        }
    }

    public function test_base_price_is_used_when_no_sale_price_is_set(): void
    {
        $pricing = $this->own(['sale_price' => null])->storefrontPricing(null, 52);

        $this->assertSame(60.0, $pricing['current_price']);
        $this->assertNull($pricing['original_price']);
        $this->assertFalse($pricing['has_discount']);
    }

    public function test_variant_price_wins_for_own_variant_products(): void
    {
        $variant = new ProductVariant(['real_price' => 30, 'sell_price' => ['default' => 30], 'default_sell_price' => 30, 'active' => true]);
        $pricing = $this->own([], [$variant])->storefrontPricing($variant, 116);

        $this->assertSame(30.0, $pricing['current_price']);
        $this->assertSame(0.0, $pricing['fixed_shipping']);
    }

    public function test_country_targeting_only_restricts_own_products(): void
    {
        $restricted = $this->own(['allowed_country_ids' => [142]]);
        $this->assertTrue($restricted->isAvailableInCountry(142));
        $this->assertFalse($restricted->isAvailableInCountry(116));
        $this->assertFalse($restricted->isAvailableInCountry(null));

        $this->assertTrue($this->own()->isAvailableInCountry(116));

        $catalog = new Product(['is_own_product' => false, 'allowed_country_ids' => [142]]);
        $this->assertTrue($catalog->isAvailableInCountry(116));
    }
}
