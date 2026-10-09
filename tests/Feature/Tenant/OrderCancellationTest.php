<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Http\Controllers\Tenant\PaymentController;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Models\AdminNotification;
use App\Models\Refund;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderActivity;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\TenantNotification;
use App\Services\Admin\OrderFulfillmentService;
use App\Services\Mail\TemplateMailService;
use App\Services\Orders\OrderCancellationService;
use App\Services\Orders\OrderPolicyService;
use App\Services\Tenant\OrderLifecycleService;
use App\Services\Tenant\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Phase 3 cancellation flow end to end: storefront API, tenant panel endpoints, lifecycle /
 * payment integrations and the COD "cash collected" refund rule. Scenarios are consolidated
 * into three methods because each tenant boot is expensive.
 */
class OrderCancellationTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private FakePaymentGateway $gateway;

    /** @var MockInterface&TemplateMailService */
    private $mail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();

        Bus::fake([SyncCentralProductStockToTenantsJob::class]);
        $this->gateway = FakePaymentGateway::install('stripe');
        $this->mail = $this->spy(TemplateMailService::class);
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    #[Test]
    public function customers_cancel_through_the_api(): void
    {
        [$customer, $variant] = $this->tenant->run(fn (): array => [
            $this->createCustomer(),
            $this->createOwnProductVariant(stock: 10, price: 40.0),
        ]);

        // ── Reasons list ─────────────────────────────────────────────────────
        $reasons = $this->api($customer, 'get', '/orders/cancellation-reasons')->assertOk()->json('data');
        $this->assertSame(
            array_map(fn (CancellationReason $r) => $r->value, CancellationReason::forCustomer()),
            array_column($reasons, 'value'),
        );
        $this->assertTrue(collect($reasons)->firstWhere('value', 'other')['requires_note']);

        // ── Pending COD order: no refund, no stock change ────────────────────
        $pending = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 2]], OrderStatus::Pending));

        // Reason validation.
        $this->api($customer, 'post', "/orders/{$pending->uuid}/cancel", [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->api($customer, 'post', "/orders/{$pending->uuid}/cancel", ['reason' => 'out_of_stock'])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->api($customer, 'post', "/orders/{$pending->uuid}/cancel", ['reason' => 'nope'])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->api($customer, 'post', "/orders/{$pending->uuid}/cancel", ['reason' => 'other'])->assertStatus(422)->assertJsonValidationErrors(['note']);
        $this->api($customer, 'post', "/orders/{$pending->uuid}/cancel", ['reason' => 'changed_mind', 'note' => str_repeat('x', 1001)])->assertStatus(422)->assertJsonValidationErrors(['note']);
        $this->assertSame(OrderStatus::Pending, $this->tenant->run(fn () => $pending->fresh()->status));

        $show = $this->api($customer, 'get', "/orders/{$pending->uuid}")->assertOk();
        $show->assertJsonPath('order.can_cancel', true)->assertJsonPath('order.cancellation', null)->assertJsonPath('order.payment_state', 'unpaid');

        $this->api($customer, 'post', "/orders/{$pending->uuid}/cancel", ['reason' => 'other', 'note' => 'Bought it in a shop nearby'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.status', 'cancelled')
            ->assertJsonPath('order.can_cancel', false)
            ->assertJsonPath('order.cancellation.reason', 'other')
            ->assertJsonPath('order.cancellation.reason_label', 'Other')
            ->assertJsonPath('order.cancellation.note', 'Bought it in a shop nearby')
            ->assertJsonPath('order.cancellation.cancelled_by', 'customer')
            ->assertJsonPath('order.cancellation.cancelled_by_label', 'You')
            ->assertJsonPath('order.refunds', []);

        $this->tenant->run(function () use ($pending, $customer, $variant): void {
            $order = $pending->fresh();
            $this->assertSame(OrderStatus::Cancelled, $order->status);
            $this->assertNotNull($order->cancelled_at);
            $this->assertSame(CancellationReason::Other, $order->cancellation_reason);
            $this->assertSame('Bought it in a shop nearby', $order->cancellation_note);
            $this->assertSame(CancellationActor::Customer, $order->cancelled_by_type);
            $this->assertSame($customer->id, $order->cancelled_by_id);
            $this->assertNull($order->stock_restored_at, 'COD stock was never deducted');
            $this->assertSame(10, ProductVariant::query()->find($variant->id)->stock);
            $this->assertSame(0, $order->refundsQuery()->count());
            $this->assertActivity($order, 'Order cancelled', 'Cancelled by Customer. Reason: Other. Bought it in a shop nearby');
            $this->assertSame(1, TenantNotification::query()->where('title', 'Order cancelled')->where('message', 'like', "%{$order->uuid}%")->count());
        });
        $this->assertSame(1, AdminNotification::query()->where('title', 'Order cancelled')->where('message', 'like', "%{$pending->uuid}%")->count());
        $this->mail->shouldHaveReceived('sendTenantOrderCancelled')
            ->withArgs(fn (Order $o) => $o->uuid === $pending->uuid && $o->cancellation_reason === CancellationReason::Other)
            ->once();
        $this->mail->shouldNotHaveReceived('sendTenantRefundProcessed');

        // Double cancel → 422 with the policy message.
        $this->api($customer, 'post', "/orders/{$pending->uuid}/cancel", ['reason' => 'changed_mind'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'already_cancelled')
            ->assertJsonPath('message', 'This order is already cancelled.');

        // ── Paid Processing order: stock restored once, refund auto-completed ─
        $paid = $this->tenant->run(function () use ($customer, $variant): Order {
            $order = $this->createOrder($customer, [[$variant, 3]], OrderStatus::Processing, paid: true, attributes: ['shipping_charge' => 10]);
            $this->assertTrue(app(StockService::class)->decrementForOrder($order));
            $this->assertSame(7, ProductVariant::query()->find($variant->id)->stock);

            return $order;
        });

        $this->api($customer, 'post', "/orders/{$paid->uuid}/cancel", ['reason' => 'found_better_price'])
            ->assertOk()
            ->assertJsonPath('order.status', 'cancelled')
            ->assertJsonPath('order.payment_state', 'refunded')
            ->assertJsonPath('order.refunded_amount', 130)
            ->assertJsonPath('order.refunds.0.status', 'completed')
            ->assertJsonPath('order.refunds.0.method', 'original_payment')
            ->assertJsonPath('order.refunds.0.amount', 130);

        $this->gateway->assertRefunded(130.0, $paid->payment_details['transaction_id']);
        $this->tenant->run(function () use ($paid, $variant): void {
            $order = $paid->fresh()->load('items');
            $this->assertSame(OrderStatus::Cancelled, $order->status, 'a cancelled order stays cancelled');
            $this->assertSame(OrderPaymentStatus::Refunded, $order->paymentState());
            $this->assertSame('130.00', $order->refunded_amount);
            $this->assertNotNull($order->stock_restored_at);
            $this->assertSame(10, ProductVariant::query()->find($variant->id)->stock);
            $this->assertFalse(app(StockService::class)->restoreForOrder($order), 'restored only once');
            $this->assertSame(10, ProductVariant::query()->find($variant->id)->stock);

            $refund = $order->refundsQuery()->sole();
            $this->assertSame(RefundSource::Cancellation, $refund->source);
            $this->assertSame(RefundStatus::Completed, $refund->status);
            $this->assertSame(CancellationActor::Customer, $refund->requested_by_type);
            $this->assertSame(CancellationActor::System, $refund->approved_by_type);
        });
        $this->mail->shouldHaveReceived('sendTenantRefundProcessed')->once();

        // ── auto_refund_on_cancel = false → the refund waits for the vendor ──
        $manual = $this->tenant->run(function () use ($customer, $variant): Order {
            app(OrderPolicyService::class)->update([OrderPolicyService::AUTO_REFUND_ON_CANCEL => false]);

            return $this->createOrder($customer, [[$variant, 1]], OrderStatus::Pending, paid: true);
        });
        $this->api($customer, 'post', "/orders/{$manual->uuid}/cancel", ['reason' => 'duplicate_order'])
            ->assertOk()
            ->assertJsonPath('order.payment_state', 'paid')
            ->assertJsonPath('order.refunds.0.status', 'pending')
            ->assertJsonPath('order.refunds.0.amount', 40);
        $this->assertCount(1, $this->gateway->successfulRefunds(), 'no gateway call while auto refund is off');

        // ── Processing locked by policy for customers ───────────────────────
        $locked = $this->tenant->run(function () use ($customer, $variant): Order {
            app(OrderPolicyService::class)->update([
                OrderPolicyService::AUTO_REFUND_ON_CANCEL => true,
                OrderPolicyService::CANCELLATION_ALLOW_PROCESSING => false,
            ]);

            return $this->createOrder($customer, [[$variant, 1]], OrderStatus::Processing);
        });
        $this->api($customer, 'get', "/orders/{$locked->uuid}")->assertJsonPath('order.can_cancel', false);
        $this->api($customer, 'post', "/orders/{$locked->uuid}/cancel", ['reason' => 'changed_mind'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'processing_locked')
            ->assertJsonPath('message', 'This order is already being prepared and can no longer be cancelled. Please contact the store.');

        // ── Shipped / Delivered / Completed / Refunded → 422 with the right message ─
        $blocked = [
            [OrderStatus::Shipped, 'shipped', "This order has already been shipped. Once it's delivered you can request a return.", true],
            [OrderStatus::Delivered, 'delivered', 'This order was delivered — please request a return instead.', true],
            [OrderStatus::Completed, 'delivered', 'This order was delivered — please request a return instead.', true],
            [OrderStatus::Refunded, 'already_refunded', 'This order has already been refunded.', false],
            [OrderStatus::Rejected, 'already_cancelled', 'This order is already cancelled.', false],
        ];

        foreach ($blocked as [$status, $code, $message, $suggestReturn]) {
            $order = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 1]], $status, paid: true));

            $this->api($customer, 'post', "/orders/{$order->uuid}/cancel", ['reason' => 'changed_mind'])
                ->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('code', $code)
                ->assertJsonPath('message', $message)
                ->assertJsonPath('suggest_return', $suggestReturn);
            $this->assertSame($status, $this->tenant->run(fn () => $order->fresh()->status));
        }

        // Someone else's order is not found.
        $other = $this->tenant->run(fn () => $this->createOrder($this->createCustomer(), [[$variant, 1]]));
        $this->api($customer, 'post', "/orders/{$other->uuid}/cancel", ['reason' => 'changed_mind'])->assertNotFound();

        $this->assertNull(tenant());
    }

    #[Test]
    public function vendors_cancel_and_manage_refunds_in_the_panel(): void
    {
        [$customer, $variant] = $this->tenant->run(fn (): array => [
            $this->createCustomer(),
            $this->createOwnProductVariant(stock: 20, price: 50.0),
        ]);

        // ── Cancel validation (+ the *.validate endpoint) ────────────────────
        $order = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 2]], OrderStatus::Processing, paid: true, stockDeducted: true));

        $this->panel('post', "/orders/{$order->id}/cancel/validate", [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->panel('post', "/orders/{$order->id}/cancel/validate", ['reason' => 'changed_mind'])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->panel('post', "/orders/{$order->id}/cancel/validate", ['reason' => 'other'])->assertStatus(422)->assertJsonValidationErrors(['note']);
        $this->panel('post', "/orders/{$order->id}/cancel/validate", ['reason' => 'out_of_stock'])->assertOk()->assertJson(['valid' => true]);

        // ── Vendor cancels; the gateway declines → failed → retry succeeds ───
        $this->gateway->fail();
        $this->panel('post', "/orders/{$order->id}/cancel", ['reason' => 'out_of_stock', 'note' => 'Supplier ran out'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_value', 'cancelled')
            ->assertJsonPath('data.refunds.0.status', 'failed')
            ->assertJsonPath('data.refunds.0.can_retry', true);

        $refund = $this->tenant->run(function () use ($order): Refund {
            $fresh = $order->fresh();
            $this->assertSame(CancellationActor::Vendor, $fresh->cancelled_by_type);
            $this->assertSame($this->admin->id, $fresh->cancelled_by_id);
            $this->assertSame(CancellationReason::OutOfStock, $fresh->cancellation_reason);
            $this->assertNotNull($fresh->stock_restored_at);
            $this->assertActivity($fresh, 'Order cancelled', 'Cancelled by Store. Reason: Out of stock. Supplier ran out');

            return $fresh->refundsQuery()->sole();
        });
        $this->assertSame(CancellationActor::Vendor, $refund->approved_by_type, 'vendor-requested refunds are approved by the vendor');

        $this->gateway->succeed();
        $this->panel('post', "/refunds/{$refund->id}/retry")
            ->assertOk()
            ->assertJsonPath('data.refund.status', 'completed')
            ->assertJsonPath('data.refund.can_retry', false);
        $this->panel('post', "/refunds/{$refund->id}/retry")->assertStatus(422)->assertJsonPath('message', 'Only failed refunds can be retried.');
        $this->panel('post', "/refunds/{$refund->id}/reject", ['reason' => 'x'])->assertStatus(422);

        // ── Unsupported gateway → failed → completed manually with a reference ─
        $this->gateway->notSupported();
        $order = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 1]], OrderStatus::Pending, paid: true));
        $this->panel('post', "/orders/{$order->id}/cancel", ['reason' => 'pricing_error'])->assertOk()->assertJsonPath('data.refunds.0.status', 'failed');
        $refund = $this->tenant->run(fn () => $order->refundsQuery()->sole());

        $this->panel('post', "/refunds/{$refund->id}/complete/validate", ['reference' => str_repeat('r', 191)])->assertStatus(422)->assertJsonValidationErrors(['reference']);
        $this->panel('post', "/refunds/{$refund->id}/complete/validate", [])->assertOk();
        $this->panel('post', "/refunds/{$refund->id}/complete", ['reference' => 'BANK-42'])
            ->assertOk()
            ->assertJsonPath('data.refund.status', 'completed')
            ->assertJsonPath('data.refund.method', 'manual')
            ->assertJsonPath('data.refund.manual_reference', 'BANK-42');
        $this->assertSame(OrderPaymentStatus::Refunded, $this->tenant->run(fn () => $order->fresh()->load('items')->paymentState()));
        $this->gateway->succeed();

        // ── Panel cancel of a shipped order → policy message ────────────────
        $shipped = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 1]], OrderStatus::Shipped, paid: true));
        $this->panel('post', "/orders/{$shipped->id}/cancel", ['reason' => 'out_of_stock'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'shipped');

        // ── Shipping status "Cancelled" goes through the cancellation service ─
        $viaShipping = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 2]], OrderStatus::Processing, paid: true, stockDeducted: true));
        $this->panel('patch', "/orders/{$viaShipping->id}/shipping-status", ['shipping_status' => OrderShippingStatus::Cancelled->value])
            ->assertOk()
            ->assertJsonPath('data.status_value', 'cancelled');
        $this->tenant->run(function () use ($viaShipping): void {
            $fresh = $viaShipping->fresh()->load('items');
            $this->assertSame(OrderStatus::Cancelled, $fresh->status);
            $this->assertSame(CancellationReason::UnableToFulfil, $fresh->cancellation_reason);
            $this->assertSame(CancellationActor::Vendor, $fresh->cancelled_by_type);
            $this->assertNotNull($fresh->stock_restored_at);
            $this->assertSame(OrderPaymentStatus::Refunded, $fresh->paymentState());
        });
        $this->panel('patch', "/orders/{$shipped->id}/shipping-status", ['shipping_status' => OrderShippingStatus::Cancelled->value])
            ->assertStatus(422)
            ->assertJsonPath('code', 'shipped');

        $refunded = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 1]], OrderStatus::Refunded, paid: true));
        $this->panel('patch', "/orders/{$refunded->id}/shipping-status", ['shipping_status' => OrderShippingStatus::Delivered->value])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cancelled, rejected or refunded orders cannot be moved through shipping states.');

        // ── COD order delivered (cash collected, not flagged paid) → manual refund ─
        $cod = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 2]], OrderStatus::Delivered, attributes: ['shipping_charge' => 10]));
        $this->assertFalse($cod->paid);

        $this->panel('post', "/orders/{$cod->id}/refunds/validate", [])->assertStatus(422)->assertJsonValidationErrors(['amount', 'reason']);
        $this->panel('post', "/orders/{$cod->id}/refunds/validate", ['amount' => 110.01, 'reason' => 'Too much'])->assertStatus(422)->assertJsonValidationErrors(['amount']);
        $this->panel('post', "/orders/{$cod->id}/refunds/validate", ['amount' => 0, 'reason' => 'Zero'])->assertStatus(422)->assertJsonValidationErrors(['amount']);
        $this->panel('post', "/orders/{$cod->id}/refunds/validate", ['amount' => 110, 'reason' => 'All'])->assertOk();

        $response = $this->panel('post', "/orders/{$cod->id}/refunds", ['amount' => 30, 'reason' => 'Scratched box'])
            ->assertOk()
            ->assertJsonPath('data.refund.status', 'pending')
            ->assertJsonPath('data.refund.method', 'manual')
            ->assertJsonPath('data.refund.can_retry', false)
            ->assertJsonPath('data.refundable_amount', 80);
        $codRefundId = $response->json('data.refund.id');

        $this->panel('post', "/refunds/{$codRefundId}/retry")->assertStatus(422)->assertJsonPath('code', 'manual_only');
        $this->panel('post', "/refunds/{$codRefundId}/reject/validate", [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->panel('post', "/refunds/{$codRefundId}/reject", ['reason' => 'Customer kept the item'])
            ->assertOk()
            ->assertJsonPath('data.refund.status', 'rejected')
            ->assertJsonPath('data.refund.failure_reason', 'Customer kept the item');

        $response = $this->panel('post', "/orders/{$cod->id}/refunds", ['amount' => 25, 'reason' => 'Goodwill'])->assertOk();
        $this->panel('post', '/refunds/'.$response->json('data.refund.id').'/complete', [])->assertOk()->assertJsonPath('data.refund.status', 'completed');

        $this->tenant->run(function () use ($cod): void {
            $fresh = $cod->fresh()->load('items');
            $this->assertFalse($fresh->paid, 'the paid flag is never touched for COD refunds');
            $this->assertTrue($fresh->isPaymentCollected());
            $this->assertSame('25.00', $fresh->refunded_amount);
            $this->assertSame(OrderStatus::Delivered, $fresh->status);
            $this->assertSame(OrderPaymentStatus::PartiallyRefunded, $fresh->paymentState());
        });

        // An undelivered COD order has nothing to refund.
        $codPending = $this->tenant->run(fn () => $this->createOrder($customer, [[$variant, 1]], OrderStatus::Shipped));
        $this->panel('post', "/orders/{$codPending->id}/refunds", ['amount' => 5, 'reason' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        // ── Tenant guard + permissions ───────────────────────────────────────
        $foreign = Refund::query()->create([
            'tenant_id' => 'another-tenant',
            'order_number' => 'x',
            'source' => RefundSource::Manual,
            'reason' => 'x',
            'items_amount' => 1,
            'amount' => 1,
            'refund_method' => RefundMethod::Manual,
            'status' => RefundStatus::Pending,
        ]);
        $this->panel('post', "/refunds/{$foreign->id}/reject", ['reason' => 'nope'])->assertForbidden();
        $this->assertSame(RefundStatus::Pending, $foreign->fresh()->status);

        $this->tenant->run(function (): void {
            $this->ownerRole->update(['permissions' => array_values(array_diff($this->ownerRole->permissions, ['sales.orders.manage', 'sales.returns.manage']))]);
        });
        $this->admin->unsetRelation('role');
        $this->panel('post', "/orders/{$codPending->id}/cancel", ['reason' => 'out_of_stock'])->assertForbidden();
        $this->panel('post', "/orders/{$cod->id}/refunds", ['amount' => 1, 'reason' => 'x'])->assertForbidden();
        $this->assertSame(OrderStatus::Shipped, $this->tenant->run(fn () => $codPending->fresh()->status));

        $this->assertNull(tenant());
    }

    #[Test]
    public function payments_and_status_changes_respect_cancellations(): void
    {
        [$customer, $variant, $cancelled] = $this->tenant->run(function (): array {
            $customer = $this->createCustomer();
            $variant = $this->createOwnProductVariant(stock: 10, price: 30.0);
            // Placed for online payment (awaiting the gateway).
            $order = $this->createOrder($customer, [[$variant, 2]], OrderStatus::Pending, attributes: ['payment_method' => 'stripe']);

            return [$customer, $variant, $order];
        });

        $this->tenant->run(function () use ($customer, $variant, $cancelled): void {
            $this->actingAs($customer, 'storefront');
            app(OrderCancellationService::class)
                ->cancel($cancelled, CancellationActor::Customer, $customer->id, CancellationReason::ChangedMind);

            // ── charge() refuses a cancelled order ──────────────────────────
            $response = app(PaymentController::class)->charge(Request::create('/checkout/payment/stripe/'.$cancelled->uuid), 'stripe', $cancelled->uuid);
            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertStringContainsString($cancelled->uuid, $response->getTargetUrl());
            $this->assertSame(
                'This order has been cancelled and can no longer be paid.',
                $response->getSession()->get('errors')->first('payment'),
            );
            $this->assertFalse((bool) $cancelled->fresh()->paid);
            $this->assertFalse(OrderActivity::query()->where('order_id', $cancelled->id)->where('title', 'Payment initiated')->exists());

            // ── A late gateway confirmation → recorded, refunded, still cancelled ─
            app(PaymentController::class)->success(Request::create('/checkout/payment/stripe/success', 'GET', [
                'transaction_id' => 'ch_late_payment',
                'order_id' => $cancelled->uuid,
            ]), 'stripe');

            $fresh = $cancelled->fresh()->load('items');
            $this->assertTrue($fresh->paid);
            $this->assertSame('ch_late_payment', $fresh->payment_details['transaction_id']);
            $this->assertSame(OrderStatus::Cancelled, $fresh->status);
            $this->assertNull($fresh->stock_deducted_at, 'no stock is taken for a cancelled order');
            $this->assertSame(10, ProductVariant::query()->find($variant->id)->stock);
            $this->assertSame(OrderPaymentStatus::Refunded, $fresh->paymentState());
            $this->gateway->assertRefunded(60.0, 'ch_late_payment');
            $this->assertTrue(OrderActivity::query()->where('order_id', $fresh->id)->where('title', 'Payment received after cancellation — refund initiated')->exists());
            $this->assertFalse(OrderActivity::query()->where('order_id', $fresh->id)->where('title', 'Payment confirmed')->exists());

            $refund = $fresh->refundsQuery()->sole();
            $this->assertSame(RefundSource::Cancellation, $refund->source);
            $this->assertSame(CancellationActor::System, $refund->requested_by_type);
            $this->assertSame(RefundStatus::Completed, $refund->status);
            $this->assertSame(1, TenantNotification::query()->where('title', 'Payment received after cancellation')->count());

            // A normal confirmation still works.
            $live = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Pending, attributes: ['payment_method' => 'stripe']);
            app(PaymentController::class)->success(Request::create('/checkout/payment/stripe/success', 'GET', [
                'transaction_id' => 'ch_live',
                'order_id' => $live->uuid,
            ]), 'stripe');
            $live->refresh();
            $this->assertTrue($live->paid);
            $this->assertSame(OrderStatus::Processing, $live->status);
            $this->assertNotNull($live->stock_deducted_at);
            $this->assertSame(9, ProductVariant::query()->find($variant->id)->stock);

            // ── updateOrderStatus(Cancelled) delegates; no fake refund email ─
            $lifecycle = app(OrderLifecycleService::class);
            $lifecycle->updateOrderStatus($live, OrderStatus::Cancelled);
            $live->refresh();
            $this->assertSame(OrderStatus::Cancelled, $live->status);
            $this->assertSame(CancellationReason::UnableToFulfil, $live->cancellation_reason);
            $this->assertSame(CancellationActor::Vendor, $live->cancelled_by_type);
            $this->assertNotNull($live->stock_restored_at);
            $this->assertSame(10, ProductVariant::query()->find($variant->id)->stock);
            $this->assertSame(RefundStatus::Completed, $live->refundsQuery()->sole()->status);
            // Exactly one refund email per completed refund (late payment + this cancellation), none from the status change.
            $this->mail->shouldHaveReceived('sendTenantRefundProcessed')->twice();

            $this->assertThrows(fn () => $lifecycle->updateOrderStatus($live, OrderStatus::Processing), InvalidArgumentException::class);
            $this->assertThrows(
                fn () => $lifecycle->updateOrderStatus($this->createOrder($customer, [[$variant, 1]], OrderStatus::Delivered), OrderStatus::Refunded),
                InvalidArgumentException::class,
            );
        });
        $this->assertNull(tenant());

        // ── Central admin status change → cancellation by the admin ─────────
        $adminOrder = $this->tenant->run(fn () => $this->createOrder(Customer::query()->findOrFail($customer->id), [[$variant, 1]], OrderStatus::Processing));
        app(OrderFulfillmentService::class)->updateOrderStatus((string) $this->tenant->id, $adminOrder->uuid, OrderStatus::Cancelled);
        $this->assertNull(tenant());

        $this->tenant->run(function () use ($adminOrder): void {
            $fresh = $adminOrder->fresh();
            $this->assertSame(OrderStatus::Cancelled, $fresh->status);
            $this->assertSame(CancellationActor::Admin, $fresh->cancelled_by_type);
            $this->assertSame(CancellationReason::UnableToFulfil, $fresh->cancellation_reason);
            $this->assertActivity($fresh, 'Order cancelled', 'Cancelled by Support. Reason: Unable to fulfil the order.');
        });

        // Admins can't cancel a shipped order either (OrderActionException is a RuntimeException the admin UI toasts).
        $shipped = $this->tenant->run(fn () => $this->createOrder(Customer::query()->findOrFail($customer->id), [[$variant, 1]], OrderStatus::Shipped));
        $this->assertThrows(
            fn () => app(OrderFulfillmentService::class)->updateShippingStatus((string) $this->tenant->id, $shipped->uuid, OrderShippingStatus::Cancelled),
            \RuntimeException::class,
            "This order has already been shipped. Once it's delivered you can request a return.",
        );
        $this->assertNull(tenant());
        $this->assertSame(OrderStatus::Shipped, $this->tenant->run(fn () => $shipped->fresh()->status));
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /** Storefront JSON API call as the customer (tenant identified by the bearer token). */
    private function api(Customer $customer, string $method, string $uri, array $data = []): TestResponse
    {
        try {
            return $this->actingAs($customer, 'storefront')
                ->withToken((string) $this->tenant->id)
                ->json(strtoupper($method), '/api/v1'.$uri, $data);
        } finally {
            tenancy()->end();
        }
    }

    /** Tenant panel JSON call as the store owner. */
    private function panel(string $method, string $uri, array $data = []): TestResponse
    {
        try {
            return $this->actingAsTenantAdmin()->json(strtoupper($method), $this->tenantUrl('/admin'.$uri), $data);
        } finally {
            tenancy()->end();
        }
    }

    private function assertActivity(Order $order, string $title, string $description): void
    {
        $this->assertTrue(
            OrderActivity::query()->where('order_id', $order->id)->where('title', $title)->where('description', $description)->exists(),
            "Missing order activity [{$title}] \"{$description}\". Got: ".OrderActivity::query()->where('order_id', $order->id)->pluck('description', 'title')->toJson(),
        );
    }
}
