<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\InspectionResult;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Enums\ReturnMethod;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Enums\Tenant\SettingType;
use App\Exceptions\OrderActionException;
use App\Exceptions\RefundException;
use App\Exceptions\ReturnActionException;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Models\AdminNotification;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderActivity;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Setting;
use App\Models\Tenant\TenantNotification;
use App\Services\Mail\TemplateMailService;
use App\Services\Orders\OrderCancellationPolicy;
use App\Services\Orders\OrderCancellationService;
use App\Services\Orders\OrderPolicyService;
use App\Services\Refunds\RefundActor;
use App\Services\Refunds\RefundService;
use App\Services\ReturnRequestService;
use App\Services\Tenant\StockService;
use App\Support\Tenant\Storefront\OrderAfterSalesPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Phase 8: the whole cancel / return / exchange / refund requirement end to end against the real
 * services and the fake payment gateway. Scenarios are consolidated into four methods because
 * every tenant bootstrap is expensive:
 *
 *   1. cancellation lifecycle + the status-vs-eligibility table   (requirement a, b, c, d, h)
 *   2. partial → full return of a delivered order                 (requirement e)
 *   3. exchange: happy path, no stock, price mismatch             (requirement f)
 *   4. gateway failure → retry; COD manual completion             (requirement g)
 */
