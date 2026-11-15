<?php

namespace Tests\Unit\Orders;

use App\Models\Tenant\Product;
use App\Services\Tenant\Checkout\OrderComposer;
use Tests\TestCase;

/**
 * "Sold in countries" for vendor own products: NULL = all countries, [ids] = only those,
 * [] = hidden everywhere. Catalog (central-linked) products are never limited here.
 * In-memory models only: no DB access.
 */
class OwnProductCountryVisibilityTest extends TestCase
{
    private function own(?array $allowed): Product
    {
        $product = new Product();
        $product->forceFill(['is_own_product' => true, 'central_product_id' => null, 'slug' => 'own']);
        $product->allowed_country_ids = $allowed;

        return $product;
    }

    public function test_all_countries_is_visible_everywhere(): void
    {
        $product = $this->own(null);

        $this->assertTrue($product->isAvailableInCountry(116));
        $this->assertTrue($product->isAvailableInCountry(65));
        $this->assertTrue($product->isAvailableInCountry(null));
    }

    public function test_some_countries_is_visible_only_there(): void
    {
        $product = $this->own([116]);

        $this->assertTrue($product->isAvailableInCountry(116));
        $this->assertFalse($product->isAvailableInCountry(65));
        $this->assertFalse($product->isAvailableInCountry(null));
    }

    public function test_no_country_is_hidden_everywhere(): void
    {
        $product = $this->own([]);

        $this->assertFalse($product->isAvailableInCountry(116));
        $this->assertFalse($product->isAvailableInCountry(null));
        $this->assertSame(['own'], OrderComposer::itemsNotSoldInCountry([['product' => $product]], 116));
    }

    public function test_catalog_products_are_not_limited(): void
    {
        $product = new Product();
        $product->forceFill(['is_own_product' => false, 'central_product_id' => 5]);
        $product->allowed_country_ids = [];

        $this->assertTrue($product->isAvailableInCountry(116));
    }
}
