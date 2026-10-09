<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\CancellationActor;
use App\Enums\InspectionResult;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Exceptions\ReturnActionException;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestNote;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderActivity;
use App\Models\Tenant\ProductVariant;
use App\Services\Mail\TemplateMailService;
use App\Services\Refunds\RefundActor;
use App\Services\ReturnRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Phase 4: return & exchange workflow against real services and the fake payment gateway.
 * Consolidated into two methods (tenant context / central context) because each tenant boot
 * is expensive.
 */
class ReturnExchangeWorkflowTest extends TestCase
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
    public function return_lifecycle_rejection_info_loop_and_creation_rules(): void
    {
        $this->tenant->run(function (): void {
            $service = app(ReturnRequestService::class);
            $vendor = RefundActor::vendor($this->admin->id);
            $customer = $this->createCustomer();
            $variant = $this->createOwnProductVariant(stock: 10, price: 100.0);
            $order = $this->deliveredOrder($customer, $variant, 2); // 2 × 100 = 200
            $item = $order->items->first();

            // ── Full return lifecycle ────────────────────────────────────────
            $request = $service->create($this->payload($order, $customer, ['quantity' => 1]));

            $this->assertSame(ReturnStatus::Pending, $request->status);
            $this->assertSame(ReturnType::Return, $request->type);
            $this->assertSame($item->id, (int) $request->order_item_id);
            $this->assertSame(1, $request->quantity);
            $this->assertSame(['approve', 'reject', 'request_info'], $service->availableActions($request, 'vendor'));
            $this->assertSame(['approve', 'reject', 'request_info', 'forward_to_merchant'], $service->availableActions($request, CancellationActor::Admin));
            $this->assertSame(['withdraw'], $service->availableActions($request, 'customer'));
            $this->assertActivity($order, 'Return requested', '1 × ');

            // Illegal transitions throw and change nothing.
            $this->assertReturnError(fn () => $service->markItemReceived($request, $vendor), ReturnActionException::INVALID_TRANSITION);
            $this->assertReturnError(fn () => $service->issueRefund($request, null, $vendor), ReturnActionException::INVALID_TRANSITION);
            $this->assertReturnError(fn () => $service->approve($request, CancellationActor::Customer, $customer->id), ReturnActionException::NOT_ALLOWED);
            $this->assertSame(ReturnStatus::Pending, $request->refresh()->status);

            $request = $service->approve($request, $vendor);
            $this->assertSame(ReturnStatus::Approved, $request->status);
            $this->assertSame(CancellationActor::Vendor, $request->reviewed_by_type);
            $this->assertSame($this->admin->id, (int) $request->reviewed_by_id);
            $this->assertNull($request->reviewed_by_admin_id);
            $this->assertNotNull($request->reviewed_at);

            $request = $service->markItemReceived($request, $vendor);
            $this->assertSame(ReturnStatus::ItemReceived, $request->status);
            $this->assertNotNull($request->received_at);

            $request = $service->inspect($request, InspectionResult::Passed, 'Unused, original box', null, $vendor);
            $this->assertSame(ReturnStatus::Inspected, $request->status);
            $this->assertSame(InspectionResult::Passed, $request->inspection_result);
            $this->assertNotNull($request->restocked_at);
            $this->assertSame(11, $variant->refresh()->stock, 'returned unit restocked');
            $this->assertSame(['reject', 'issue_refund'], $service->availableActions($request, 'vendor'));

            $refund = $service->issueRefund($request, null, $vendor);

            $this->assertSame(RefundStatus::Completed, $refund->status);
            $this->assertSame(RefundSource::Return, $refund->source);
            $this->assertSame($request->id, (int) $refund->return_request_id);
            $this->assertSame('100.00', $refund->amount);
            $this->assertSame('100.00', $refund->items_amount);
            $this->assertSame('0.00', $refund->shipping_amount);
            $this->assertSame('0.00', $refund->return_fee);
            $this->assertSame(RefundMethod::OriginalPayment, $refund->refund_method);
            $this->assertSame(CancellationActor::Vendor, $refund->approved_by_type);
            $this->assertSame('Panel Test Admin', $refund->approved_by_name);
            $this->assertNotNull($refund->requested_at);
            $this->assertNotNull($refund->approved_at);
            $this->assertNotNull($refund->processed_at);
            $this->gateway->assertRefunded(100.0, $order->payment_details['transaction_id']);

            $request->refresh();
            $this->assertSame(ReturnStatus::Refunded, $request->status);
            $this->assertSame('100.00', $request->refund_amount);
            $this->assertSame(['close'], $service->availableActions($request, 'vendor'));

            $order->refresh()->load('items');
            $this->assertSame('100.00', $order->refunded_amount);
            $this->assertSame(OrderPaymentStatus::PartiallyRefunded, $order->paymentState());
            $this->assertSame(OrderStatus::Delivered, $order->status);

            foreach (['Return approved', 'Returned item received', 'Return inspected', 'Refund completed', 'Return refunded'] as $title) {
                $this->assertActivity($order, $title);
            }

            $visibleNotes = ReturnRequestNote::query()->where('return_request_id', $request->id)->where('customer_visible', true)->pluck('note')->implode(' | ');
            $this->assertStringContainsString('has been submitted', $visibleNotes);
            $this->assertStringContainsString('has been approved', $visibleNotes);
            $this->assertStringContainsString('Please ship the item back to the store.', $visibleNotes);
            $this->assertStringContainsString('passed inspection', $visibleNotes);
            $this->assertStringContainsString($refund->reference, $visibleNotes);
            $this->mail->shouldHaveReceived('sendReturnStatusUpdate')->atLeast()->times(5);
            $this->assertSame((string) $this->tenant->id, (string) tenant('id'), 'tenant context kept');

            // ── Quantity limits + one open request per item ──────────────────
            $this->assertSame(1, $service->remainingQuantity($order, $item));
            $this->assertContains('You can return between 1 and 1 unit(s) of this item.', $service->creationErrors($this->payload($order, $customer, ['quantity' => 2])));

            $second = $service->create($this->payload($order, $customer, ['quantity' => 1]));
            $this->assertSame(0, $service->remainingQuantity($order, $item));
            $errors = $service->creationErrors($this->payload($order, $customer, ['quantity' => 1]));
            $this->assertContains('There is already an open return request for this item.', $errors);
            $this->assertContains('All units of this item have already been returned.', $errors);
            $this->assertReturnError(fn () => $service->create($this->payload($order, $customer, ['quantity' => 1])), ReturnActionException::VALIDATION);

            // ── Rejection frees the quantity ─────────────────────────────────
            $this->assertReturnError(fn () => $service->reject($second, '   ', $vendor), ReturnActionException::VALIDATION);
            $second = $service->reject($second, 'Item shows signs of use.', $vendor);
            $this->assertSame(ReturnStatus::Rejected, $second->status);
            $this->assertTrue(ReturnRequestNote::query()->where('return_request_id', $second->id)->where('note', 'Item shows signs of use.')->where('customer_visible', true)->exists());
            $this->assertActivity($order, 'Return rejected', 'Item shows signs of use.');
            $this->assertReturnError(fn () => $service->approve($second, $vendor), ReturnActionException::INVALID_TRANSITION);
            $this->assertSame(1, $service->remainingQuantity($order, $item));

            // ── AwaitingInfo loop (direct, then forwarded to the merchant) ───
            $third = $service->create($this->payload($order, $customer, ['quantity' => 1]));
            $third = $service->requestMoreInfo($third, 'Please send a photo of the label.', $vendor);
            $this->assertSame(ReturnStatus::AwaitingInfo, $third->status);
            $this->assertSame(['withdraw', 'reply'], $service->availableActions($third, 'customer'));
            $this->assertReturnError(fn () => $service->customerReply($third, $customer->id + 999, 'Here it is.'), ReturnActionException::NOT_ALLOWED);

            $third = $service->customerReply($third, $customer->id, 'Here is the label photo.');
            $this->assertSame(ReturnStatus::Pending, $third->status);
            $this->assertTrue(ReturnRequestNote::query()->where('return_request_id', $third->id)->where('author_type', ReturnRequestNote::AUTHOR_CUSTOMER)->exists());
            $this->assertReturnError(fn () => $service->customerReply($third, $customer->id, 'Again'), ReturnActionException::NOT_ALLOWED);

            $this->assertReturnError(fn () => $service->markAwaitingMerchantReview($third, $vendor), ReturnActionException::NOT_ALLOWED);
            $third = $service->markAwaitingMerchantReview($third, RefundActor::admin(null, 'Support'));
            $this->assertSame(ReturnStatus::AwaitingMerchantReview, $third->status);
            $this->assertNotNull($third->forwarded_at);
            $this->assertSame(CancellationActor::Admin, $third->reviewed_by_type);

            $third = $service->requestMoreInfo($third, 'Which size did you order?', $vendor);
            $third = $service->customerReply($third, $customer->id, 'Size M.');
            $this->assertSame(ReturnStatus::AwaitingMerchantReview, $third->status, 'forwarded requests go back to the merchant');

            // ── Customer withdraw ────────────────────────────────────────────
            $third = $service->cancelByCustomer($third, $customer->id);
            $this->assertSame(ReturnStatus::Cancelled, $third->status);
            $this->assertNotNull($third->cancelled_at);
            $this->assertSame([], $service->availableActions($third, 'customer'));
            $this->assertReturnError(fn () => $service->cancelByCustomer($third, $customer->id), ReturnActionException::NOT_ALLOWED);
            $this->assertActivity($order, 'Return withdrawn');
            $third = $service->close($third, $vendor);
            $this->assertSame(ReturnStatus::Closed, $third->status);

            // ── Photos only when the reason needs evidence ───────────────────
            $defective = ['reason' => 'defective', 'description' => 'The screen arrived cracked.'];
            $this->assertNotContains('At least one photo is required as evidence.', $service->creationErrors($this->payload($order, $customer)));
            $this->assertContains('At least one photo is required as evidence.', $service->creationErrors($this->payload($order, $customer, $defective)));
            $this->assertNotContains('At least one photo is required as evidence.', $service->creationErrors($this->payload($order, $customer, $defective), 1));
            $this->assertContains('Please describe the problem (at least 10 characters).', $service->creationErrors($this->payload($order, $customer, ['reason' => 'defective', 'description' => 'bad']), 1));
            $this->assertContains('Please choose how you will return the item.', $service->creationErrors($this->payload($order, $customer, ['return_method' => null])));

            // ── Non-delivered / cancelled orders are refused ─────────────────
            $shipped = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Shipped, paid: true);
            $this->assertReturnError(fn () => $service->create($this->payload($shipped, $customer)), ReturnActionException::NOT_ELIGIBLE, 'You can request a return once the order is delivered.');
            $cancelled = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Cancelled, paid: true);
            $this->assertContains('This order is not eligible for a return.', $service->creationErrors($this->payload($cancelled, $customer)));
            $this->assertReturnError(fn () => $service->create($this->payload($order, $this->createCustomer())), ReturnActionException::NOT_FOUND);

            // ── COD: nothing to restock (stock never deducted) + manual refund ─
            $cod = $this->deliveredOrder($customer, $variant, 1, paid: false);
            $stockBefore = (int) $variant->refresh()->stock;
            $codRequest = $service->create($this->payload($cod, $customer));
            $service->approve($codRequest, $vendor);
            $service->markItemReceived($codRequest->refresh(), $vendor);
            $codRequest = $service->inspect($codRequest->refresh(), InspectionResult::Passed, null, true, $vendor);
            $this->assertNull($codRequest->restocked_at);
            $this->assertSame($stockBefore, (int) $variant->refresh()->stock);
            $this->assertTrue(ReturnRequestNote::query()->where('return_request_id', $codRequest->id)->where('customer_visible', false)->where('note', 'like', '%Not restocked%')->exists());

            $codRefund = $service->issueRefund($codRequest, 50, $vendor);
            $this->assertSame(RefundMethod::Manual, $codRefund->refund_method);
            $this->assertSame(RefundStatus::Pending, $codRefund->status);
            $this->assertSame(ReturnStatus::Inspected, $codRequest->refresh()->status, 'stays inspected while the refund is open');
            $this->assertSame([], $service->availableActions($codRequest, 'vendor'), 'no refund/reject while a refund is open');
            $this->assertSame(1, Refund::query()->where('return_request_id', $codRequest->id)->count());
        });
    }

    #[Test]
    public function exchange_flow_from_the_central_context(): void
    {
        /** @var array{customer: Customer, order: Order, a: ProductVariant, b: ProductVariant, c: ProductVariant, d: ProductVariant} $fixture */
        $fixture = $this->tenant->run(function (): array {
            $customer = $this->createCustomer();
            $a = $this->createOwnProductVariant(stock: 10, price: 100.0);
            $product = $a->product;

            return [
                'customer' => $customer,
                'a' => $a,
                'b' => $this->createVariantFor($product, stock: 1, price: 100.0),   // same price, 1 left
                'c' => $this->createVariantFor($product, stock: 5, price: 120.0),   // different price
                'd' => $this->createVariantFor($product, stock: 0, price: 100.0),   // out of stock
                'order' => $this->deliveredOrder($customer, $a, 2),
            ];
        });

        ['customer' => $customer, 'order' => $order, 'b' => $b, 'c' => $c, 'd' => $d] = $fixture;
        $this->assertNull(tenant());

        $service = app(ReturnRequestService::class);
        $vendor = RefundActor::vendor($this->admin->id);
        $tenantId = (string) $this->tenant->id;
        $item = $order->items->first();

        $options = $service->exchangeOptions($item, $tenantId);
        $this->assertSame([$b->id], array_column($options, 'id'));
        $this->assertSame(100.0, $options[0]['price']);
        $this->assertSame(1, $options[0]['stock']);
        $this->assertNull(tenant(), 'context restored');

        $eligible = $service->eligibleItems($order, $tenantId);
        $this->assertTrue($eligible[0]['returnable']);
        $this->assertSame(2, $eligible[0]['remaining']);
        $this->assertNull(tenant());

        $exchange = fn (ProductVariant $variant, int $qty = 1) => $this->payload($order, $customer, [
            'type' => 'exchange',
            'quantity' => $qty,
            'replacement_product_variant_id' => $variant->id,
        ]);

        // ── Refused: out of stock / different price / not enough stock ────
        $this->assertReturnError(fn () => $service->create($exchange($d)), ReturnActionException::OUT_OF_STOCK, 'Only 0 left of');
        $this->assertReturnError(fn () => $service->create($exchange($c)), ReturnActionException::OUT_OF_STOCK, 'different price');
        $this->assertReturnError(fn () => $service->create($exchange($b, 2)), ReturnActionException::OUT_OF_STOCK, 'Only 1 left of');
        $this->assertNull(tenant(), 'context restored after an exception');

        // ── Approve reserves, reject releases ─────────────────────────────
        $first = $service->create($exchange($b));
        $this->assertSame(ReturnType::Exchange, $first->type);
        $this->assertSame(1, $first->replacement_quantity);
        $this->assertSame(['approve', 'reject', 'request_info', 'convert_to_refund'], $service->availableActions($first, 'vendor'));

        $first = $service->approve($first, $vendor);
        $this->assertNotNull($first->replacement_reserved_at);
        $this->assertSame(0, $this->stockOf($b));

        $first = $service->reject($first, 'Wrong item sent back.', $vendor);
        $this->assertNull($first->replacement_reserved_at);
        $this->assertSame(1, $this->stockOf($b));
        $first = $service->close($first, $vendor);
        $this->assertSame(1, $this->stockOf($b), 'release is idempotent');

        // ── Full exchange: approve → receive → inspect → shipped → exchanged ─
        $second = $service->approve($service->create($exchange($b)), $vendor);
        $this->assertSame(0, $this->stockOf($b));
        $second = $service->markItemReceived($second, $vendor);
        $second = $service->inspect($second, 'passed', null, true, $vendor);
        $this->assertSame(11, $this->stockOf($fixture['a']), 'returned unit restocked');
        $this->assertSame(['reject', 'mark_exchange_shipped', 'convert_to_refund'], $service->availableActions($second, 'admin'));
        $this->assertReturnError(fn () => $service->issueRefund($second, null, $vendor), ReturnActionException::NOT_ALLOWED);
        $this->assertReturnError(fn () => $service->markExchangeShipped($second, ' ', $vendor), ReturnActionException::VALIDATION);

        $second = $service->markExchangeShipped($second, 'TRK-123', $vendor);
        $this->assertSame(ReturnStatus::ExchangeShipped, $second->status);
        $this->assertSame('TRK-123', $second->exchange_tracking_number);
        $this->assertSame(['mark_exchange_completed'], $service->availableActions($second, 'vendor'));
        $this->assertReturnError(fn () => $service->convertToRefund($second, $vendor), ReturnActionException::NOT_ALLOWED);

        $second = $service->markExchangeCompleted($second, $vendor);
        $this->assertSame(ReturnStatus::Exchanged, $second->status);
        $this->assertNotNull($second->exchange_completed_at);
        $service->close($second, $vendor);
        $this->assertSame(0, $this->stockOf($b), 'a shipped replacement is never released');
        $this->mail->shouldHaveReceived('sendReturnStatusUpdate')
            ->withArgs(fn (ReturnRequest $r, Order $o, ?string $message) => $r->id === $second->id && str_contains((string) $message, 'TRK-123'))
            ->once();

        // ── Convert to refund releases the reservation ────────────────────
        $this->assertReturnError(fn () => $service->create($exchange($b)), ReturnActionException::OUT_OF_STOCK, 'Only 0 left of');
        $this->tenant->run(fn () => $b->refresh()->update(['stock' => 3]));

        $third = $service->approve($service->create($exchange($b)), $vendor);
        $this->assertSame(2, $this->stockOf($b));
        $third = $service->convertToRefund($third, $vendor);
        $this->assertSame(ReturnType::Return, $third->type);
        $this->assertSame(ReturnStatus::Approved, $third->status);
        $this->assertNull($third->replacement_reserved_at);
        $this->assertSame(3, $this->stockOf($b));
        $this->assertSame(['reject', 'mark_received'], $service->availableActions($third, 'vendor'));
        $this->assertReturnError(fn () => $service->convertToRefund($third, $vendor), ReturnActionException::NOT_ALLOWED);

        $this->tenant->run(function () use ($order): void {
            foreach (['Exchange requested', 'Exchange shipped', 'Exchange completed', 'Exchange converted to refund'] as $title) {
                $this->assertActivity($order, $title);
            }
        });
        $this->assertNull(tenant());
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function deliveredOrder(Customer $customer, ProductVariant $variant, int $qty, bool $paid = true): Order
    {
        $order = $this->createOrder($customer, [[$variant, $qty]], OrderStatus::Delivered, paid: $paid, stockDeducted: $paid);
        $order->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);

        return $order;
    }

    /** @return array<string, mixed> */
    private function payload(Order $order, Customer $customer, array $overrides = []): array
    {
        return array_merge([
            'tenant_id' => (string) $this->tenant->id,
            'order_number' => $order->uuid,
            'customer_id' => $customer->id,
            'order_item_id' => $order->items->first()->id,
            'quantity' => 1,
            'type' => 'return',
            'return_method' => 'ship_back',
            'reason' => 'changed_mind',
            'description' => null,
        ], $overrides);
    }

    private function stockOf(ProductVariant $variant): int
    {
        return (int) $this->tenant->run(fn () => ProductVariant::query()->whereKey($variant->id)->value('stock'));
    }

    private function assertActivity(Order $order, string $title, ?string $contains = null): void
    {
        $activity = OrderActivity::query()->where('order_id', $order->id)->where('title', $title)->latest('id')->first();

        $this->assertNotNull($activity, "Missing order activity [{$title}]");

        if ($contains !== null) {
            $this->assertStringContainsString($contains, (string) $activity->description);
        }
    }

    private function assertReturnError(\Closure $callback, string $reason, ?string $messageContains = null): void
    {
        try {
            $callback();
        } catch (ReturnActionException $e) {
            $this->assertSame($reason, $e->reason, $e->getMessage());

            if ($messageContains !== null) {
                $this->assertStringContainsString($messageContains, $e->getMessage());
            }

            return;
        }

        $this->fail("Expected a ReturnActionException [{$reason}].");
    }
}
