<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Exceptions\RefundException;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Models\AdminNotification;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestNote;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderActivity;
use App\Models\Tenant\TenantNotification;
use App\Repositories\OrderRepository;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Admin\TenantAdminAggregateService;
use App\Services\Admin\TenantLedgerService;
use App\Services\Mail\TemplateMailService;
use App\Services\Refunds\RefundActor;
use App\Services\Refunds\RefundService;
use App\Support\OrderProfitCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Phase 2 refund engine against real services, with the fake payment gateway.
 * Scenarios are consolidated into two methods (tenant context / central context)
 * because each tenant boot is expensive.
 */
class RefundServiceTest extends TestCase
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
    public function refund_lifecycle_inside_the_tenant_context(): void
    {
        $this->tenant->run(function (): void {
            $service = app(RefundService::class);
            $customer = $this->createCustomer();
            $variant = $this->createOwnProductVariant(stock: 50, price: 40.0);
            $vendor = RefundActor::vendor($this->admin->id);

            // ── Full refund → order Refunded ─────────────────────────────────
            $order = $this->paidOrder($customer, $variant); // 2 × 40 + 20 shipping = 100
            $this->assertSame(100.0, $order->grand_total);

            $refund = $service->create($order, RefundSource::Manual, 100, 'Goodwill refund', $vendor);

            $this->assertSame(RefundStatus::Pending, $refund->status);
            $this->assertSame(RefundMethod::OriginalPayment, $refund->refund_method);
            $this->assertSame('stripe', $refund->gateway);
            $this->assertSame($order->payment_details['transaction_id'], $refund->original_transaction_id);
            $this->assertSame('stripe', $refund->payment_method);
            $this->assertSame('100.00', $refund->amount);
            $this->assertSame('100.00', $refund->items_amount);
            $this->assertSame('USD', $refund->currency);
            $this->assertSame((string) $this->tenant->id, $refund->tenant_id);
            $this->assertSame(CancellationActor::Vendor, $refund->requested_by_type);
            $this->assertSame($this->admin->id, $refund->requested_by_id);
            $this->assertSame(CancellationActor::Vendor, $refund->approved_by_type, 'staff-created refunds are approved at creation');
            $this->assertSame('Panel Test Admin', $refund->approved_by_name);
            $this->assertNotNull($refund->requested_at);
            $this->assertNotNull($refund->approved_at);
            $this->assertNull($refund->processed_at);

            $refund = $service->execute($refund, $vendor);

            $this->assertSame(RefundStatus::Completed, $refund->status);
            $this->assertStringStartsWith('re_fake_', (string) $refund->gateway_refund_id);
            $this->assertNotNull($refund->processed_at);
            $this->assertNull($refund->failure_reason);
            $this->gateway->assertRefunded(100.0, $order->payment_details['transaction_id']);

            $order->refresh()->load('items');
            $this->assertSame(OrderStatus::Refunded, $order->status);
            $this->assertSame('100.00', $order->refunded_amount);
            $this->assertNotNull($order->refunded_at);
            $this->assertSame(OrderPaymentStatus::Refunded, $order->paymentState());
            $this->assertTrue(OrderProfitCalculator::isFinanciallyVoid($order));
            $this->assertSame(0.0, OrderProfitCalculator::effectiveTenantProfitForOrder($order));
            $this->assertActivity($order, 'Refund requested', $refund->reference);
            $this->assertActivity($order, 'Refund completed', $refund->reference);

            $this->mail->shouldHaveReceived('sendTenantRefundProcessed')
                ->withArgs(fn (Order $o, ?Refund $r) => $o->uuid === $order->uuid && $r?->reference === $refund->reference)
                ->once();
            $this->assertSame(1, TenantNotification::query()->where('type', 'refund')->where('title', 'Refund completed')->count());
            $this->assertSame(1, TenantNotification::query()->where('type', 'refund')->where('title', 'Refund requested')->count());

            $this->assertRefundError(fn () => $service->create($order, RefundSource::Manual, 1, 'More', $vendor), RefundException::ORDER_REFUNDED);
            $this->assertRefundError(fn () => $service->execute($refund), RefundException::INVALID_STATE);

            // ── Partial refunds + no double refund ───────────────────────────
            $order = $this->paidOrder($customer, $variant);

            $first = $service->create($order, RefundSource::Manual, 30, 'Late delivery', $vendor);
            $this->assertRefundError(fn () => $service->create($order, RefundSource::Manual, 70.01, 'Too much', $vendor), RefundException::EXCEEDS_REFUNDABLE);
            $this->assertSame(70.0, $service->refundableAmount($order));

            $service->execute($first);
            $order->refresh()->load('items');
            $this->assertSame(OrderStatus::Processing, $order->status);
            $this->assertSame('30.00', $order->refunded_amount);
            $this->assertNull($order->refunded_at);
            $this->assertSame(OrderPaymentStatus::PartiallyRefunded, $order->paymentState());
            $this->assertSame(70.0, OrderProfitCalculator::netOrderTotal($order));

            $second = $service->create($order, RefundSource::Manual, 70, 'Rest', $vendor);
            $this->assertSame(0.0, $service->refundableAmount($order));
            $this->assertRefundError(fn () => $service->create($order, RefundSource::Manual, 0.01, 'Even more', $vendor), RefundException::EXCEEDS_REFUNDABLE);
            $this->assertRefundError(fn () => $service->create($order, RefundSource::Manual, 0, 'Zero', $vendor), RefundException::INVALID_AMOUNT);

            $service->execute($second);
            $order->refresh();
            $this->assertSame(OrderStatus::Refunded, $order->status);
            $this->assertSame('100.00', $order->refunded_amount);
            $this->assertSame(2, $order->refundsQuery()->where('status', RefundStatus::Completed->value)->count());

            // ── Gateway failure → failed (safe reason) → retry succeeds ──────
            $order = $this->paidOrder($customer, $variant);
            $this->gateway->fail('sk_live_SECRET invalid api key');

            $refund = $service->create($order, RefundSource::Manual, 40, 'Damaged box', RefundActor::customer($customer->id));
            $this->assertNull($refund->approved_at, 'customer-requested refunds await approval');
            $this->assertSame(CancellationActor::Customer, $refund->requested_by_type);
            $this->assertSame($customer->id, $refund->requested_by_id);

            $refund = $service->execute($refund); // system (auto) execution
            $this->assertSame(RefundStatus::Failed, $refund->status);
            $this->assertNotNull($refund->processed_at);
            $this->assertStringNotContainsString('SECRET', (string) $refund->failure_reason);
            $this->assertStringContainsString('declined', (string) $refund->failure_reason);
            $this->assertSame(CancellationActor::System, $refund->approved_by_type);
            $this->assertSame('System', $refund->approved_by_name);
            $this->assertSame('0.00', $order->fresh()->refunded_amount);
            $this->assertActivity($order, 'Refund failed', $refund->reference);
            $this->assertSame(1, TenantNotification::query()->where('title', 'Refund failed — action needed')->count());

            $this->assertRefundError(fn () => $service->execute($refund), RefundException::INVALID_STATE);

            $this->gateway->succeed();
            $refund = $service->retry($refund, $vendor);
            $this->assertSame(RefundStatus::Completed, $refund->status);
            $this->assertNull($refund->failure_reason);
            $this->assertSame(CancellationActor::System, $refund->approved_by_type, 'the first approver is kept');
            $this->assertCount(2, $refund->meta['attempts']);
            $this->assertSame('40.00', $order->fresh()->refunded_amount);
            $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
            $this->assertRefundError(fn () => $service->retry($refund, $vendor), RefundException::INVALID_STATE);

            // ── Gateway can't refund → manual completion ────────────────────
            $this->gateway->notSupported();
            $refund = $service->execute($service->create($order, RefundSource::Manual, 10, 'Price match', $vendor));
            $this->assertSame(RefundStatus::Failed, $refund->status);
            $this->assertStringContainsString('does not support automatic refunds', (string) $refund->failure_reason);

            $refund = $service->markCompletedManually($refund, $vendor, null, 'BANK-TRX-123');
            $this->assertSame(RefundStatus::Completed, $refund->status);
            $this->assertSame(RefundMethod::Manual, $refund->refund_method);
            $this->assertSame('BANK-TRX-123', $refund->meta['manual_reference']);
            $this->assertSame('vendor', $refund->meta['completed_by']['type']);
            $this->assertSame('50.00', $order->fresh()->refunded_amount);
            $this->assertRefundError(fn () => $service->markCompletedManually($refund, $vendor), RefundException::INVALID_STATE);
            $this->gateway->succeed();

            // ── Offline payment (COD) → manual method; reject ───────────────
            $cod = $this->paidOrder($customer, $variant, ['payment_method' => 'cod', 'payment_details' => null]);
            $refund = $service->create($cod, RefundSource::Manual, 25, 'Wrong colour', $vendor);
            $this->assertSame(RefundMethod::Manual, $refund->refund_method);
            $this->assertNull($refund->original_transaction_id);
            $this->assertRefundError(fn () => $service->execute($refund), RefundException::MANUAL_ONLY);

            $this->assertRefundError(fn () => $service->reject($refund, $vendor, '   '), RefundException::INVALID_STATE);
            $refund = $service->reject($refund, $vendor, 'The customer kept the item.');
            $this->assertSame(RefundStatus::Rejected, $refund->status);
            $this->assertSame('The customer kept the item.', $refund->failure_reason);
            $this->assertSame('vendor', $refund->meta['rejection']['type']);
            $this->assertSame('Panel Test Admin', $refund->meta['rejection']['name']);
            $this->assertActivity($cod, 'Refund rejected', $refund->reference);
            $this->assertRefundError(fn () => $service->reject($refund, $vendor, 'again'), RefundException::INVALID_STATE);
            $this->assertRefundError(fn () => $service->markCompletedManually($refund, $vendor), RefundException::INVALID_STATE);
            $this->assertSame(100.0, $service->refundableAmount($cod), 'a rejected refund frees the amount');

            // Unsupported gateway → created as manual straight away.
            $this->gateway->advertisesRefunds = false;
            $this->assertSame(RefundMethod::Manual, $service->create($cod, RefundSource::Manual, 5, 'x', $vendor)->refund_method);
            $this->gateway->advertisesRefunds = true;

            // ── Cancelled order stays Cancelled; the cancellation refund ─────
            $cancelled = $this->paidOrder($customer, $variant, [
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => CancellationReason::ChangedMind,
                'cancellation_note' => 'Bought it elsewhere',
                'cancelled_by_type' => CancellationActor::Customer,
                'cancelled_by_id' => $customer->id,
            ]);

            $refund = $service->requestForCancellation($cancelled, CancellationActor::Customer, $customer->id);
            $this->assertSame(RefundSource::Cancellation, $refund->source);
            $this->assertSame('100.00', $refund->amount);
            $this->assertSame('80.00', $refund->items_amount);
            $this->assertSame('20.00', $refund->shipping_amount);
            $this->assertSame('0.00', $refund->return_fee);
            $this->assertSame('Changed my mind: Bought it elsewhere', $refund->reason);
            $this->assertNull($refund->approved_at);
            $this->assertNull($service->requestForCancellation($cancelled, CancellationActor::Customer, $customer->id), 'nothing left once fully reserved');

            $refund = $service->execute($refund);
            $cancelled->refresh();
            $this->assertSame(OrderStatus::Cancelled, $cancelled->status);
            $this->assertNotNull($cancelled->refunded_at);
            $this->assertSame(OrderPaymentStatus::Refunded, $cancelled->load('items')->paymentState());
            $this->assertSame(CancellationActor::System, $refund->approved_by_type);

            // ── Unpaid orders can't be refunded ──────────────────────────────
            $unpaid = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Pending, paid: false);
            $this->assertRefundError(fn () => $service->create($unpaid, RefundSource::Manual, 10, 'x', $vendor), RefundException::NOT_PAID);
            $this->assertRefundError(fn () => $service->requestForCancellation($unpaid, CancellationActor::Vendor, $this->admin->id), RefundException::NOT_PAID);
            $this->assertSame(0.0, $service->refundableAmount($unpaid));
            $this->assertSame(0, $unpaid->refundsQuery()->count());
        });

        $this->assertNull(tenant());
        $this->assertGreaterThan(0, AdminNotification::query()->where('type', 'refund')->where('title', 'Refund completed')->count());
    }

    #[Test]
    public function refunds_run_from_the_central_context_and_complete_return_requests(): void
    {
        [$order, $customer, $variant] = $this->tenant->run(function (): array {
            $customer = $this->createCustomer();
            $variant = $this->createOwnProductVariant(stock: 50, price: 40.0);

            return [$this->paidOrder($customer, $variant, ['status' => OrderStatus::Delivered]), $customer, $variant];
        });

        $this->assertNull(tenant());
        $service = app(RefundService::class);
        $admin = RefundActor::admin(null, 'Platform Admin');

        // ── Create + execute an admin refund from the central context ───────
        $refund = $service->create($order, RefundSource::Manual, 15, 'Support goodwill', $admin, tenant: $this->tenant);
        $this->assertNull(tenant(), 'context restored after create');
        $this->assertSame(CancellationActor::Admin, $refund->approved_by_type);
        $this->assertSame('Platform Admin', $refund->approved_by_name);

        $refund = $service->execute($refund, $admin);
        $this->assertNull(tenant(), 'context restored after execute');
        $this->assertSame(RefundStatus::Completed, $refund->status);

        // Errors from inside the tenant still restore the central context.
        $this->assertRefundError(fn () => $service->create($order, RefundSource::Manual, 500, 'x', $admin, tenant: $this->tenant), RefundException::EXCEEDS_REFUNDABLE);
        $this->assertNull(tenant(), 'context restored after an exception');

        // ── Return request → calculated refund → request Refunded ─────────
        $item = $order->items->first();
        $returnRequest = ReturnRequest::create([
            'tenant_id' => $this->tenant->id,
            'order_number' => $order->uuid,
            'customer_id' => $customer->id,
            'order_item_id' => $item->id,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => 1,
            'reason' => ReturnReason::ChangedMind,
            'status' => ReturnStatus::Inspected,
        ]);

        $calculated = $service->calculateForReturn($returnRequest);
        $this->assertNull(tenant());
        $this->assertSame(40.0, $calculated->itemsAmount);
        $this->assertSame(0.0, $calculated->shippingAmount);
        $this->assertSame(40.0, $calculated->max);

        $this->assertRefundError(fn () => $service->requestForReturn($returnRequest, 40.01, $admin), RefundException::EXCEEDS_CALCULATED);

        $refund = $service->requestForReturn($returnRequest, 35, $admin);
        $this->assertSame(RefundSource::Return, $refund->source);
        $this->assertSame($returnRequest->id, $refund->return_request_id);
        $this->assertSame('35.00', $refund->amount);
        $this->assertSame('35.00', $refund->items_amount);
        $this->assertSame(ReturnReason::ChangedMind->label(), $refund->reason);
        $this->assertTrue($refund->meta['adjusted_by_reviewer']);
        $this->assertRefundError(fn () => $service->requestForReturn($returnRequest, 1, $admin), RefundException::ALREADY_REQUESTED);

        $service->execute($refund, $admin);
        $this->assertNull(tenant());

        $returnRequest->refresh();
        $this->assertSame(ReturnStatus::Refunded, $returnRequest->status);
        $this->assertSame('35.00', $returnRequest->refund_amount);
        $note = ReturnRequestNote::query()->where('return_request_id', $returnRequest->id)->latest('id')->first();
        $this->assertSame(ReturnRequestNote::AUTHOR_SYSTEM, $note->author_type);
        $this->assertTrue($note->customer_visible);
        $this->assertStringContainsString($refund->reference, $note->note);

        // A request that isn't at a refundable step keeps its status (guarded by the state machine).
        $pending = ReturnRequest::create([
            'tenant_id' => $this->tenant->id,
            'order_number' => $order->uuid,
            'customer_id' => $customer->id,
            'order_item_id' => $item->id,
            'quantity' => 1,
            'reason' => ReturnReason::Defective,
            'status' => ReturnStatus::Pending,
        ]);
        $service->execute($service->requestForReturn($pending, 10, $admin), $admin);
        $this->assertSame(ReturnStatus::Pending, $pending->fresh()->status);
        $this->assertSame('10.00', $pending->fresh()->refund_amount);

        // ── Order state as seen from the tenant ─────────────────────────────
        $this->tenant->run(function () use ($order): void {
            $fresh = Order::query()->with('items')->findOrFail($order->id);
            $this->assertSame('60.00', $fresh->refunded_amount);
            $this->assertSame(OrderStatus::Delivered, $fresh->status);
            $this->assertSame(OrderPaymentStatus::PartiallyRefunded, $fresh->paymentState());
            $this->assertSame(40.0, OrderProfitCalculator::netOrderTotal($fresh));
            $this->assertSame(3, OrderActivity::query()->where('order_id', $fresh->id)->where('title', 'Refund completed')->count());
        });

        // ── requestForCancellation from the central context ────────────────
        $cancelled = $this->tenant->run(fn () => $this->paidOrder(Customer::query()->findOrFail($customer->id), $variant, [
            'status' => OrderStatus::Cancelled,
            'cancellation_reason' => CancellationReason::OutOfStock,
        ]));
        $refund = $service->requestForCancellation($cancelled, CancellationActor::Admin, null, $this->tenant);
        $this->assertNull(tenant());
        $this->assertSame('Out of stock', $refund->reason);
        $this->assertSame(CancellationActor::Admin, $refund->approved_by_type);
        $this->assertSame('100.00', $service->execute($refund)->amount);
        $this->assertSame(OrderStatus::Cancelled, $this->tenant->run(fn () => $cancelled->fresh()->status));

        $this->assertGreaterThan(0, AdminNotification::query()->where('type', 'refund')->count());
        $this->assertNull(tenant());

        // ── Financial reports: cancelled excluded, refunds subtracted ──────
        // Orders: delivered 100 with 60 refunded (net 40) + cancelled 100 fully refunded (net 0).
        $ledger = app(TenantLedgerService::class)->find((string) $this->tenant->id);
        $this->assertSame(40.0, $ledger->gross_sales);
        $this->assertSame(40.0, $ledger->collected_sales);
        $this->assertSame(160.0, $ledger->refunded_total);
        $this->assertSame(1, $ledger->paid_orders_count);
        $this->assertSame(40.0, $ledger->vendor_profit_total);
        $this->assertNull(tenant());

        $aggregated = app(TenantAdminAggregateService::class)->orders()->keyBy('order_number');
        $this->assertSame(OrderPaymentStatus::PartiallyRefunded, $aggregated[$order->uuid]->payment_status);
        $this->assertSame(40.0, $aggregated[$order->uuid]->net_amount);
        $this->assertSame(OrderPaymentStatus::Refunded, $aggregated[$cancelled->uuid]->payment_status);
        $this->assertSame(0.0, $aggregated[$cancelled->uuid]->net_amount);

        $summary = app(OrderRepository::class)->reportSummary();
        $this->assertSame(40.0, $summary['gross_revenue']);
        $this->assertSame(40.0, $summary['collected_revenue']);
        $this->assertSame(1, $summary['paid_orders']);
        $this->assertSame(1, $summary['cancelled_orders']);
        $this->assertSame(160.0, $summary['refunded_amount']);
        $this->assertNotEmpty(app(OrderRepository::class)->reportOverview()['cards']);

        $this->tenant->run(function (): void {
            $repository = app(TenantPanelRepository::class);
            $this->assertSame(40.0, $repository->orderStats()['collected']);
            $this->assertSame(40.0, $repository->dashboardStats()['revenue']);
            $this->assertSame(40.0, $repository->billingStats()['collected']);
        });
    }

    /** 2 × 40 + 20 shipping = 100, paid by "stripe" (faked). */
    private function paidOrder(Customer $customer, $variant, array $attributes = []): Order
    {
        $status = $attributes['status'] ?? OrderStatus::Processing;
        unset($attributes['status']);

        return $this->createOrder($customer, [[$variant, 2]], $status, paid: true, attributes: array_merge([
            'shipping_charge' => 20,
        ], $attributes), stockDeducted: true);
    }

    private function assertActivity(Order $order, string $title, string $reference): void
    {
        $this->assertTrue(
            OrderActivity::query()
                ->where('order_id', $order->id)
                ->where('title', $title)
                ->where('description', 'like', "%{$reference}%")
                ->exists(),
            "Missing order activity [{$title}] for {$reference}.",
        );
    }

    private function assertRefundError(\Closure $callback, string $reason): void
    {
        try {
            $callback();
        } catch (RefundException $e) {
            $this->assertSame($reason, $e->reason, $e->getMessage());

            return;
        }

        $this->fail("Expected a RefundException [{$reason}].");
    }
}
