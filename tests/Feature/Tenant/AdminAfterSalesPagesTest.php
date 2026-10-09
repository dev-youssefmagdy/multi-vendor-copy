<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\ActivationStatus;
use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Livewire\Admin\Order\OrderDetailPage;
use App\Livewire\Admin\Order\OrderReturnDetailPage;
use App\Livewire\Admin\Order\OrderReturnsList;
use App\Livewire\Admin\Order\OrdersList;
use App\Livewire\Admin\Order\ReturnAnalyticsPage;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderActivity;
use App\Models\Tenant\ProductVariant;
use App\Services\Mail\TemplateMailService;
use App\Services\Refunds\RefundService;
use App\Services\ReturnRequestService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Phase 7: central admin UI for cancellations, refunds and returns. The Livewire pages run in the
 * central context against a real tenant (own database) and the fake payment gateway.
 * Consolidated into three methods because each tenant boot is expensive.
 */
class AdminAfterSalesPagesTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private FakePaymentGateway $gateway;

    private AdminUser $central;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();

        Bus::fake([SyncCentralProductStockToTenantsJob::class]);
        $this->gateway = FakePaymentGateway::install('stripe');
        $this->spy(TemplateMailService::class);

        $this->central = $this->centralAdmin(['sales.orders.manage', 'sales.orders.view'], 'Central Boss');
        $this->actingAs($this->central, 'admin');
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    #[Test]
    public function admin_cancels_orders_and_manages_refunds_from_the_order_page(): void
    {
        /** @var array{customer: Customer, variant: ProductVariant} $fx */
        $fx = $this->tenant->run(fn (): array => [
            'customer' => $customer = $this->createCustomer(),
            'variant' => $this->createOwnProductVariant(stock: 8, price: 100.0), // 8 left = 10 minus the 2 deducted below
        ]);
        ['customer' => $customer, 'variant' => $variant] = $fx;

        $paid = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 2]], OrderStatus::Processing, paid: true, stockDeducted: true));
        $shipped = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 1]], OrderStatus::Shipped, paid: true));
        $tenantId = (string) $this->tenant->id;
        $open = fn (Order $order) => Livewire::test(OrderDetailPage::class, ['tenantId' => $tenantId, 'orderNumber' => $order->uuid]);

        // ── Cancel: validation, then the confirm step, then the real cancellation ─────────
        $page = $open($paid)
            ->assertOk()
            ->assertSee('Cancel Order')
            ->assertSee('Tracking Number')
            ->assertSee('Payment State')
            ->assertDontSee('Cancellation')
            ->call('openCancelModal')
            ->assertSet('showCancelModal', true)->assertSet('cancelStep', 1)
            ->assertSee('Unable to fulfil the order')
            ->assertDontSee('Changed my mind')            // customer-only reason
            ->call('continueCancel')->assertHasErrors(['cancelReason'])
            ->set('cancelReason', 'changed_mind')->call('continueCancel')->assertHasErrors(['cancelReason'])
            ->set('cancelReason', 'other')->call('continueCancel')->assertHasErrors(['cancelNote'])->assertSet('cancelStep', 1)
            ->set('cancelNote', 'Fraud check failed')->call('continueCancel')->assertHasNoErrors()->assertSet('cancelStep', 2)
            ->assertSee('Are you sure you want to cancel order')->assertSee('Fraud check failed')
            ->call('backToCancelReason')->assertSet('cancelStep', 1)
            ->set('cancelReason', 'out_of_stock')->set('cancelNote', 'Supplier ran out.')->call('continueCancel')
            ->call('cancelOrder')
            ->assertSet('showCancelModal', false)
            ->assertDispatched('admin-toast', message: 'Order cancelled.', type: 'success')
            ->assertSee('Cancellation')->assertSee('Out of stock')->assertSee('Supplier ran out.')
            ->assertSee('Support (Central Boss)')         // who cancelled it
            ->assertDontSee('Cancel Order')               // policy no longer allows it
            ->assertDontSee('Tracking Number');           // no shipping controls on a cancelled order

        $this->assertNull(tenant(), 'tenant context restored');

        $this->tenant->run(function () use ($paid, $variant): void {
            $order = $paid->fresh();
            $this->assertSame(OrderStatus::Cancelled, $order->status);
            $this->assertSame(CancellationReason::OutOfStock, $order->cancellation_reason);
            $this->assertSame('Supplier ran out.', $order->cancellation_note);
            $this->assertSame(CancellationActor::Admin, $order->cancelled_by_type);
            $this->assertSame($this->central->id, (int) $order->cancelled_by_id);
            $this->assertNotNull($order->stock_restored_at);
            $this->assertSame(10, ProductVariant::query()->find($variant->id)->stock, 'stock restored');
            $this->assertSame(1, OrderActivity::query()->where('order_id', $order->id)->where('title', 'Order cancelled')->count());
        });

        $cancellation = Refund::query()->forOrder($tenantId, $paid->uuid)->sole();
        $this->assertSame(RefundSource::Cancellation, $cancellation->source);
        $this->assertSame(RefundStatus::Completed, $cancellation->status, 'auto refund on cancel');
        $this->assertSame('200.00', $cancellation->amount);
        $this->gateway->assertRefunded(200.0, $paid->payment_details['transaction_id']);
        $page->assertSee($cancellation->reference)->assertSee('Order cancellation')->assertSee('Refunded');

        // ── Policy: shipped orders cannot be cancelled → button hidden, forced call → toast ──
        $open($shipped)
            ->assertDontSee('Cancel Order')->assertSee('Tracking Number')
            ->set('cancelReason', 'out_of_stock')
            ->call('cancelOrder')
            ->assertDispatched('admin-toast', message: "This order has already been shipped. Once it's delivered you can request a return.", type: 'error')
            ->assertSet('showCancelModal', false);
        $this->assertSame(OrderStatus::Shipped, $this->tenant->run(fn () => $shipped->fresh()->status));
        $this->assertSame(0, Refund::query()->forOrder($tenantId, $shipped->uuid)->count());

        // ── Refund actions on a paid, delivered order (grand total 200) ──
        $delivered = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 2]], OrderStatus::Delivered, paid: true));
        $page = $open($delivered)->assertSee('Refund')->assertSee('$200.00');
        $this->assertSame(200.0, $this->refundable($delivered));

        // Manual refund: validation (amount ≤ refundable, reason required) …
        $this->gateway->fail('Card declined by the bank');
        $page->call('openRefundModal')->assertSet('refundAmount', '200.00')
            ->set('refundAmount', '250')->set('refundReason', 'Goodwill')->call('issueRefund')->assertHasErrors(['refundAmount'])
            ->set('refundAmount', '30')->set('refundReason', '')->call('issueRefund')->assertHasErrors(['refundReason'])
            // … a gateway failure leaves a failed refund with the reason …
            ->set('refundReason', 'Goodwill')->call('issueRefund')
            ->assertSet('showRefundModal', false)
            ->assertDispatched('admin-toast', type: 'error')
            ->assertSee('declined')->assertSee('Retry')->assertSee('Mark completed');

        $failed = Refund::query()->forOrder($tenantId, $delivered->uuid)->sole();
        $this->assertSame(RefundStatus::Failed, $failed->status);
        $this->assertSame(RefundSource::Manual, $failed->source);
        $this->assertSame('30.00', $failed->amount);
        $this->assertSame(CancellationActor::Admin, $failed->requested_by_type);
        $this->assertSame($this->central->id, (int) $failed->requested_by_id);
        $this->assertSame(200.0, $this->refundable($delivered), 'a failed refund reserves nothing');

        // … retry once the gateway works: completed.
        $this->gateway->succeed();
        $page->call('retryRefund', $failed->id)->assertDispatched('admin-toast', message: 'Refund completed.', type: 'success');
        $failed->refresh();
        $this->assertSame(RefundStatus::Completed, $failed->status);
        $this->assertSame('Central Boss', $failed->approved_by_name);
        $this->assertSame(CancellationActor::Admin, $failed->approved_by_type);
        $this->assertNotNull($failed->processed_at);
        $this->assertSame(OrderPaymentStatus::PartiallyRefunded, $this->tenant->run(fn () => $delivered->fresh()->paymentState()));
        $page->assertSee('Partially Refunded');

        // Mark completed (manual reference) on a failed refund …
        $this->gateway->fail('Gateway timeout');
        $page->call('openRefundModal')->set('refundAmount', '20')->set('refundReason', 'Late delivery')->call('issueRefund');
        $second = Refund::query()->forOrder($tenantId, $delivered->uuid)->where('amount', 20)->sole();
        $this->assertSame(RefundStatus::Failed, $second->status);

        $page->call('openCompleteRefund', $second->id)->assertSet('completeRefundId', $second->id)->assertSee('External reference')
            ->set('completeReference', str_repeat('x', 191))->call('completeRefund')->assertHasErrors(['completeReference'])
            ->set('completeReference', 'BANK-123')->call('completeRefund')
            ->assertSet('completeRefundId', null)
            ->assertDispatched('admin-toast', message: 'Refund marked as completed.', type: 'success');
        $second->refresh();
        $this->assertSame(RefundStatus::Completed, $second->status);
        $this->assertSame(RefundMethod::Manual, $second->refund_method);
        $this->assertSame('BANK-123', $second->meta['manual_reference']);

        // … reject (reason required).
        $page->call('openRefundModal')->set('refundAmount', '10')->set('refundReason', 'Test')->call('issueRefund');
        $third = Refund::query()->forOrder($tenantId, $delivered->uuid)->where('amount', 10)->sole();
        $page->call('openRejectRefund', $third->id)->set('rejectRefundReason', '')->call('rejectRefund')->assertHasErrors(['rejectRefundReason'])
            ->set('rejectRefundReason', 'Not eligible for a refund.')->call('rejectRefund')
            ->assertSet('rejectRefundId', null)
            ->assertDispatched('admin-toast', message: 'Refund rejected.', type: 'success')
            ->assertSee('Not eligible for a refund.');
        $third->refresh();
        $this->assertSame(RefundStatus::Rejected, $third->status);
        $this->assertSame('Not eligible for a refund.', $third->failure_reason);
        $this->assertSame(150.0, $this->refundable($delivered), '200 − 30 − 20 (the rejected one frees its amount)');

        // A completed refund can't be retried / rejected again, and refunds of other orders are 404.
        $page->call('retryRefund', $failed->id)->assertDispatched('admin-toast', type: 'error');
        $otherRefund = Refund::query()->forOrder($tenantId, $paid->uuid)->sole();
        try {
            $page->call('retryRefund', $otherRefund->id);
            $this->fail('A refund of another order must not be actionable.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(ModelNotFoundException::class, $e);
        }

        // Refund the rest → the order becomes Refunded and loses its shipping controls + refund button.
        $this->gateway->succeed();
        $page = $open($delivered)->call('openRefundModal')->assertSet('refundAmount', '150.00')->set('refundReason', 'Rest')->call('issueRefund');
        $this->tenant->run(function () use ($delivered): void {
            $order = $delivered->fresh();
            $this->assertSame(OrderStatus::Refunded, $order->status);
            $this->assertSame(OrderPaymentStatus::Refunded, $order->paymentState());
        });
        $page->assertDontSee('Tracking Number')->assertDontSeeHtml('id="admin-refund-order"')->assertSee('Refunded');

        // ── Admin orders list: Refunded in the status/payment filters + badges ──
        $list = Livewire::test(OrdersList::class)
            ->assertSeeHtml('<option value="refunded">Refunded</option>')
            ->assertSeeHtml('<option value="partially_refunded">Partially Refunded</option>')
            ->assertSee('Cancelled')->assertSee('Refunded');
        $list->set('statusFilter', 'refunded')->assertSee($delivered->uuid)->assertDontSee($paid->uuid);
        $list->set('statusFilter', '')->set('paymentFilter', 'refunded')->assertSee($delivered->uuid);

        // ── Permission: view-only admins see the order but cannot act ──
        $viewer = $this->centralAdmin(['sales.orders.view'], 'Viewer');
        $this->actingAs($viewer, 'admin');
        $readOnly = $open($delivered)->assertOk()->assertDontSee('Create Return')->assertDontSee('Cancel Order');
        $readOnly->call('openCancelModal')->assertForbidden();
        $open($shipped)->call('cancelOrder')->assertForbidden();
        $open($shipped)->call('updateTrackingNumber')->assertForbidden();
        $open($delivered)->call('issueRefund')->assertForbidden();
        $open($delivered)->call('retryRefund', $failed->id)->assertForbidden();
        $open($delivered)->call('openCompleteRefund', $failed->id)->assertForbidden();
        $open($delivered)->call('rejectRefund')->assertForbidden();
        $this->assertSame(OrderStatus::Shipped, $this->tenant->run(fn () => $shipped->fresh()->status));
    }

    #[Test]
    public function admin_drives_returns_and_exchanges(): void
    {
        $fx = $this->tenant->run(function (): array {
            $customer = $this->createCustomer();
            $a = $this->createOwnProductVariant(stock: 7, price: 100.0);    // 7 left = 10 minus the 3 sold below
            $b = $this->createVariantFor($a->product, stock: 5, price: 100.0); // same-price replacement

            return [$customer, $a, $b, $this->deliveredOrder($customer, $a, 3)];
        });
        [$customer, $a, $b, $order] = $fx;

        $service = app(ReturnRequestService::class);
        $tenantId = (string) $this->tenant->id;
        $create = fn (array $extra = []) => $service->create(array_merge([
            'tenant_id' => $tenantId,
            'order_number' => $order->uuid,
            'customer_id' => $customer->id,
            'order_item_id' => $order->items->first()->id,
            'quantity' => 1,
            'type' => 'return',
            'return_method' => 'ship_back',
            'reason' => 'changed_mind',
            'customer_note' => 'Does not fit.',
        ], $extra));
        $open = fn (ReturnRequest $request) => Livewire::test(OrderReturnDetailPage::class, ['id' => $request->id]);
        $stock = fn (ProductVariant $variant) => (int) $this->tenant->run(fn () => ProductVariant::query()->whereKey($variant->id)->value('stock'));
        $buttons = fn ($page, array $present) => collect(['approve', 'reject', 'request_info', 'forward_to_merchant', 'mark_received', 'inspect', 'issue_refund', 'mark_exchange_shipped', 'mark_exchange_completed', 'convert_to_refund', 'close'])
            ->each(fn (string $action) => in_array($action, $present, true)
                ? $page->assertSeeHtml('data-action="'.$action.'"')
                : $page->assertDontSeeHtml('data-action="'.$action.'"'));

        // ── Return: pending → forwarded → approved → received → inspected → refunded → closed ──
        $return = $create();
        $page = $open($return)->assertOk()
            ->assertSee('Return Method')->assertSee('Ship it back myself')->assertSee('Does not fit.')->assertSee('Quantity');
        $buttons($page, ['approve', 'reject', 'request_info', 'forward_to_merchant']);

        $page->call('openInfoModal')->call('requestMoreInfo')->assertHasErrors(['infoMessage'])
            ->set('infoMessage', 'Send a photo please.')->call('requestMoreInfo')
            ->assertDispatched('admin-toast', message: 'Requested more information from the customer.', type: 'success');
        $this->assertSame(ReturnStatus::AwaitingInfo, $return->refresh()->status);
        $buttons($page, ['approve', 'reject', 'forward_to_merchant']);

        // Customer replies (pending again) → admin forwards to the merchant.
        $service->customerReply($return, $customer->id, 'Photo attached.');
        $page = $open($return);
        $page->call('forwardToMerchant');
        $return->refresh();
        $this->assertSame(ReturnStatus::AwaitingMerchantReview, $return->status);
        $this->assertNotNull($return->forwarded_at);
        $this->assertSame(CancellationActor::Admin, $return->reviewed_by_type);
        $page->assertSee('Forwarded to merchant');
        $buttons($page, ['approve', 'reject', 'request_info']);

        $page->call('openApproveModal')->call('approve')
            ->assertDispatched('admin-toast', message: 'Return request approved.', type: 'success')
            ->assertSee('Reviewed by')->assertSee('Central Boss');
        $this->assertSame(ReturnStatus::Approved, $return->refresh()->status);
        $this->assertSame($this->central->id, (int) $return->reviewed_by_admin_id);
        $buttons($page, ['reject', 'mark_received']);

        $page->call('markItemReceived');
        $this->assertSame(ReturnStatus::ItemReceived, $return->refresh()->status);
        $buttons($page, ['inspect']);

        $page->call('openInspectModal')->assertSet('restock', true)   // the store's restock_returned_items default
            ->call('inspect')->assertHasErrors(['inspectionResult'])
            ->set('inspectionResult', 'passed')->set('inspectionNotes', 'Like new')->call('inspect')
            ->assertDispatched('admin-toast', message: 'Inspection recorded.', type: 'success')
            ->assertSee('Like new')->assertSee('Passed');
        $return->refresh();
        $this->assertSame(ReturnStatus::Inspected, $return->status);
        $this->assertNotNull($return->restocked_at);
        $this->assertSame(8, $stock($a), 'returned unit restocked');
        $page->assertSee('Restocked on');
        $buttons($page, ['reject', 'issue_refund']);

        // Issue refund: prefilled with the calculator max, editable down only.
        $page->call('openRefundModal')->assertSet('refundAmount', '100.00')->assertSee('Maximum refund')
            ->set('refundAmount', '150')->call('issueRefund')->assertHasErrors(['refundAmount'])
            ->set('refundAmount', '0')->call('issueRefund')->assertHasErrors(['refundAmount'])
            ->set('refundAmount', '60')->call('issueRefund')
            ->assertSet('showRefundModal', false)
            ->assertDispatched('admin-toast', message: 'Refund completed.', type: 'success');
        $refund = Refund::query()->where('return_request_id', $return->id)->sole();
        $this->assertSame(RefundSource::Return, $refund->source);
        $this->assertSame(RefundStatus::Completed, $refund->status);
        $this->assertSame('60.00', $refund->amount);
        $this->assertSame('Central Boss', $refund->approved_by_name);
        $this->gateway->assertRefunded(60.0);
        $return->refresh();
        $this->assertSame(ReturnStatus::Refunded, $return->status);
        $page->assertSee($refund->reference)->assertSee('Return');
        $buttons($page, ['close']);

        $page->call('close')->assertDispatched('admin-toast', message: 'Return request closed.', type: 'success');
        $this->assertSame(ReturnStatus::Closed, $return->refresh()->status);
        $buttons($page, []);
        $this->assertNull(tenant());

        // ── Exchange: approve reserves the replacement, then shipped → completed ──
        $exchange = $create(['type' => 'exchange', 'replacement_product_variant_id' => $b->id]);
        $page = $open($exchange)->assertSee('Exchange')->assertSee('Available stock');
        $page->call('openApproveModal')->assertSee('reserves the replacement stock')->call('approve');
        $this->assertSame(4, $stock($b), 'replacement reserved on approval');
        $page->call('markItemReceived')->call('openInspectModal')->set('inspectionResult', 'passed')->call('inspect');
        $this->assertSame(ReturnStatus::Inspected, $exchange->refresh()->status);
        $buttons($page, ['reject', 'mark_exchange_shipped', 'convert_to_refund']);

        $page->call('openShipModal')->call('markExchangeShipped')->assertHasErrors(['trackingNumber'])
            ->set('trackingNumber', 'TRK-777')->call('markExchangeShipped')
            ->assertDispatched('admin-toast', message: 'Replacement marked as shipped.', type: 'success')
            ->assertSee('TRK-777')->assertSee('Reserved');
        $this->assertSame(ReturnStatus::ExchangeShipped, $exchange->refresh()->status);
        $buttons($page, ['mark_exchange_completed']);

        $page->call('markExchangeCompleted')->assertDispatched('admin-toast', message: 'Exchange completed.', type: 'success');
        $this->assertSame(ReturnStatus::Exchanged, $exchange->refresh()->status);
        $buttons($page, ['close']);

        // ── Exchange the store cannot fulfil → convert to refund releases the reserved stock ──
        $third = $create(['type' => 'exchange', 'replacement_product_variant_id' => $b->id, 'quantity' => 1]);
        $page = $open($third);
        $page->call('openApproveModal')->call('approve');
        $this->assertSame(3, $stock($b));
        $page->call('convertToRefund')->assertDispatched('admin-toast', message: 'Exchange converted to a refund.', type: 'success');
        $this->assertSame(ReturnType::Return, $third->refresh()->type);
        $this->assertSame(4, $stock($b), 'reserved replacement released');

        // ── Rule violations become error toasts (the order has no units left to return) ──
        $page->call('markExchangeCompleted')->assertDispatched('admin-toast', type: 'error');

        // ── Reject needs a reason; view-only admins cannot even open the page ──
        $fresh = $this->tenant->run(fn () => $this->deliveredOrder($customer, $a, 1));
        $rejectable = $service->create([
            'tenant_id' => $tenantId, 'order_number' => $fresh->uuid, 'customer_id' => $customer->id,
            'order_item_id' => $fresh->items->first()->id, 'quantity' => 1, 'type' => 'return',
            'return_method' => 'drop_off', 'reason' => 'changed_mind',
        ]);
        $open($rejectable)->call('openRejectModal')->call('reject')->assertHasErrors(['rejectReason'])
            ->set('rejectReason', 'Outside the policy.')->call('reject')
            ->assertDispatched('admin-toast', message: 'Return request rejected.', type: 'success');
        $this->assertSame(ReturnStatus::Rejected, $rejectable->refresh()->status);

        $this->actingAs($this->centralAdmin(['sales.orders.view'], 'Viewer'), 'admin');
        Livewire::test(OrderReturnDetailPage::class, ['id' => $return->id])->assertForbidden();

        // ── Lists render the new fields and statuses ──
        $this->actingAs($this->central, 'admin');
        Livewire::test(OrderReturnsList::class)
            ->assertSee('Qty')->assertSee('Type')->assertSee('Exchange')
            ->assertSeeHtml('<option value="inspected">Inspected</option>')
            ->assertSeeHtml('<option value="exchange_shipped">Replacement Shipped</option>')
            ->assertSeeHtml('<option value="cancelled">Withdrawn</option>')
            ->set('statusFilter', 'exchanged')->assertSee($exchange->order_number);
        Livewire::test(ReturnAnalyticsPage::class)->assertOk()->assertSee('Approval Rate');
        $this->assertNull(tenant(), 'the analytics page does not leave a tenant initialized');
    }

    /** @param list<string> $permissions */
    private function centralAdmin(array $permissions, string $name): AdminUser
    {
        $role = AdminRole::query()->create([
            'name' => $name.' role '.uniqid(),
            'permissions' => $permissions,
            'permissions_count' => count($permissions),
        ]);

        return AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => $name,
            'email' => uniqid('central').'@example.com',
            'password' => Hash::make('password12345'),
            'status' => ActivationStatus::Active,
        ]);
    }

    /** The return window counts from the delivery activity. */
    private function deliveredOrder(Customer $customer, ProductVariant $variant, int $qty): Order
    {
        $order = $this->createOrder($customer, [[$variant, $qty]], OrderStatus::Delivered, paid: true, stockDeducted: true);
        $order->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);

        return $order;
    }

    private function refundable(Order $order): float
    {
        return app(RefundService::class)->refundableAmount($order, $this->tenant);
    }
}
