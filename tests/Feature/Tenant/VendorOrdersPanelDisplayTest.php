<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\CancellationActor;
use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Enums\ReturnStatus;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Models\ReturnRequest;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\TenantNotification;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Mail\TemplateMailService;
use App\Services\ReturnRequestService;
use App\Services\Tenant\OrderLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Vendor panel Orders area (client round): order queue columns, a single shipping-status
 * notification, no manual refund on own-product orders, customer returns visible to the vendor.
 * One method because each tenant boot is expensive.
 */
class VendorOrdersPanelDisplayTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;
    use SetsUpTenantPanel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());

        Bus::fake([SyncCentralProductStockToTenantsJob::class]);
        FakePaymentGateway::install('stripe');
        $this->spy(TemplateMailService::class);
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    #[Test]
    public function order_queue_notifications_refund_button_and_customer_returns(): void
    {
        $f = $this->tenant->run(function (): array {
            $customer = $this->createCustomer();
            $own = $this->createOwnProductVariant(stock: 20, price: 50.0);
            $catalog = $this->createCentralCatalogVariant(stock: 20, price: 40.0)['variant'];

            $orders = [
                'ownPaid' => $this->createOrder($customer, [[$own, 1, 50.0]], OrderStatus::Processing, paid: true, stockDeducted: true),
                'catalogPaid' => $this->createOrder($customer, [[$catalog, 1, 40.0]], OrderStatus::Processing, paid: true, stockDeducted: true),
                'ownToShip' => $this->createOrder($customer, [[$own, 1, 50.0]], OrderStatus::Processing, paid: true, stockDeducted: true),
                'delivered' => $this->deliveredOrder($customer, $catalog),
            ];

            // BuildsOrders leaves order_items.grand_total at 0; the order total (and so the
            // refundable amount) is the sum of the stored line totals.
            foreach ($orders as $order) {
                $order->items()->update(['grand_total' => DB::raw('sub_total')]);
            }

            return ['customer' => $customer] + array_map(fn (Order $order) => $order->fresh('items.product'), $orders);
        });

        // ── 1) Order queue: no profit / "Platform Owes You" columns; "Platform Dues" header ─
        $this->page('/orders')
            ->assertOk()
            ->assertSee('Platform Dues')
            ->assertDontSee('You Owe Platform')
            ->assertDontSee('Vendor Profit')
            ->assertDontSee('Platform Profit')
            ->assertDontSee('Platform Owes You');

        $rows = $this->panel('get', '/orders/data?draw=1&start=0&length=25')->assertOk();
        $this->assertSame(4, $rows->json('recordsTotal'));
        $row = $rows->json('data.0');
        // (vendor_profit / platform_profit still come back as raw order attributes; they are just no
        // longer rendered as columns, which the header assertions above cover.)
        $this->assertArrayHasKey('vendor_own_platform', $row);
        $this->assertArrayNotHasKey('platform_own_vendor', $row);

        // Sorting (Placed At) and the status filter still work server side.
        $sorted = $this->panel('get', '/orders/data?'.http_build_query([
            'draw' => 1, 'start' => 0, 'length' => 25,
            'columns' => [['data' => 'placed_at', 'name' => 'created_at', 'orderable' => 'true', 'searchable' => 'false']],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]))->assertOk();
        $this->assertNull($sorted->json('error'), (string) $sorted->json('error'));
        $this->assertSame(4, $sorted->json('recordsFiltered'));
        $this->assertSame(1, $this->panel('get', '/orders/data?draw=1&start=0&length=25&filters[status]=delivered')->json('recordsFiltered'));

        // ── 2) Vendor changes the shipping status: one confirmation, no bell/toast to itself ─
        $this->panel('patch', "/orders/{$f['ownToShip']->id}/shipping-status", ['shipping_status' => OrderShippingStatus::Delivered->value])
            ->assertOk()
            ->assertJsonPath('message', 'Shipping status updated successfully.');
        $this->assertSame(0, $this->notificationsTitled('Order Shipping Status Updated'));

        // A change made by the platform still notifies the vendor.
        $this->tenant->run(fn () => app(OrderLifecycleService::class)
            ->updateShippingStatus($f['catalogPaid']->fresh(), OrderShippingStatus::Shipped, CancellationActor::Admin));
        $this->assertSame(1, $this->notificationsTitled('Order Shipping Status Updated'));

        // ── 3) Manual refund: hidden + refused on own-product orders, kept for catalog orders ─
        $this->page("/orders/{$f['ownPaid']->id}")
            ->assertOk()
            ->assertSee('order-refunds-panel', false)
            ->assertDontSee('order-refund-modal', false);
        $this->panel('post', "/orders/{$f['ownPaid']->id}/refunds/validate", ['amount' => 10, 'reason' => 'Goodwill'])->assertForbidden();
        $this->panel('post', "/orders/{$f['ownPaid']->id}/refunds", ['amount' => 10, 'reason' => 'Goodwill'])->assertForbidden();
        $this->assertSame(0, $this->tenant->run(fn () => $f['ownPaid']->fresh()->refundsQuery()->count()));

        $this->page("/orders/{$f['catalogPaid']->id}")->assertOk()->assertSee('order-refund-modal', false);

        // ── 4) A customer's return request reaches the vendor's Returns list and Pending count ─
        /** @var Order $delivered */
        $delivered = $f['delivered'];
        /** @var Customer $customer */
        $customer = $f['customer'];
        $request = $this->tenant->run(fn () => app(ReturnRequestService::class)->create([
            'tenant_id' => (string) tenant()->id,
            'order_number' => $delivered->uuid,
            'customer_id' => $customer->id,
            'order_item_id' => $delivered->items->first()->id,
            'quantity' => 1,
            'type' => 'return',
            'return_method' => 'ship_back',
            'reason' => 'changed_mind',
        ]));
        $this->assertSame(ReturnStatus::Pending, $request->status);

        $list = $this->panel('get', '/returns/data?draw=1&start=0&length=25')->assertOk();
        $this->assertSame(1, $list->json('recordsTotal'));
        $this->assertStringContainsString($delivered->uuid, (string) $list->json('data.0.order_number'));

        $pendingOnly = $this->panel('get', '/returns/data?draw=1&start=0&length=25&filters[status]=pending')->assertOk();
        $this->assertSame(1, $pendingOnly->json('recordsFiltered'));

        $this->page('/returns')->assertOk()->assertSee('Pending Review');
        $this->page("/returns/{$request->id}")->assertOk()->assertSee('approve-modal', false);
        $this->assertSame(1, ReturnRequest::query()->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(1, $this->tenant->run(fn () => app(TenantPanelRepository::class)->returnStats()['pending']));

        // Forwarded to the merchant by the platform: still pending the vendor's decision.
        $this->tenant->run(fn () => app(ReturnRequestService::class)->markAwaitingMerchantReview($request->fresh(), CancellationActor::Admin));
        $this->assertSame(ReturnStatus::AwaitingMerchantReview, $request->refresh()->status);
        $this->assertSame(1, $this->tenant->run(fn () => app(TenantPanelRepository::class)->returnStats()['pending']));
        $this->assertSame(1, $this->panel('get', '/returns/data?draw=1&start=0&length=25')->json('recordsTotal'));

        $this->assertNull(tenant());
    }

    private function deliveredOrder(Customer $customer, ProductVariant $variant): Order
    {
        $order = $this->createOrder($customer, [[$variant, 1, 40.0]], OrderStatus::Delivered, paid: true, stockDeducted: true);
        $order->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);

        return $order;
    }

    private function notificationsTitled(string $title): int
    {
        return (int) $this->tenant->run(fn () => TenantNotification::query()->where('title', $title)->count());
    }

    private function page(string $uri): TestResponse
    {
        try {
            return $this->actingAsTenantAdmin()->get($this->tenantUrl('/admin'.$uri));
        } finally {
            tenancy()->end();
        }
    }

    private function panel(string $method, string $uri, array $data = []): TestResponse
    {
        try {
            return $this->actingAsTenantAdmin()->json(strtoupper($method), $this->tenantUrl('/admin'.$uri), $data);
        } finally {
            tenancy()->end();
        }
    }
}
