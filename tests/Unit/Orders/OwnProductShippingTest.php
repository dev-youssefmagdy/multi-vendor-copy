<?php

namespace Tests\Unit\Orders;

use App\Enums\DeliveryScope;
use App\Models\Tenant\Customer;
use App\Models\Product as CentralProduct;
use App\Models\Tenant\Product;
use App\Services\Tenant\Checkout\OrderComposer;
use App\Services\Tenant\Checkout\PlacesOrders;
use Tests\TestCase;

/**
 * Own (vendor-created) products carry no shipping; catalog (central-linked) products do.
 * In-memory models only: no DB access.
 */
class OwnProductShippingTest extends TestCase
{
    private function line(bool $own, string $key = 'k'): array
    {
        $product = new Product(['is_own_product' => $own]);

        if (!$own) {
            $central = new CentralProduct(['weight_grams' => 500]);
            $central->requires_shipping = true;
            $central->delivery_scope = DeliveryScope::AllZones;
            $product->setRelation('centralProduct', $central);
        } else {
            $product->setRelation('centralProduct', null);
        }

        return ['key' => $key, 'product' => $product, 'variant' => null, 'qty' => 2];
    }

    public function test_own_item_is_never_shippable_and_catalog_item_is(): void
    {
        $this->assertFalse(OrderComposer::isShippableCartItem($this->line(true)));
        $this->assertTrue(OrderComposer::isShippableCartItem($this->line(false)));
    }

    public function test_own_product_with_a_central_link_is_still_not_shippable(): void
    {
        $item = $this->line(false);
        $item['product']->is_own_product = true;

        $this->assertFalse(OrderComposer::isShippableCartItem($item));
    }

    public function test_own_only_cart_has_zero_shipping_and_no_error(): void
    {
        $calc = app(PlacesOrders::class)->resolveShippingCalculation(
            [$this->line(true, 'a'), $this->line(true, 'b')],
            null,
            null,
            new Customer(),
        );

        $this->assertTrue($calc['available']);
        $this->assertSame(0.0, $calc['amount']);
        $this->assertNull($calc['zone_rate_id']);
        $this->assertNull($calc['message']);
        $this->assertSame(['a' => 0.0, 'b' => 0.0], $calc['line_weights']);
    }

    public function test_mixed_cart_weight_comes_from_catalog_lines_only(): void
    {
        $items = [$this->line(true, 'own'), $this->line(false, 'cat')];
        $shippable = array_filter($items, fn($i) => OrderComposer::isShippableCartItem($i));

        $this->assertSame(['cat'], array_column($shippable, 'key'));
    }
}
