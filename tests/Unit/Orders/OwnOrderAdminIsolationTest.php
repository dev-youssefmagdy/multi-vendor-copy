<?php

namespace Tests\Unit\Orders;

use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use App\Services\Admin\OrderFulfillmentService;
use Tests\TestCase;

/** Merchant-fulfilled (own products) orders are read-only for platform admins. In-memory models only. */
class OwnOrderAdminIsolationTest extends TestCase
{
    private function order(array $ownFlags): Order
    {
        $order = new Order();
        $items = collect($ownFlags)->map(function (bool $own) {
            $item = new OrderItem();
            $item->setRelation('product', new Product(['is_own_product' => $own]));

            return $item;
        });
        $order->setRelation('items', $items);

        return $order;
    }

    public function test_discriminator(): void
    {
        $this->assertTrue($this->order([true, true])->isOwnProductsOrder());
        $this->assertFalse($this->order([false])->isOwnProductsOrder());
        $this->assertFalse($this->order([true, false])->isOwnProductsOrder());
        $this->assertFalse($this->order([])->isOwnProductsOrder());
    }

    public function test_service_guard_rejects_own_order_only(): void
    {
        OrderFulfillmentService::assertPlatformManageable($this->order([false]));
        $this->expectException(\RuntimeException::class);
        OrderFulfillmentService::assertPlatformManageable($this->order([true]));
    }
}
