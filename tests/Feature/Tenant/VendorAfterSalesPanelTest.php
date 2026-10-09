<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\ProductVariant;
use App\Services\Mail\TemplateMailService;
use App\Services\Orders\OrderPolicyService;
use App\Services\ReturnRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Phase 6 vendor panel UI: order / return show pages, the new return action endpoints, the
 * Sales > Refunds list and the "Cancellation & Refunds" settings card. Consolidated into two
 * methods because each tenant boot is expensive.
 */
class VendorAfterSalesPanelTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
        // HTML pages sit behind the onboarding-tour middleware.
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());

        Bus::fake([SyncCentralProductStockToTenantsJob::class]);
        $this->gateway = FakePaymentGateway::install('stripe');
        $this->spy(TemplateMailService::class);
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    #[Test]
    public function order_pages_refunds_list_and_policy_settings(): void
    {
        [$customer, $variant] = $this->tenant->run(fn (): array => [
            $this->createCustomer(),
            $this->createOwnProductVariant(stock: 20, price: 50.0),
        ]);

        $pending = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 2]], OrderStatus::Pending));
        $processing = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 2]], OrderStatus::Processing, paid: true, stockDeducted: true));
        $toCancel = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 2]], OrderStatus::Processing, paid: true, stockDeducted: true));
        $refundedOrder = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 2]], OrderStatus::Refunded, paid: true, attributes: ['refunded_amount' => 100, 'refunded_at' => now()]));

        // ── Order show: pending (cancel action, shipping controls, empty refunds) ─
        $this->page("/orders/{$pending->id}")
            ->assertOk()
            ->assertSee('cancel-order-modal', false)
            ->assertSee('Cancel order')
            ->assertSee('order-refunds-panel', false)
            ->assertSee('No refunds have been issued for this order yet.')
            ->assertSee('New Status')
            ->assertDontSee('order-cancellation-panel', false)
            ->assertDontSee('order-refund-modal', false);

        // ── Processing + paid: manual refund modal offered ──────────────────
        $this->page("/orders/{$processing->id}")
            ->assertOk()
            ->assertSee('cancel-order-modal', false)
            ->assertSee('order-refund-modal', false)
            ->assertSee('Still refundable');

        // ── Cancel through the panel → cancelled page with refund + cancellation info ─
        $this->panel('post', "/orders/{$toCancel->id}/cancel", ['reason' => 'other', 'note' => 'Supplier <b>issue</b>'])->assertOk();
        $refund = $this->tenant->run(fn () => $toCancel->fresh()->refundsQuery()->sole());
        $this->assertSame(RefundStatus::Completed, $refund->status);

        $this->page("/orders/{$toCancel->id}")
            ->assertOk()
            ->assertSee('order-cancellation-panel', false)
            ->assertSee('Supplier &lt;b&gt;issue&lt;/b&gt;', false)
            ->assertDontSee('Supplier <b>issue</b>', false)
            ->assertSee($refund->reference)
            ->assertSee('Refunded')
            ->assertDontSee('cancel-order-modal', false)
            ->assertDontSee('New Status');

        // ── Refunded order: no cancel action, no shipping controls ──────────
        $this->page("/orders/{$refundedOrder->id}")
            ->assertOk()
            ->assertSee('Refunded')
            ->assertDontSee('cancel-order-modal', false)
            ->assertDontSee('New Status');

        // ── Orders list: Refunded filter + payment state indicator ──────────
        $list = $this->panel('get', '/orders/data?draw=1&start=0&length=25&filters[status]=refunded')->assertOk();
        $this->assertSame(1, $list->json('recordsFiltered'));
        $this->assertStringContainsString('Refunded', (string) $list->json('data.0.value'));
        $this->page('/orders')->assertOk()->assertSee('Refunded');

        // ── Refunds list ────────────────────────────────────────────────────
        $failed = Refund::query()->create([
            'tenant_id' => (string) $this->tenant->id,
            'order_number' => $pending->uuid,
            'source' => RefundSource::Manual,
            'reason' => '<script>alert(1)</script>',
            'items_amount' => 10,
            'amount' => 10,
            'refund_method' => RefundMethod::Manual,
            'status' => RefundStatus::Failed,
        ]);
        $foreign = Refund::query()->create([
            'tenant_id' => 'another-tenant',
            'order_number' => 'other-order',
            'source' => RefundSource::Manual,
            'reason' => 'foreign',
            'items_amount' => 5,
            'amount' => 5,
            'refund_method' => RefundMethod::Manual,
            'status' => RefundStatus::Pending,
        ]);

        $this->page('/refunds')
            ->assertOk()
            ->assertSee('Total Refunded')
            ->assertSee('refunds-table', false);

        $all = $this->panel('get', '/refunds/data?draw=1&start=0&length=25')->assertOk();
        $this->assertSame(2, $all->json('recordsTotal'), 'only this tenant\'s refunds are listed');
        $this->assertStringNotContainsString('<script>alert(1)', $all->getContent());
        $this->assertStringNotContainsString($foreign->reference, $all->getContent());
        $this->assertStringContainsString('&lt;script&gt;', $all->getContent());
        $this->assertStringNotContainsString('"meta"', $all->getContent());

        $onlyFailed = $this->panel('get', '/refunds/data?draw=1&start=0&length=25&filters[status]=failed')->assertOk();
        $this->assertSame(1, $onlyFailed->json('recordsFiltered'));
        $this->assertStringContainsString($failed->reference, (string) $onlyFailed->json('data.0.reference'));

        $this->assertSame(1, $this->panel('get', '/refunds/data?draw=1&start=0&length=25&filters[source]=cancellation')->json('recordsFiltered'));
        $this->assertSame(1, $this->panel('get', '/refunds/data?draw=1&start=0&length=25&filters[search]='.$refund->reference)->json('recordsFiltered'));
        $this->assertSame(0, $this->panel('get', '/refunds/data?draw=1&start=0&length=25&filters[search]=nothing-matches')->json('recordsFiltered'));

        // ── Settings → Return Policy: Cancellation & Refunds card ───────────
        $this->page('/settings/return-policy')
            ->assertOk()
            ->assertSee('Cancellation &amp; Refunds', false)
            ->assertSee('cancellation_window_hours', false);

        $payload = ['window_days' => 14, 'fee' => 0];
        $this->panel('post', '/settings/return-policy/validate', $payload + ['cancellation_window_hours' => -1])
            ->assertStatus(422)->assertJsonValidationErrors(['cancellation_window_hours']);

        $this->panel('put', '/settings/return-policy', $payload + [
            'cancellation_allow_processing' => 0,
            'cancellation_window_hours' => 12,
            'auto_refund_on_cancel' => 0,
            'exchange_enabled' => 0,
            'restock_returned_items' => 1,
        ])->assertOk();

        $policy = $this->tenant->run(fn () => app(OrderPolicyService::class)->all());
        $this->assertSame([
            'cancellation_allow_processing' => false,
            'cancellation_window_hours' => 12,
            'auto_refund_on_cancel' => false,
            'exchange_enabled' => false,
            'restock_returned_items' => true,
        ], $policy);

        // A legacy payload without the new keys leaves the policy untouched.
        $this->panel('put', '/settings/return-policy', $payload)->assertOk();
        $this->assertSame(12, $this->tenant->run(fn () => app(OrderPolicyService::class)->all()['cancellation_window_hours']));

        // ── Permissions ─────────────────────────────────────────────────────
        $this->tenant->run(function (): void {
            $this->ownerRole->update(['permissions' => array_values(array_diff($this->ownerRole->permissions, ['sales.orders.manage', 'sales.returns.manage']))]);
        });
        $this->admin->unsetRelation('role');

        $this->page('/refunds')->assertRedirect(); // HTML pages bounce back, JSON gets a 403
        $this->panel('get', '/refunds/data?draw=1')->assertForbidden();
        $this->panel('put', '/settings/return-policy', $payload)->assertForbidden();
        // Viewing an order still works, but without the cancel / refund actions.
        $this->page("/orders/{$processing->id}")
            ->assertOk()
            ->assertDontSee('cancel-order-modal', false)
            ->assertDontSee('order-refund-modal', false);

        $this->assertNull(tenant());
    }

    #[Test]
    public function return_pages_and_action_endpoints(): void
    {
        $fixture = $this->tenant->run(function (): array {
            $customer = $this->createCustomer();
            $a = $this->createOwnProductVariant(stock: 20, price: 100.0);
            $b = $this->createVariantFor($a->product, stock: 5, price: 100.0);

            return [
                'customer' => $customer,
                'a' => $a,
                'b' => $b,
                'ret' => $this->deliveredOrder($customer, $a, 1),
                'exchange' => $this->deliveredOrder($customer, $a, 1),
                'convert' => $this->deliveredOrder($customer, $a, 1),
                'legacy' => $this->deliveredOrder($customer, $a, 1),
            ];
        });
        /** @var Customer $customer */
        $customer = $fixture['customer'];
        /** @var ProductVariant $b */
        $b = $fixture['b'];
        $service = app(ReturnRequestService::class);

        $make = fn (Order $order, array $extra = []) => $this->tenant->run(fn () => $service->create(array_merge([
            'tenant_id' => (string) $this->tenant->id,
            'order_number' => $order->uuid,
            'customer_id' => $customer->id,
            'order_item_id' => $order->items->first()->id,
            'quantity' => 1,
            'type' => 'return',
            'return_method' => 'ship_back',
            'reason' => 'changed_mind',
            'description' => null,
            'customer_note' => 'Please hurry',
        ], $extra)));

        // ── Return (refund path): pending page → approve → received → inspect → refund ─
        $ret = $make($fixture['ret']);
        $this->page("/returns/{$ret->id}")
            ->assertOk()
            ->assertSee('Pending Review')
            ->assertSee('Please hurry')
            ->assertSee('approve-modal', false)
            ->assertSee('reject-modal', false)
            ->assertDontSee('inspect-modal', false)
            ->assertDontSee('issue-refund-modal', false)
            ->assertSee('No refunds have been issued for this order yet.');

        // Actions that are not allowed yet are refused with a 422, not a 500.
        $this->panel('post', "/returns/{$ret->id}/inspect", ['inspection_result' => 'passed'])->assertStatus(422);
        $this->panel('post', "/returns/{$ret->id}/issue-refund", ['amount' => 10])->assertStatus(422);
        $this->panel('post', "/returns/{$ret->id}/close")->assertStatus(422);

        $this->panel('post', "/returns/{$ret->id}/approve/validate", ['approve_note' => str_repeat('x', 2001)])->assertStatus(422)->assertJsonValidationErrors(['approve_note']);
        $this->panel('post', "/returns/{$ret->id}/approve", ['approve_note' => 'Send it back soon'])
            ->assertOk()->assertJsonPath('data.returnRecord.status', 'approved');
        $this->panel('post', "/returns/{$ret->id}/received")->assertOk()->assertJsonPath('data.returnRecord.status', 'item_received');

        $this->page("/returns/{$ret->id}")->assertOk()->assertSee('inspect-modal', false)->assertSee('Record Inspection');

        $this->panel('post', "/returns/{$ret->id}/inspect/validate", [])->assertStatus(422)->assertJsonValidationErrors(['inspection_result']);
        $this->panel('post', "/returns/{$ret->id}/inspect/validate", ['inspection_result' => 'bogus'])->assertStatus(422)->assertJsonValidationErrors(['inspection_result']);
        $this->panel('post', "/returns/{$ret->id}/inspect/validate", ['inspection_result' => 'passed'])->assertOk()->assertJson(['valid' => true]);
        $this->panel('post', "/returns/{$ret->id}/inspect", ['inspection_result' => 'passed', 'inspection_notes' => 'Unused', 'restock' => 1])
            ->assertOk()
            ->assertJsonPath('data.returnRecord.status', 'inspected')
            ->assertJsonPath('data.returnRecord.inspection_result', 'passed');
        $this->assertNotNull($ret->refresh()->restocked_at);

        $this->page("/returns/{$ret->id}")
            ->assertOk()
            ->assertSee('Inspected')
            ->assertSee('Unused')
            ->assertSee('issue-refund-modal', false)
            ->assertSee('Maximum refund')
            ->assertSee('value="100.00"', false);

        // The refund amount is capped at the calculated maximum and must be positive.
        $this->panel('post', "/returns/{$ret->id}/issue-refund/validate", [])->assertStatus(422)->assertJsonValidationErrors(['amount']);
        $this->panel('post', "/returns/{$ret->id}/issue-refund/validate", ['amount' => 0])->assertStatus(422)->assertJsonValidationErrors(['amount']);
        $this->panel('post', "/returns/{$ret->id}/issue-refund/validate", ['amount' => 100.01])->assertStatus(422)->assertJsonValidationErrors(['amount']);
        $this->panel('post', "/returns/{$ret->id}/issue-refund", ['amount' => 100.01])->assertStatus(422);
        $this->panel('post', "/returns/{$ret->id}/issue-refund/validate", ['amount' => 60])->assertOk();
        $this->panel('post', "/returns/{$ret->id}/issue-refund", ['amount' => 60])
            ->assertOk()
            ->assertJsonPath('data.refund.status', 'completed')
            ->assertJsonPath('data.refund.amount', 60)
            ->assertJsonPath('data.refund.return_request_id', $ret->id)
            ->assertJsonPath('data.returnRecord.status', 'refunded');
        $this->gateway->assertRefunded(60.0, $fixture['ret']->payment_details['transaction_id']);

        $refund = Refund::query()->where('return_request_id', $ret->id)->sole();
        $this->page("/returns/{$ret->id}")
            ->assertOk()
            ->assertSee('Refunded')
            ->assertSee($refund->reference)
            ->assertDontSee('issue-refund-modal', false)
            ->assertSee('Close Request');

        // Returns list shows type and qty columns.
        $index = $this->panel('get', '/returns/data?draw=1&start=0&length=25')->assertOk();
        $this->assertGreaterThanOrEqual(1, $index->json('recordsTotal'));
        $this->assertArrayHasKey('type', $index->json('data.0'));
        $this->assertArrayHasKey('quantity', $index->json('data.0'));

        $this->panel('post', "/returns/{$ret->id}/close")->assertOk()->assertJsonPath('data.returnRecord.status', 'closed');

        // ── Exchange: approve reserves stock, ship with tracking, complete ───
        $exchange = $make($fixture['exchange'], ['type' => 'exchange', 'replacement_product_variant_id' => $b->id]);
        $this->assertSame(ReturnType::Exchange, $exchange->type);
        $this->page("/returns/{$exchange->id}")
            ->assertOk()
            ->assertSee('return-exchange-panel', false)
            ->assertSee('Available stock')
            ->assertSee('Convert to Refund');

        $this->panel('post', "/returns/{$exchange->id}/approve")->assertOk();
        $this->assertSame(4, $this->stockOf($b), 'replacement reserved on approval');
        $this->page("/returns/{$exchange->id}")->assertOk()->assertSee('Reserved');
        $this->panel('post', "/returns/{$exchange->id}/received")->assertOk();
        $this->panel('post', "/returns/{$exchange->id}/inspect", ['inspection_result' => 'passed'])->assertOk();

        $this->page("/returns/{$exchange->id}")
            ->assertOk()
            ->assertSee('exchange-shipped-modal', false)
            ->assertDontSee('issue-refund-modal', false);
        $this->panel('post', "/returns/{$exchange->id}/issue-refund", ['amount' => 10])->assertStatus(422);
        $this->panel('post', "/returns/{$exchange->id}/exchange-completed")->assertStatus(422);
        $this->panel('post', "/returns/{$exchange->id}/exchange-shipped/validate", [])->assertStatus(422)->assertJsonValidationErrors(['tracking_number']);
        $this->panel('post', "/returns/{$exchange->id}/exchange-shipped/validate", ['tracking_number' => 'TRK-1'])->assertOk();
        $this->panel('post', "/returns/{$exchange->id}/exchange-shipped", ['tracking_number' => 'TRK-998877'])
            ->assertOk()
            ->assertJsonPath('data.returnRecord.status', 'exchange_shipped')
            ->assertJsonPath('data.returnRecord.exchange_tracking_number', 'TRK-998877');
        $this->page("/returns/{$exchange->id}")->assertOk()->assertSee('TRK-998877')->assertSee('Complete Exchange');
        $this->panel('post', "/returns/{$exchange->id}/convert-to-refund")->assertStatus(422);
        $this->panel('post', "/returns/{$exchange->id}/exchange-completed")
            ->assertOk()->assertJsonPath('data.returnRecord.status', 'exchanged');

        // ── Convert an exchange to a refund: stock released, type changes ───
        $convert = $make($fixture['convert'], ['type' => 'exchange', 'replacement_product_variant_id' => $b->id]);
        $this->panel('post', "/returns/{$convert->id}/approve")->assertOk();
        $this->assertSame(3, $this->stockOf($b));
        $this->panel('post', "/returns/{$convert->id}/convert-to-refund")
            ->assertOk()->assertJsonPath('data.returnRecord.type', 'return');
        $this->assertSame(4, $this->stockOf($b), 'reserved replacement released');
        $this->assertSame(ReturnType::Return, $convert->refresh()->type);

        // ── Legacy "refunded" route keeps working ───────────────────────────
        $legacy = $make($fixture['legacy']);
        $this->panel('post', "/returns/{$legacy->id}/approve")->assertOk();
        $this->panel('post', "/returns/{$legacy->id}/refunded/validate", ['refund_amount' => 50])->assertOk();
        $this->panel('post', "/returns/{$legacy->id}/refunded", ['refund_amount' => 50])->assertOk();
        $this->assertSame(ReturnStatus::Refunded, $legacy->refresh()->status);

        // ── Tenant isolation ────────────────────────────────────────────────
        $foreign = ReturnRequest::query()->create([
            'tenant_id' => 'another-tenant',
            'order_number' => 'x',
            'customer_id' => 1,
            'reason' => 'changed_mind',
            'status' => ReturnStatus::Inspected,
        ]);
        $this->page("/returns/{$foreign->id}")->assertNotFound();
        foreach ([
            ['post', 'approve', []],
            ['post', 'inspect', ['inspection_result' => 'passed']],
            ['post', 'issue-refund', ['amount' => 1]],
            ['post', 'exchange-shipped', ['tracking_number' => 'T']],
            ['post', 'exchange-completed', []],
            ['post', 'convert-to-refund', []],
            ['post', 'close', []],
        ] as [$method, $action, $data]) {
            $this->panel($method, "/returns/{$foreign->id}/{$action}", $data)->assertForbidden();
        }
        $this->assertSame(ReturnStatus::Inspected, $foreign->refresh()->status);

        // ── Permission ──────────────────────────────────────────────────────
        $this->tenant->run(function (): void {
            $this->ownerRole->update(['permissions' => array_values(array_diff($this->ownerRole->permissions, ['sales.returns.manage']))]);
        });
        $this->admin->unsetRelation('role');

        foreach (['inspect' => ['inspection_result' => 'passed'], 'issue-refund' => ['amount' => 1], 'exchange-shipped' => ['tracking_number' => 'T'], 'exchange-completed' => [], 'convert-to-refund' => [], 'close' => []] as $action => $data) {
            $this->panel('post', "/returns/{$ret->id}/{$action}", $data)->assertForbidden();

            if (in_array($action, ['inspect', 'issue-refund', 'exchange-shipped'], true)) {
                $this->panel('post', "/returns/{$ret->id}/{$action}/validate", $data)->assertForbidden();
            }
        }
        $this->page("/returns/{$ret->id}")->assertRedirect();

        $this->assertNull(tenant());
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function deliveredOrder(Customer $customer, ProductVariant $variant, int $qty): Order
    {
        $order = $this->createOrder($customer, [[$variant, $qty]], OrderStatus::Delivered, paid: true, stockDeducted: true);
        $order->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);

        return $order;
    }

    private function stockOf(ProductVariant $variant): int
    {
        return (int) $this->tenant->run(fn () => ProductVariant::query()->whereKey($variant->id)->value('stock'));
    }

    /** Panel page (HTML) as the store owner. */
    private function page(string $uri): TestResponse
    {
        try {
            $response = $this->actingAsTenantAdmin()->get($this->tenantUrl('/admin'.$uri));

            return $response;
        } finally {
            tenancy()->end();
        }
    }

    /** Panel JSON call as the store owner (GET data goes in the query string). */
    private function panel(string $method, string $uri, array $data = []): TestResponse
    {
        try {
            return $this->actingAsTenantAdmin()->json(strtoupper($method), $this->tenantUrl('/admin'.$uri), $data);
        } finally {
            tenancy()->end();
        }
    }
}