class OrderAfterSalesLifecycleTest extends TestCase
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
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    // ─── 1. Cancellation lifecycle ──────────────────────────────────────────

    #[Test]
    public function cancellation_lifecycle_policy_guards_and_eligibility_table(): void
    {
        $this->tenant->run(function (): void {
            $cancellation = app(OrderCancellationService::class);
            $policyService = app(OrderPolicyService::class);
            $customer = $this->createCustomer();
            $own = $this->createOwnProductVariant(stock: 10, price: 40.0);

            // ── (a) Pending COD order, customer cancels with reason + note ───────
            $pending = $this->createOrder($customer, [[$own, 2]], OrderStatus::Pending);
            $this->assertSame('cod', $pending->payment_method);

            $cancellation->cancel($pending, CancellationActor::Customer, $customer->id, CancellationReason::ChangedMind, 'Found it cheaper in town');

            $pending->refresh();
            $this->assertSame(OrderStatus::Cancelled, $pending->status);
            $this->assertNotNull($pending->cancelled_at);
            $this->assertSame(CancellationReason::ChangedMind, $pending->cancellation_reason);
            $this->assertSame('Found it cheaper in town', $pending->cancellation_note);
            $this->assertSame(CancellationActor::Customer, $pending->cancelled_by_type);
            $this->assertSame($customer->id, (int) $pending->cancelled_by_id);
            $this->assertNull($pending->stock_restored_at, 'COD stock was never deducted');
            $this->assertSame(10, $own->refresh()->stock, 'no stock change for a COD order');
            $this->assertSame(0, $pending->refundsQuery()->count(), 'nothing was paid, so nothing is refunded');
            $this->assertSame(OrderPaymentStatus::Unpaid, $pending->paymentState());
            $this->assertActivity($pending, 'Order cancelled', 'Cancelled by Customer. Reason: Changed my mind. Found it cheaper in town');
            $this->assertSame(1, $this->tenantNotifications('Order cancelled', $pending));
            $this->assertSame(1, $this->adminNotifications('Order cancelled', $pending));
            $this->mail->shouldHaveReceived('sendTenantOrderCancelled')
                ->withArgs(fn (Order $o) => $o->uuid === $pending->uuid && $o->cancellation_note === 'Found it cheaper in town')
                ->once();
            $this->mail->shouldNotHaveReceived('sendTenantRefundProcessed');

            // What the customer sees: reason + date (+ who) in the presenter and the shared partial.
            $summary = OrderAfterSalesPresenter::cancellation($pending);
            $this->assertSame('changed_mind', $summary['reason']);
            $this->assertSame('Changed my mind', $summary['reason_label']);
            $this->assertSame('Found it cheaper in town', $summary['note']);
            $this->assertSame('You', $summary['cancelled_by_label']);
            $this->assertNotNull($summary['cancelled_at']);
            $this->assertTrue($summary['cancelled_at']->equalTo($pending->cancelled_at));
            $this->assertSame([], OrderAfterSalesPresenter::refundsFor($pending));

            $html = view('livewire.tenant.storefront.partials.order-cancellation-summary', ['order' => $pending])->render();
            $this->assertStringContainsString('Changed my mind', $html);
            $this->assertStringContainsString('Found it cheaper in town', $html);
            $this->assertStringContainsString($pending->cancelled_at->translatedFormat('M j, Y H:i'), $html);

            // Double submit: blocked, nothing is written twice.
            $this->assertOrderError(
                fn () => $cancellation->cancel($pending, CancellationActor::Customer, $customer->id, CancellationReason::ChangedMind),
                'already_cancelled',
                'This order is already cancelled.',
            );
            $this->assertSame(1, $this->tenantNotifications('Order cancelled', $pending));
            $this->assertSame(1, OrderActivity::query()->where('order_id', $pending->id)->where('title', 'Order cancelled')->count());

            // Reason / note validation.
            $other = $this->createOrder($customer, [[$own, 1]], OrderStatus::Pending);
            $this->assertOrderError(fn () => $cancellation->cancel($other, CancellationActor::Customer, $customer->id, CancellationReason::OutOfStock), OrderActionException::INVALID_REASON);
            $this->assertOrderError(fn () => $cancellation->cancel($other, CancellationActor::Customer, $customer->id, CancellationReason::Other, '  '), OrderActionException::NOTE_REQUIRED);
            $this->assertSame(OrderStatus::Pending, $other->refresh()->status);

            // ── (b) Paid Processing order (central-catalog product), customer cancels ──
            $central = $this->createCentralCatalogVariant(stock: 20, soldCount: 5, price: 50.0);
            $paid = $this->createOrder(
                $customer,
                [[$central['variant'], 2]],
                OrderStatus::Processing,
                paid: true,
                attributes: ['shipping_charge' => 10],
            );
            $this->assertTrue(app(StockService::class)->decrementForOrder($paid));
            $this->assertSame(18, $central['variant']->refresh()->stock);
            $this->assertSame(18, $central['product']->refresh()->stock);
            $this->assertSame(18, (int) $central['central_variant']->refresh()->stock);
            $this->assertSame(18, (int) $central['central_product']->refresh()->stock);
            $this->assertSame(7, (int) $central['central_product']->sold_count);

            $cancellation->cancel($paid, CancellationActor::Customer, $customer->id, CancellationReason::FoundBetterPrice);

            $paid->refresh()->load('items');
            $this->assertSame(OrderStatus::Cancelled, $paid->status, 'a cancelled order stays cancelled after its refund');
            $this->assertNotNull($paid->stock_restored_at);
            // Stock restored exactly once — tenant + central.
            $this->assertSame(20, $central['variant']->refresh()->stock);
            $this->assertSame(20, $central['product']->refresh()->stock);
            $this->assertSame(20, (int) $central['central_variant']->refresh()->stock);
            $this->assertSame(20, (int) $central['central_product']->refresh()->stock);
            $this->assertSame(5, (int) $central['central_product']->sold_count);
            $this->assertFalse(app(StockService::class)->restoreForOrder($paid), 'restore is idempotent');
            $this->assertSame(20, $central['variant']->refresh()->stock);
            $this->assertSame(20, (int) $central['central_variant']->refresh()->stock);

            // Refund row — every B.4 field.
            $refund = $paid->refundsQuery()->sole();
            $this->assertMatchesRegularExpression('/^RF-\d{8}-[A-Z0-9]{6}$/', $refund->reference);
            $this->assertSame((string) $this->tenant->id, (string) $refund->tenant_id);
            $this->assertSame($paid->uuid, $refund->order_number);
            $this->assertNull($refund->return_request_id);
            $this->assertSame(RefundSource::Cancellation, $refund->source);
            $this->assertStringContainsString('Found a better price elsewhere', (string) $refund->reason);
            $this->assertSame('USD', $refund->currency);
            $this->assertSame('110.00', $refund->amount);
            $this->assertSame('100.00', $refund->items_amount);
            $this->assertSame('10.00', $refund->shipping_amount);
            $this->assertSame('0.00', $refund->return_fee);
            $this->assertSame('stripe', $refund->payment_method);
            $this->assertSame('stripe', $refund->gateway);
            $this->assertSame($paid->payment_details['transaction_id'], $refund->original_transaction_id);
            $this->assertSame(RefundMethod::OriginalPayment, $refund->refund_method);
            $this->assertStringStartsWith('re_fake_', (string) $refund->gateway_refund_id);
            $this->assertSame(RefundStatus::Completed, $refund->status);
            $this->assertNull($refund->failure_reason);
            $this->assertSame(CancellationActor::Customer, $refund->requested_by_type);
            $this->assertSame($customer->id, (int) $refund->requested_by_id);
            $this->assertSame(CancellationActor::System, $refund->approved_by_type, 'auto-refund is approved by the system');
            $this->assertNotNull($refund->approved_at);
            $this->assertNotNull($refund->requested_at);
            $this->assertNotNull($refund->processed_at);
            $this->gateway->assertRefunded(110.0, $paid->payment_details['transaction_id']);

            $this->assertSame('110.00', $paid->refunded_amount);
            $this->assertNotNull($paid->refunded_at);
            $this->assertSame(OrderPaymentStatus::Refunded, $paid->paymentState());
            $this->assertActivity($paid, 'Order cancelled');
            $this->assertActivity($paid, 'Refund requested');
            $this->assertActivity($paid, 'Refund completed');

            // Vendor + admin were told, the customer was emailed once about the cancellation and once about the refund.
            foreach (['Order cancelled', 'Refund requested', 'Refund completed'] as $title) {
                $this->assertSame(1, $this->tenantNotifications($title, $paid), "vendor notification [{$title}]");
                $this->assertSame(1, $this->adminNotifications($title, $paid), "admin notification [{$title}]");
            }
            $this->mail->shouldHaveReceived('sendTenantOrderCancelled')->withArgs(fn (Order $o) => $o->uuid === $paid->uuid)->once();
            $this->mail->shouldHaveReceived('sendTenantRefundProcessed')->withArgs(fn (Order $o, $r) => $o->uuid === $paid->uuid && $r->id === $refund->id)->once();

            $customerRefunds = OrderAfterSalesPresenter::refundsFor($paid);
            $this->assertCount(1, $customerRefunds);
            $this->assertSame('completed', $customerRefunds[0]['status']);
            $this->assertSame(110.0, $customerRefunds[0]['amount']);
            $this->assertNotNull($customerRefunds[0]['processed_at']);

            // ── (c) Processing with cancellation_allow_processing=false ──────────
            $policyService->update([OrderPolicyService::CANCELLATION_ALLOW_PROCESSING => false]);
            $locked = $this->createOrder($customer, [[$own, 1]], OrderStatus::Processing);

            $this->assertOrderError(
                fn () => $cancellation->cancel($locked, CancellationActor::Customer, $customer->id, CancellationReason::ChangedMind),
                'processing_locked',
                'This order is already being prepared and can no longer be cancelled. Please contact the store.',
            );
            $this->assertSame(OrderStatus::Processing, $locked->refresh()->status);
            $this->assertNull($locked->cancelled_at);

            $cancellation->cancel($locked, CancellationActor::Vendor, $this->admin->id, CancellationReason::OutOfStock, 'Supplier delay');
            $locked->refresh();
            $this->assertSame(OrderStatus::Cancelled, $locked->status);
            $this->assertSame(CancellationActor::Vendor, $locked->cancelled_by_type);
            $this->assertSame($this->admin->id, (int) $locked->cancelled_by_id);
            $this->assertSame('The store', OrderAfterSalesPresenter::cancellation($locked)['cancelled_by_label']);

            // ── (d) Shipped / Delivered / Cancelled / Refunded are blocked ───────
            $policyService->update([OrderPolicyService::CANCELLATION_ALLOW_PROCESSING => true]);
            $blocked = [
                [OrderStatus::Shipped, 'shipped', "This order has already been shipped. Once it's delivered you can request a return."],
                [OrderStatus::Delivered, 'delivered', 'This order was delivered — please request a return instead.'],
                [OrderStatus::Cancelled, 'already_cancelled', 'This order is already cancelled.'],
                [OrderStatus::Refunded, 'already_refunded', 'This order has already been refunded.'],
            ];

            foreach ($blocked as [$status, $code, $message]) {
                $order = $this->createOrder($customer, [[$own, 1]], $status, paid: true);

                foreach ([[CancellationActor::Customer, $customer->id], [CancellationActor::Vendor, $this->admin->id], [CancellationActor::Admin, null]] as [$actor, $actorId]) {
                    $this->assertOrderError(
                        fn () => $cancellation->cancel($order, $actor, $actorId, $actor === CancellationActor::Customer ? CancellationReason::ChangedMind : CancellationReason::CustomerRequest),
                        $code,
                        $message,
                    );
                }

                $this->assertSame($status, $order->refresh()->status, "{$status->value} order untouched");
                $this->assertSame(0, $order->refundsQuery()->count());
            }

            $refundedOrder = $this->createOrder($customer, [[$own, 1]], OrderStatus::Refunded, paid: true);
            $refundService = app(RefundService::class);
            $this->assertRefundError(
                fn () => $refundService->create($refundedOrder, RefundSource::Manual, 10.0, 'Goodwill', RefundActor::vendor($this->admin->id)),
                RefundException::orderRefunded()->getMessage(),
            );
            $this->assertRefundError(fn () => $refundService->requestForCancellation($refundedOrder, CancellationActor::Admin), RefundException::orderRefunded()->getMessage());
            $unpaid = $this->createOrder($customer, [[$own, 1]], OrderStatus::Processing);
            $this->assertRefundError(
                fn () => $refundService->create($unpaid, RefundSource::Manual, 10.0, 'Nothing collected', RefundActor::vendor($this->admin->id)),
                RefundException::notPaid()->getMessage(),
            );
            $this->assertSame(0, Refund::query()->where('order_number', $refundedOrder->uuid)->count());

            // ── (h) Status vs. eligibility table, one data-driven assertion ───────
            //            status               customer cancel   vendor cancel   return request
            $table = [
                [OrderStatus::Pending,    true,  true,  false],  // ✅ cancel
                [OrderStatus::Processing, true,  true,  false],  // ✅ cancel per policy
                [OrderStatus::Shipped,    false, false, false],  // ⚠️ → return once delivered
                [OrderStatus::Delivered,  false, false, true],   // ❌ cancel → return
                [OrderStatus::Completed,  false, false, true],   // treated like Delivered
                [OrderStatus::Cancelled,  false, false, false],  // ❌
                [OrderStatus::Rejected,   false, false, false],  // treated like Cancelled
                [OrderStatus::Refunded,   false, false, false],  // ❌
            ];

            $policy = app(OrderCancellationPolicy::class);
            $returns = app(ReturnRequestService::class);
            $actual = [];

            foreach ($table as [$status]) {
                $order = $this->createOrder($customer, [[$own, 1]], $status, paid: true);
                $order->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);

                $errors = $returns->creationErrors([
                    'tenant_id' => (string) $this->tenant->id,
                    'order_number' => $order->uuid,
                    'customer_id' => $customer->id,
                    'order_item_id' => $order->items->first()->id,
                    'quantity' => 1,
                    'type' => 'return',
                    'return_method' => 'ship_back',
                    'reason' => 'changed_mind',
                ]);

                $actual[] = [
                    $status,
                    $policy->evaluate($order, CancellationActor::Customer, $policyService)->allowed,
                    $policy->evaluate($order, CancellationActor::Vendor, $policyService)->allowed,
                    $errors === [],
                ];
            }

            $this->assertSame($table, $actual);
            $this->assertTrue($policy->evaluate($this->createOrder($customer, [[$own, 1]], OrderStatus::Shipped), CancellationActor::Customer)->suggestReturn);
            $this->assertTrue($policy->evaluate($this->createOrder($customer, [[$own, 1]], OrderStatus::Delivered), CancellationActor::Customer)->suggestReturn);
            $this->assertFalse($policy->evaluate($this->createOrder($customer, [[$own, 1]], OrderStatus::Cancelled), CancellationActor::Customer)->suggestReturn);
        });

        $this->assertNull(tenant(), 'tenant context restored');
    }

    // ─── 2. Partial → full return of a delivered order ───────────────────────

    #[Test]
    public function delivered_order_is_returned_in_two_steps_until_fully_refunded(): void
    {
        $this->tenant->run(function (): void {
            $service = app(ReturnRequestService::class);
            $refunds = app(RefundService::class);
            $vendor = RefundActor::vendor($this->admin->id);
            $customer = $this->createCustomer();
            $variant = $this->createOwnProductVariant(stock: 20, price: 100.0);

            Setting::updateOrCreate(['name' => 'return_policy_fee'], ['value' => '10', 'type' => SettingType::String->value, 'group' => 'return_policy']);

            $order = $this->createOrder($customer, [[$variant, 3]], OrderStatus::Processing, paid: true);
            $this->assertTrue(app(StockService::class)->decrementForOrder($order));
            $order->update(['status' => OrderStatus::Delivered]);
            $order->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);
            $order->refresh()->load('items');
            $item = $order->items->first();
            $this->assertSame(17, $variant->refresh()->stock);
            $this->assertSame('300.00', number_format($order->grand_total, 2, '.', ''));

            // ── Return #1: 2 units, defective, photos, courier pickup ────────────
            $first = $service->create([
                'tenant_id' => (string) $this->tenant->id,
                'order_number' => $order->uuid,
                'customer_id' => $customer->id,
                'order_item_id' => $item->id,
                'quantity' => 2,
                'type' => 'return',
                'return_method' => 'courier_pickup',
                'reason' => 'defective',
                'description' => 'The screen arrived cracked.',
                'customer_note' => 'Call before pickup please.',
            ], [UploadedFile::fake()->image('crack-1.jpg'), UploadedFile::fake()->image('crack-2.jpg')], [UploadedFile::fake()->create('crack.mp4', 100, 'video/mp4')]);

            $this->assertSame(ReturnStatus::Pending, $first->status);
            $this->assertSame(ReturnType::Return, $first->type);
            $this->assertSame(2, $first->quantity);
            $this->assertSame($item->id, (int) $first->order_item_id);
            $this->assertSame(ReturnReason::Defective, $first->reason);
            $this->assertSame(ReturnMethod::CourierPickup, $first->return_method);
            $this->assertSame('Call before pickup please.', $first->customer_note);
            $this->assertSame(2, $first->media()->where('type', 'photo')->count());
            $this->assertActivity($order, 'Return requested', '2 × ');
            $this->assertSame(1, $service->remainingQuantity($order, $item));

            // Photos are mandatory for a seller-fault reason.
            $this->assertContains('At least one photo is required as evidence.', $service->creationErrors([
                'tenant_id' => (string) $this->tenant->id, 'order_number' => $order->uuid, 'customer_id' => $customer->id,
                'order_item_id' => $item->id, 'quantity' => 1, 'type' => 'return', 'return_method' => 'drop_off',
                'reason' => 'defective', 'description' => 'The screen arrived cracked.',
            ]));

            $first = $service->approve($first, $vendor);
            $first = $service->markItemReceived($first, $vendor);
            $first = $service->inspect($first, InspectionResult::Passed, 'Good as new', true, $vendor);
            $this->assertSame(ReturnStatus::Inspected, $first->status);
            $this->assertNotNull($first->received_at);
            $this->assertNotNull($first->inspected_at);
            $this->assertNotNull($first->restocked_at);
            $this->assertSame(19, $variant->refresh()->stock, '2 returned units are back on the shelf');

            $calculated = $refunds->calculateForReturn($first);
            $this->assertSame(200.0, $calculated->amount);
            $this->assertSame(0.0, $calculated->returnFee, 'the 10.00 policy fee is waived for a defective item');
            $this->assertTrue($calculated->feeWaived);

            $refundOne = $service->issueRefund($first, null, $vendor);

            $this->assertSame(RefundStatus::Completed, $refundOne->status);
            $this->assertSame(RefundSource::Return, $refundOne->source);
            $this->assertSame($first->id, (int) $refundOne->return_request_id);
            $this->assertSame(ReturnReason::Defective->label(), $refundOne->reason);
            $this->assertSame(number_format($calculated->amount, 2, '.', ''), $refundOne->amount);
            $this->assertSame('200.00', $refundOne->items_amount);
            $this->assertSame('0.00', $refundOne->shipping_amount);
            $this->assertSame('0.00', $refundOne->return_fee);
            $this->assertSame('stripe', $refundOne->payment_method);
            $this->assertSame(RefundMethod::OriginalPayment, $refundOne->refund_method);
            $this->assertSame($order->payment_details['transaction_id'], $refundOne->original_transaction_id);
            $this->assertNotNull($refundOne->gateway_refund_id);
            $this->assertSame(CancellationActor::Vendor, $refundOne->requested_by_type);
            $this->assertSame(CancellationActor::Vendor, $refundOne->approved_by_type);
            $this->assertSame($this->admin->id, (int) $refundOne->approved_by_id);
            $this->assertSame('Panel Test Admin', $refundOne->approved_by_name);
            $this->assertNotNull($refundOne->requested_at);
            $this->assertNotNull($refundOne->approved_at);
            $this->assertNotNull($refundOne->processed_at);
            $this->gateway->assertRefunded(200.0, $order->payment_details['transaction_id']);

            $first->refresh();
            $this->assertSame(ReturnStatus::Refunded, $first->status);
            $this->assertSame('200.00', $first->refund_amount);

            $order->refresh()->load('items');
            $this->assertSame('200.00', $order->refunded_amount);
            $this->assertSame(OrderPaymentStatus::PartiallyRefunded, $order->paymentState());
            $this->assertSame(OrderStatus::Delivered, $order->status);
            $this->assertNull($order->refunded_at);

            // ── A 4th unit does not exist; the last one is still returnable ──────
            $this->assertSame(1, $service->remainingQuantity($order, $item));

            // ── Return #2: the last unit, changed mind → fee applied ─────────────
            $second = $service->create([
                'tenant_id' => (string) $this->tenant->id,
                'order_number' => $order->uuid,
                'customer_id' => $customer->id,
                'order_item_id' => $item->id,
                'quantity' => 1,
                'type' => 'return',
                'return_method' => 'drop_off',
                'reason' => 'changed_mind',
            ]);
            $this->assertSame(0, $service->remainingQuantity($order, $item));

            // A further return while the last unit is in flight is refused: no quantity left.
            $payload = [
                'tenant_id' => (string) $this->tenant->id, 'order_number' => $order->uuid, 'customer_id' => $customer->id,
                'order_item_id' => $item->id, 'quantity' => 1, 'type' => 'return', 'return_method' => 'drop_off', 'reason' => 'changed_mind',
            ];
            $this->assertContains('All units of this item have already been returned.', $service->creationErrors($payload));
            $this->assertReturnError(fn () => $service->create($payload), ReturnActionException::VALIDATION);

            $second = $service->approve($second, $vendor);
            $second = $service->markItemReceived($second, $vendor);
            $second = $service->inspect($second, InspectionResult::Passed, null, true, $vendor);
            $this->assertSame(20, $variant->refresh()->stock, 'all 3 units are back');

            $calculated = $refunds->calculateForReturn($second);
            $this->assertSame(90.0, $calculated->amount);
            $this->assertSame(10.0, $calculated->returnFee);
            $this->assertFalse($calculated->feeWaived);

            $refundTwo = $service->issueRefund($second, null, $vendor);

            $this->assertSame(RefundStatus::Completed, $refundTwo->status);
            $this->assertSame('90.00', $refundTwo->amount);
            $this->assertSame('100.00', $refundTwo->items_amount);
            $this->assertSame('0.00', $refundTwo->shipping_amount);
            $this->assertSame('10.00', $refundTwo->return_fee, 'the policy fee is deducted');
            $this->assertSame(ReturnReason::ChangedMind->label(), $refundTwo->reason);
            $this->assertSame(CancellationActor::Vendor, $refundTwo->approved_by_type);
            $this->assertNotNull($refundTwo->requested_at);
            $this->assertNotNull($refundTwo->processed_at);
            $this->gateway->assertRefunded(90.0, $order->payment_details['transaction_id']);
            $this->assertNotSame($refundOne->reference, $refundTwo->reference);

            $this->assertSame(ReturnStatus::Refunded, $second->refresh()->status);
            $this->assertSame('90.00', $second->refund_amount);

            // Every unit came back and the 10.00 fee is retained by the store → fully refunded.
            $order->refresh()->load('items');
            $this->assertSame('290.00', $order->refunded_amount);
            $this->assertSame(OrderStatus::Refunded, $order->status);
            $this->assertSame(OrderPaymentStatus::Refunded, $order->paymentState());
            $this->assertNotNull($order->refunded_at);
            $this->assertSame(0.0, $refunds->refundableAmount($order), 'nothing is left to refund');
            $this->assertSame(2, $order->refundsQuery()->where('status', RefundStatus::Completed->value)->count());

            // A 4th return (or any new one) on the now fully refunded order is refused.
            $this->assertReturnError(fn () => $service->create($payload), ReturnActionException::class);
            $this->assertRefundError(
                fn () => $refunds->create($order, RefundSource::Manual, 5.0, 'Extra', $vendor),
                RefundException::orderRefunded()->getMessage(),
            );

            // Audit trail, notes, notifications, emails.
            foreach (['Return requested', 'Return approved', 'Returned item received', 'Return inspected', 'Refund requested', 'Refund completed', 'Return refunded'] as $title) {
                $this->assertActivity($order, $title);
            }
            $this->assertSame(2, $this->tenantNotifications('Refund completed', $order));
            $this->assertSame(2, $this->adminNotifications('Refund completed', $order));
            $this->mail->shouldHaveReceived('sendTenantRefundProcessed')->twice();
            $this->mail->shouldHaveReceived('sendReturnStatusUpdate')->atLeast()->times(8);
        });

        $this->assertNull(tenant());
    }

    // ─── 3. Exchange ─────────────────────────────────────────────────────────

    #[Test]
    public function exchange_happy_path_stock_unavailable_and_price_mismatch(): void
    {
        /** @var array{customer: Customer, order: Order, a: ProductVariant, b: ProductVariant, c: ProductVariant, d: ProductVariant} $fx */
        $fx = $this->tenant->run(function (): array {
            $customer = $this->createCustomer();
            $a = $this->createOwnProductVariant(stock: 10, price: 100.0);
            $order = $this->createOrder($customer, [[$a, 2]], OrderStatus::Processing, paid: true);
            app(StockService::class)->decrementForOrder($order);                  // a: 10 → 8
            $order->update(['status' => OrderStatus::Delivered]);
            $order->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);

            return [
                'customer' => $customer,
                'order' => $order->refresh()->load('items'),
                'a' => $a,
                'b' => $this->createVariantFor($a->product, stock: 3, price: 100.0),  // same price, in stock
                'c' => $this->createVariantFor($a->product, stock: 5, price: 120.0),  // different price
                'd' => $this->createVariantFor($a->product, stock: 0, price: 100.0),  // sold out
            ];
        });
        $this->assertNull(tenant());

        ['customer' => $customer, 'order' => $order, 'a' => $a, 'b' => $b, 'c' => $c, 'd' => $d] = $fx;
        $service = app(ReturnRequestService::class);
        $vendor = RefundActor::vendor($this->admin->id);

        $exchange = fn (ProductVariant $variant, int $qty = 1) => [
            'tenant_id' => (string) $this->tenant->id,
            'order_number' => $order->uuid,
            'customer_id' => $customer->id,
            'order_item_id' => $order->items->first()->id,
            'quantity' => $qty,
            'type' => 'exchange',
            'return_method' => 'ship_back',
            'reason' => 'size_or_fit',
            'replacement_product_variant_id' => $variant->id,
        ];

        // ── Stock unavailable / price mismatch are refused, nothing is written ─
        $this->assertReturnError(fn () => $service->create($exchange($d)), ReturnActionException::OUT_OF_STOCK, 'Only 0 left of');
        $this->assertReturnError(fn () => $service->create($exchange($b, 4)), ReturnActionException::VALIDATION);
        $this->assertReturnError(fn () => $service->create($exchange($c)), ReturnActionException::OUT_OF_STOCK, 'different price');
        $this->assertSame(0, ReturnRequest::query()->where('order_number', $order->uuid)->count());
        $this->assertSame([3, 5, 0], [$this->stockOf($b), $this->stockOf($c), $this->stockOf($d)], 'refused requests move no stock');
        $this->assertNull(tenant());

        // ── Happy path ────────────────────────────────────────────────────────
        $request = $service->create($exchange($b));
        $this->assertSame(ReturnType::Exchange, $request->type);
        $this->assertSame($b->id, (int) $request->replacement_product_variant_id);
        $this->assertSame(1, $request->replacement_quantity);
        $this->assertSame(3, $this->stockOf($b), 'nothing is reserved before approval');

        $request = $service->approve($request, $vendor);
        $this->assertNotNull($request->replacement_reserved_at);
        $this->assertSame(2, $this->stockOf($b), 'replacement reserved on approval');

        $request = $service->markItemReceived($request, $vendor);
        $request = $service->inspect($request, InspectionResult::Passed, null, true, $vendor);
        $this->assertSame(9, $this->stockOf($a), 'returned unit restocked');

        $request = $service->markExchangeShipped($request, 'TRK-998', $vendor);
        $this->assertSame(ReturnStatus::ExchangeShipped, $request->status);
        $this->assertSame('TRK-998', $request->exchange_tracking_number);
        $this->assertNotNull($request->exchange_shipped_at);

        $request = $service->markExchangeCompleted($request, $vendor);
        $this->assertSame(ReturnStatus::Exchanged, $request->status);
        $this->assertNotNull($request->exchange_completed_at);
        $this->assertSame(2, $this->stockOf($b), 'a shipped replacement stays deducted');

        // No money moved for a same-price exchange.
        $this->assertSame(0, Refund::query()->where('order_number', $order->uuid)->count());
        $this->gateway->assertNothingRefunded();
        $this->tenant->run(function () use ($order): void {
            $fresh = $order->fresh();
            $this->assertSame('0.00', $fresh->refunded_amount);
            $this->assertSame(OrderStatus::Delivered, $fresh->status);
            foreach (['Exchange requested', 'Exchange shipped', 'Exchange completed'] as $title) {
                $this->assertActivity($fresh, $title);
            }
        });
        $this->mail->shouldHaveReceived('sendReturnStatusUpdate')
            ->withArgs(fn (ReturnRequest $r, Order $o, ?string $message) => $r->id === $request->id && str_contains((string) $message, 'TRK-998'))
            ->once();
        $this->assertNull(tenant());
    }

    // ─── 4. Gateway failure → retry; COD manual completion ───────────────────

    #[Test]
    public function failed_gateway_refund_is_retried_and_cod_refunds_are_completed_manually(): void
    {
        $this->tenant->run(function (): void {
            $service = app(ReturnRequestService::class);
            $refunds = app(RefundService::class);
            $vendor = RefundActor::vendor($this->admin->id);
            $customer = $this->createCustomer();
            $variant = $this->createOwnProductVariant(stock: 10, price: 80.0);
            $base = fn (Order $order, array $extra = []) => array_merge([
                'tenant_id' => (string) $this->tenant->id,
                'order_number' => $order->uuid,
                'customer_id' => $customer->id,
                'order_item_id' => $order->items->first()->id,
                'quantity' => 1,
                'type' => 'return',
                'return_method' => 'ship_back',
                'reason' => 'defective',
                'description' => 'It does not switch on at all.',
            ], $extra);

            // ── Gateway failure → refund failed → vendor retry → completed ───────
            $order = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Processing, paid: true, attributes: ['shipping_charge' => 5]);
            app(StockService::class)->decrementForOrder($order);
            $order->update(['status' => OrderStatus::Delivered]);
            $order->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);
            $order->refresh()->load('items');

            $request = $service->create($base($order), [UploadedFile::fake()->image('broken.jpg')], [UploadedFile::fake()->create('broken.mp4', 100, 'video/mp4')]);
            $service->approve($request, $vendor);
            $service->markItemReceived($request->refresh(), $vendor);
            $request = $service->inspect($request->refresh(), InspectionResult::Passed, null, true, $vendor);

            $this->gateway->fail('Insufficient funds on the merchant account.');
            $refund = $service->issueRefund($request, null, $vendor);

            $this->assertSame(RefundStatus::Failed, $refund->status);
            $this->assertSame('The payment gateway declined the refund. Please retry or complete it manually.', $refund->failure_reason, 'safe, generic reason');
            $this->assertNotNull($refund->processed_at, 'the failed attempt is dated');
            $this->assertNull($refund->gateway_refund_id);
            $this->assertSame('85.00', $refund->amount, 'defective + whole order → shipping refunded too');
            $this->assertSame('5.00', $refund->shipping_amount);
            $this->assertCount(1, $this->gateway->refunds);
            $this->gateway->assertNothingRefunded();

            $this->assertSame(ReturnStatus::Inspected, $request->refresh()->status, 'the return waits for the refund');
            $this->assertSame('0.00', $order->refresh()->refunded_amount);
            $this->assertSame(OrderStatus::Delivered, $order->status);
            $this->assertSame(OrderPaymentStatus::Paid, $order->paymentState());
            $this->assertActivity($order, 'Refund failed');
            $this->assertSame(1, $this->tenantNotifications('Refund failed — action needed', $order));
            $this->assertSame(1, $this->adminNotifications('Refund failed — action needed', $order));
            $this->mail->shouldNotHaveReceived('sendTenantRefundProcessed');

            // The customer sees the pending/failed state without internals.
            $shown = OrderAfterSalesPresenter::refundsFor($order)[0];
            $this->assertSame('failed', $shown['status']);
            $this->assertNull($shown['processed_at'], 'only completed refunds expose a completion date');

            // A failed refund no longer reserves its amount, so the reviewer may re-issue one …
            $duplicate = $service->issueRefund($request->refresh(), null, $vendor);
            $this->assertNotSame($refund->id, $duplicate->id);
            $this->assertSame(RefundStatus::Failed, $duplicate->status);

            $this->gateway->succeed();
            $retried = $refunds->retry($refund, $vendor);

            $this->assertSame($refund->id, $retried->id);
            $this->assertSame(RefundStatus::Completed, $retried->status);
            $this->assertNull($retried->failure_reason);
            $this->assertStringStartsWith('re_fake_', (string) $retried->gateway_refund_id);
            $this->assertSame(CancellationActor::Vendor, $retried->approved_by_type);
            $this->assertSame('Panel Test Admin', $retried->approved_by_name);
            $this->assertNotNull($retried->processed_at);
            $this->gateway->assertRefunded(85.0, $order->payment_details['transaction_id']);
            $this->assertCount(3, $this->gateway->refunds, 'two failed calls + one successful retry');
            $this->assertCount(1, $this->gateway->successfulRefunds());

            $this->assertRefundError(fn () => $refunds->retry($retried, $vendor), RefundException::invalidState('retried')->getMessage());
            // … but the surplus failed duplicate can never be retried or completed once the money is back (no double refund).
            $this->assertRefundError(fn () => $refunds->retry($duplicate, $vendor), RefundException::exceedsRefundable(0.0)->getMessage());
            $this->assertRefundError(fn () => $refunds->markCompletedManually($duplicate, $vendor), RefundException::exceedsRefundable(0.0)->getMessage());
            $this->assertCount(3, $this->gateway->refunds, 'nothing was sent to the gateway again');
            $this->assertSame(RefundStatus::Failed, $duplicate->refresh()->status);

            $this->assertSame(ReturnStatus::Refunded, $request->refresh()->status);
            $order->refresh()->load('items');
            $this->assertSame('85.00', $order->refunded_amount);
            $this->assertSame(OrderStatus::Refunded, $order->status);
            $this->assertSame(OrderPaymentStatus::Refunded, $order->paymentState());
            $this->assertSame(1, $this->tenantNotifications('Refund completed', $order));
            $this->mail->shouldHaveReceived('sendTenantRefundProcessed')->withArgs(fn (Order $o, $r) => $o->uuid === $order->uuid)->once();

            // ── COD, delivered (cash collected, never flagged paid): manual completion ─
            $before = count($this->gateway->refunds);
            $cod = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Delivered);
            $cod->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);
            $this->assertFalse($cod->paid);
            $this->assertTrue($cod->isPaymentCollected());

            $codReturn = $service->create($base($cod, ['reason' => 'changed_mind', 'description' => null]));
            $service->approve($codReturn, $vendor);
            $service->markItemReceived($codReturn->refresh(), $vendor);
            $codReturn = $service->inspect($codReturn->refresh(), InspectionResult::Passed, null, true, $vendor);
            $this->assertNull($codReturn->restocked_at, 'stock was never deducted for COD');
            $this->assertSame(10, $variant->refresh()->stock, 'COD return adds no phantom stock (the first order was restocked back to 10)');

            $codRefund = $service->issueRefund($codReturn, null, $vendor);
            $this->assertSame(RefundMethod::Manual, $codRefund->refund_method);
            $this->assertSame(RefundStatus::Pending, $codRefund->status);
            $this->assertSame('cod', $codRefund->payment_method);
            $this->assertSame('80.00', $codRefund->amount);
            $this->assertNull($codRefund->processed_at);
            $this->assertSame(CancellationActor::Vendor, $codRefund->approved_by_type);
            $this->assertCount($before, $this->gateway->refunds, 'the gateway is never called for COD');
            $this->assertSame(ReturnStatus::Inspected, $codReturn->refresh()->status);

            $done = $refunds->markCompletedManually($codRefund, $vendor, null, 'CASH-RECEIPT-77');
            $this->assertSame(RefundStatus::Completed, $done->status);
            $this->assertSame(RefundMethod::Manual, $done->refund_method);
            $this->assertSame('CASH-RECEIPT-77', $done->meta['manual_reference']);
            $this->assertNotNull($done->processed_at);
            $this->assertCount($before, $this->gateway->refunds);

            $this->assertSame(ReturnStatus::Refunded, $codReturn->refresh()->status);
            $cod->refresh()->load('items');
            $this->assertFalse($cod->paid, 'the paid flag is never touched for COD');
            $this->assertSame('80.00', $cod->refunded_amount);
            $this->assertSame(OrderStatus::Refunded, $cod->status);
            $this->assertSame(OrderPaymentStatus::Refunded, $cod->paymentState());
            $this->assertRefundError(fn () => $refunds->markCompletedManually($done, $vendor), RefundException::invalidState('completed')->getMessage());
            $this->assertSame(1, $this->tenantNotifications('Refund completed', $cod));
        });

        $this->assertNull(tenant());
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function tenantNotifications(string $title, Order $order): int
    {
        return TenantNotification::query()->where('title', $title)->where('message', 'like', "%{$order->uuid}%")->count();
    }

    private function adminNotifications(string $title, Order $order): int
    {
        return AdminNotification::on(config('tenancy.database.central_connection'))->where('title', $title)->where('message', 'like', "%{$order->uuid}%")->count();
    }

    private function stockOf(ProductVariant $variant): int
    {
        return (int) $this->tenant->run(fn () => ProductVariant::query()->whereKey($variant->id)->value('stock'));
    }

    private function assertActivity(Order $order, string $title, ?string $contains = null): void
    {
        $activity = OrderActivity::query()->where('order_id', $order->id)->where('title', $title)->latest('id')->first();

        $this->assertNotNull($activity, "Missing order activity [{$title}]. Got: ".OrderActivity::query()->where('order_id', $order->id)->pluck('title')->toJson());

        if ($contains !== null) {
            $this->assertStringContainsString($contains, (string) $activity->description);
        }
    }

    private function assertOrderError(\Closure $callback, string $reason, ?string $message = null): void
    {
        try {
            $callback();
        } catch (OrderActionException $e) {
            $this->assertSame($reason, $e->reason, $e->getMessage());

            if ($message !== null) {
                $this->assertSame($message, $e->getMessage());
            }

            return;
        }

        $this->fail("Expected an OrderActionException [{$reason}].");
    }

    private function assertReturnError(\Closure $callback, string $reason, ?string $messageContains = null): void
    {
        try {
            $callback();
        } catch (ReturnActionException $e) {
            if ($reason !== ReturnActionException::class) {
                $this->assertSame($reason, $e->reason, $e->getMessage());
            }

            if ($messageContains) {
                $this->assertStringContainsString($messageContains, $e->getMessage());
            }

            return;
        }

        $this->fail("Expected a ReturnActionException [{$reason}].");
    }

    private function assertRefundError(\Closure $callback, string $message): void
    {
        try {
            $callback();
        } catch (RefundException $e) {
            $this->assertSame($message, $e->getMessage());

            return;
        }

        $this->fail("Expected a RefundException: {$message}");
    }
}
