<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Livewire\Tenant\Storefront\OrderStatusPage;
use App\Livewire\Tenant\Storefront\OrderTrackingPage;
use App\Livewire\Tenant\Storefront\ProfilePage;
use App\Livewire\Tenant\Storefront\RequestReturnForm;
use App\Livewire\Tenant\Storefront\ReturnDetailPage;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\ProductVariant;
use App\Services\Mail\TemplateMailService;
use App\Services\Orders\OrderCancellationService;
use App\Services\Refunds\RefundActor;
use App\Services\ReturnRequestService;
use App\Services\Tenant\TemplateRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Phase 5: storefront after-sales UI (cancel modal, cancellation / refund summaries, return form,
 * return detail) through the real Livewire components, in every built-in theme. Consolidated into
 * two methods because each tenant boot is expensive.
 */
class StorefrontAfterSalesUiTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private const THEMES = ['ecommet', 'elora', 'souqify'];

    /** A bit of markup only that theme's order page has (proves the forced theme rendered). */
    private const THEME_MARKERS = [
        'ecommet' => 'max-w-[1100px]',
        'elora' => 'detail-layout',
        'souqify' => 'bg-zinc-100',
    ];

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        // The storefront repository caches on the `file` store: keep that out of the shared
        // storage/framework/cache directory (whose sub-directories may belong to the web server).
        config(['cache.stores.file.path' => sys_get_temp_dir().'/storefront-after-sales-ui-test-cache']);
        Cache::forgetDriver('file');

        $this->setUpTenantPanel();

        Bus::fake([SyncCentralProductStockToTenantsJob::class]);
        $this->gateway = FakePaymentGateway::install('stripe');
        $this->spy(TemplateMailService::class);

        // Storefront components read the session (cart, country detection). Livewire::test()
        // renders through the router with middleware disabled, so attach one to every request.
        $session = $this->app['session']->driver();
        $session->start();
        Event::listen(RouteMatched::class, fn (RouteMatched $event) => $event->request->setLaravelSession($session));
    }

    protected function tearDown(): void
    {
        TemplateRegistryService::clearForce();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    #[Test]
    public function order_page_cancel_flow_notices_and_every_status_renders_in_all_themes(): void
    {
        $this->tenant->run(function (): void {
            $customer = $this->createCustomer();
            $variant = $this->createOwnProductVariant(stock: 20, price: 50.0);
            $this->actingAs($customer, 'storefront');

            // ── Cancel flow: reason validation → confirm step → cancelled banner ─────────
            $pending = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Pending);

            $page = Livewire::test(OrderStatusPage::class, ['uuid' => $pending->uuid])
                ->assertSeeHtml("openCancelModal('{$pending->uuid}')")
                ->call('openCancelModal', $pending->uuid)
                ->assertSet('cancelStep', 1)
                ->assertSee(__('Reason for cancelling'))
                ->call('continueCancel')
                ->assertHasErrors(['cancelReason' => 'required'])
                ->assertSet('cancelStep', 1)
                ->set('cancelReason', CancellationReason::OutOfStock->value) // staff-only reason
                ->call('continueCancel')
                ->assertHasErrors(['cancelReason' => 'in'])
                ->set('cancelReason', CancellationReason::Other->value)
                ->call('continueCancel')
                ->assertHasErrors(['cancelNote' => 'required'])
                ->set('cancelNote', 'Bought it in a shop nearby')
                // Confirming from step 1 only shows the confirmation — nothing is cancelled yet.
                ->call('confirmCancelOrder')
                ->assertHasNoErrors()
                ->assertSet('cancelStep', 2)
                ->assertSee(__('Are you sure you want to cancel order #:order? This cannot be undone.', ['order' => $pending->uuid]));

            $this->assertSame(OrderStatus::Pending, $pending->refresh()->status, 'not cancelled before the confirm step');

            $page->call('backToCancelReason')
                ->assertSet('cancelStep', 1)
                ->call('continueCancel')
                ->assertSet('cancelStep', 2)
                ->call('confirmCancelOrder')
                ->assertDispatched('order-status-swal', message: __('Your order has been cancelled.'), type: 'success')
                ->assertSet('cancelStep', 0);

            $pending->refresh();
            $this->assertSame(OrderStatus::Cancelled, $pending->status);
            $this->assertSame(CancellationReason::Other, $pending->cancellation_reason);
            $this->assertSame('Bought it in a shop nearby', $pending->cancellation_note);
            $this->assertSame(CancellationActor::Customer, $pending->cancelled_by_type);

            $page->assertSee(__('This order was cancelled'))
                ->assertSee(CancellationReason::Other->label())
                ->assertSee('Bought it in a shop nearby')
                ->assertSee($pending->cancelled_at->translatedFormat('M j, Y H:i'))
                ->assertSee(CancellationActor::Customer->customerFacingLabel())
                ->assertDontSeeHtml('openCancelModal(');

            // A second attempt is refused with the policy message (no service call).
            $page->call('openCancelModal', $pending->uuid)
                ->assertSet('cancelStep', 0)
                ->assertDispatched('order-status-swal', message: __('This order is already cancelled.'), type: 'warning');

            // Someone else's order can't be cancelled from this page.
            $stranger = $this->createOrder($this->createCustomer(), [[$variant, 1]], OrderStatus::Pending);
            Livewire::test(OrderStatusPage::class, ['uuid' => $stranger->uuid])
                ->assertDontSeeHtml('openCancelModal(')
                ->call('openCancelModal', $stranger->uuid)
                ->assertSet('cancelStep', 0);
            $this->assertSame(OrderStatus::Pending, $stranger->refresh()->status);

            // ?cancel=1 (tracking page / order lists without the modal) opens the modal right away.
            $deepLinked = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Pending);
            Livewire::withQueryParams(['cancel' => 1])
                ->test(OrderStatusPage::class, ['uuid' => $deepLinked->uuid])
                ->assertSet('cancelStep', 1)
                ->assertSet('cancelOrderUuid', $deepLinked->uuid);

            // ── Shipped: info note, no cancel button, "Return after delivery" ────────────
            $shipped = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Shipped);
            Livewire::test(OrderStatusPage::class, ['uuid' => $shipped->uuid])
                ->assertSee(__('Already shipped — you can request a return after delivery.'))
                ->assertSee(__('Return after delivery'))
                ->assertDontSeeHtml('openCancelModal(')
                ->call('openCancelModal', $shipped->uuid)
                ->assertSet('cancelStep', 0);

            // ── Profile: the order list opens the same modal; no silent default reason ───
            // (Ecommet's account list has no cancel button; Elora / Souqify do.)
            TemplateRegistryService::force('elora');
            $fromProfile = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Processing);
            Livewire::test(ProfilePage::class)
                ->assertSeeHtml("openCancelModal('{$fromProfile->uuid}')")
                ->call('cancelOrder', $fromProfile->uuid)          // legacy call without a reason
                ->assertSet('cancelStep', 1)
                ->assertSet('cancelOrderUuid', $fromProfile->uuid)
                ->set('cancelReason', CancellationReason::ChangedMind->value)
                ->call('continueCancel')
                ->call('confirmCancelOrder')
                ->assertDispatched('profile-swal', message: __('Your order has been cancelled.'), type: 'success');
            $this->assertSame(CancellationReason::ChangedMind, $fromProfile->refresh()->cancellation_reason);
            TemplateRegistryService::clearForce();

            // ── Every status renders in every theme ──────────────────────────────────────
            $orders = $this->ordersInEveryStatus($customer, $variant);

            foreach (self::THEMES as $theme) {
                TemplateRegistryService::force($theme);

                foreach ($orders as $label => $order) {
                    $component = Livewire::test(OrderStatusPage::class, ['uuid' => $order->uuid])
                        ->assertOk()
                        ->assertSee($order->uuid)
                        ->assertSeeHtml(self::THEME_MARKERS[$theme]);

                    match ($label) {
                        'pending', 'processing' => $component->assertSeeHtml("openCancelModal('{$order->uuid}')"),
                        'shipped' => $component->assertSee(__('Already shipped — you can request a return after delivery.')),
                        'delivered' => $component->assertSee(__('Request Return'))->assertDontSeeHtml('openCancelModal('),
                        'cancelled_refunded' => $component
                            ->assertSee(__('This order was cancelled'))
                            ->assertSee(CancellationReason::OrderedByMistake->label())
                            ->assertSee($order->refundsQuery()->value('reference'))
                            ->assertSee(RefundStatus::Completed->label()),
                        'refunded' => $component
                            ->assertSee(__('This order has been refunded'))
                            ->assertSee('RF-UI-REFUNDED')
                            ->assertDontSee(__('Request Return')),
                    };

                    // Tracking mode of the same component + the standalone tracking page.
                    $component->call('showTracking')->assertOk();
                    Livewire::test(OrderTrackingPage::class, ['uuid' => $order->uuid])->assertOk();
                }

                $profile = Livewire::test(ProfilePage::class)->assertOk();

                if ($theme !== 'ecommet') {
                    $profile->assertSeeHtml("openCancelModal('{$orders['pending']->uuid}')")
                        ->assertDontSeeHtml("openCancelModal('{$orders['shipped']->uuid}')")
                        ->call('openCancelModal', $orders['pending']->uuid)
                        ->assertSee(__('Reason for cancelling'))
                        ->call('closeCancelModal')
                        ->assertSet('cancelStep', 0);
                }
            }
        });
    }

    #[Test]
    public function return_form_exchange_validation_withdraw_and_reply(): void
    {
        $this->tenant->run(function (): void {
            $customer = $this->createCustomer();
            $variant = $this->createOwnProductVariant(stock: 10, price: 100.0, variantAttributes: ['sku' => 'SIZE-M']);
            $replacement = $this->createVariantFor($variant->product, stock: 4, price: 100.0, attributes: ['sku' => 'SIZE-L']);
            $order = $this->createOrder($customer, [[$variant, 2]], OrderStatus::Delivered, paid: true, stockDeducted: true);
            $order->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);
            $item = $order->items->first();
            $this->actingAs($customer, 'storefront');

            // Order page: the item can be returned, nothing cancellable.
            Livewire::test(OrderStatusPage::class, ['uuid' => $order->uuid])
                ->assertSee(__('Request Return'))
                ->assertDontSeeHtml('openCancelModal(');

            $form = Livewire::withQueryParams(['item' => $item->id])
                ->test(RequestReturnForm::class, ['uuid' => $order->uuid])
                ->assertOk()
                ->assertSet('quantity', 2)
                ->assertSee(__('Quantity to return'))
                ->assertSee(__('Exchange'))                       // exchange offered: same-price variant in stock
                ->call('incrementQuantity')
                ->assertSet('quantity', 2)                         // capped at the remaining units
                ->call('decrementQuantity')
                ->assertSet('quantity', 1)
                ->call('decrementQuantity')
                ->assertSet('quantity', 1);

            // Required fields (method + reason) are validated before anything is created.
            $form->call('submit')->assertHasErrors(['reason' => 'required', 'returnMethod' => 'required']);

            // Live refund estimate for a refund-type request.
            $form->set('returnMethod', 'courier_pickup')
                ->set('reason', 'changed_mind')
                ->assertSee(__('Estimated refund'))
                ->assertSee('100.00');

            // Seller-fault reason: photos + description are required (shared rules).
            $form->set('reason', 'defective')
                ->set('description', 'Broken zipper')
                ->call('submit')
                ->assertNoRedirect();
            $this->assertContains(__('At least one photo is required as evidence.'), $form->get('validationErrors'));
            $this->assertSame(0, ReturnRequest::query()->where('order_number', $order->uuid)->count());

            // Exchange needs a replacement option.
            $form->set('reason', 'size_or_fit')
                ->set('type', 'exchange')
                ->assertSee(__('Replacement option'))
                ->assertSee(__(':count in stock', ['count' => 4]))
                ->call('submit')
                ->assertHasErrors(['replacementVariantId' => 'required_if']);

            // Exchange without photos (not seller fault) succeeds.
            $form->set('replacementVariantId', $replacement->id)
                ->set('customerNote', 'Please pick it up in the morning')
                ->call('submit')
                ->assertHasNoErrors()
                ->assertRedirect(route('tenant.storefront.order-status', ['uuid' => $order->uuid]));

            /** @var ReturnRequest $exchange */
            $exchange = ReturnRequest::query()->where('order_number', $order->uuid)->sole();
            $this->assertSame(ReturnType::Exchange, $exchange->type);
            $this->assertSame(1, $exchange->quantity);
            $this->assertSame($item->id, (int) $exchange->order_item_id);
            $this->assertSame($replacement->id, (int) $exchange->replacement_product_variant_id);
            $this->assertSame('courier_pickup', $exchange->return_method->value);
            $this->assertSame('Please pick it up in the morning', $exchange->customer_note);
            $this->assertSame(0, $exchange->media()->count());

            // Order page links to the open request; the item has an open request → no new one.
            Livewire::test(OrderStatusPage::class, ['uuid' => $order->uuid])
                ->assertSeeHtml(route('tenant.storefront.return-detail', $exchange->id))
                ->assertDontSeeHtml(route('tenant.storefront.order-return', ['uuid' => $order->uuid, 'item' => $item->id]));

            // ── Return detail: timeline, exchange details, withdraw with confirmation ────
            $detail = Livewire::test(ReturnDetailPage::class, ['id' => $exchange->id])
                ->assertOk()
                ->assertSee(__('Exchange Request #:id', ['id' => $exchange->id]))
                ->assertSee(__('Request submitted'))
                ->assertSee(__('Replacement shipped'))
                ->assertSee('SIZE-L')
                ->assertSee(__('Courier pickup'))
                ->assertSee(__('Withdraw request'))
                ->call('withdraw')                                 // first call only asks for confirmation
                ->assertSet('confirmingWithdraw', true)
                ->assertSee(__('Are you sure you want to withdraw this request? This cannot be undone.'));
            $this->assertSame(ReturnStatus::Pending, $exchange->refresh()->status);

            $detail->call('cancelWithdraw')
                ->assertSet('confirmingWithdraw', false)
                ->call('askWithdraw')
                ->call('withdraw')
                ->assertDispatched('return-detail-swal', message: __('Your return request has been withdrawn.'), type: 'success')
                ->assertSee(__('Withdrawn'))
                ->assertDontSee(__('Withdraw request'));
            $this->assertSame(ReturnStatus::Cancelled, $exchange->refresh()->status);

            // ── Reply to an info request ─────────────────────────────────────────────────
            $service = app(ReturnRequestService::class);
            $request = $service->create([
                'tenant_id' => (string) $this->tenant->id,
                'order_number' => $order->uuid,
                'customer_id' => $customer->id,
                'order_item_id' => $item->id,
                'quantity' => 2,
                'type' => 'return',
                'return_method' => 'drop_off',
                'reason' => 'changed_mind',
            ]);
            $request = $service->requestMoreInfo($request, 'Is the original box included?', RefundActor::vendor($this->admin->id));

            Livewire::test(ReturnDetailPage::class, ['id' => $request->id])
                ->assertSee('Is the original box included?')
                ->assertSee(__('The team has requested more information. Please reply below.'))
                ->set('replyText', 'short')
                ->call('submitReply')
                ->assertHasErrors(['replyText' => 'min'])
                ->set('replyText', 'Yes, the original box is included.')
                ->call('submitReply')
                ->assertHasNoErrors()
                ->assertDispatched('return-detail-swal', message: __('Your reply has been submitted.'), type: 'success')
                ->assertDontSee(__('The team has requested more information. Please reply below.'));
            $this->assertSame(ReturnStatus::Pending, $request->refresh()->status);

            // ── The return pages render in every theme ───────────────────────────────────
            foreach (self::THEMES as $theme) {
                TemplateRegistryService::force($theme);

                Livewire::withQueryParams(['item' => $item->id])
                    ->test(RequestReturnForm::class, ['uuid' => $order->uuid])
                    ->assertOk()
                    ->assertSee(__('How will you return the item?'));

                Livewire::test(ReturnDetailPage::class, ['id' => $request->id])
                    ->assertOk()
                    ->assertSee(__('Return Request #:id', ['id' => $request->id]))
                    ->assertSee(__('Progress'));

                Livewire::test(OrderStatusPage::class, ['uuid' => $order->uuid])->assertOk();
            }
        });
    }

    /** @return array<string, Order> label => order */
    private function ordersInEveryStatus(Customer $customer, ProductVariant $variant): array
    {
        $delivered = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Delivered, paid: true, stockDeducted: true);
        $delivered->activities()->create(['status' => 'delivered', 'title' => 'Delivered', 'description' => 'Delivered']);

        // Paid processing order cancelled by the customer → cancellation refund through the fake gateway.
        $cancelled = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Processing, paid: true, stockDeducted: true);
        app(OrderCancellationService::class)->cancel($cancelled, CancellationActor::Customer, $customer->id, CancellationReason::OrderedByMistake);
        $this->assertSame(RefundStatus::Completed, $cancelled->refundsQuery()->first()?->status);

        // Fully refunded order (as RefundService leaves it).
        $refunded = $this->createOrder($customer, [[$variant, 1]], OrderStatus::Delivered, paid: true, stockDeducted: true);
        Refund::create([
            'reference' => 'RF-UI-REFUNDED',
            'tenant_id' => (string) $this->tenant->id,
            'order_number' => $refunded->uuid,
            'source' => RefundSource::Manual,
            'reason' => 'Goodwill',
            'currency' => 'USD',
            'items_amount' => 50,
            'amount' => 50,
            'payment_method' => 'stripe',
            'refund_method' => RefundMethod::OriginalPayment,
            'status' => RefundStatus::Completed,
            'requested_at' => now(),
            'processed_at' => now(),
        ]);
        $refunded->update(['status' => OrderStatus::Refunded, 'refunded_amount' => 50, 'refunded_at' => now()]);

        return [
            'pending' => $this->createOrder($customer, [[$variant, 1]], OrderStatus::Pending),
            'processing' => $this->createOrder($customer, [[$variant, 1]], OrderStatus::Processing, paid: true, stockDeducted: true),
            'shipped' => $this->createOrder($customer, [[$variant, 1]], OrderStatus::Shipped, paid: true, stockDeducted: true),
            'delivered' => $delivered,
            'cancelled_refunded' => $cancelled->refresh(),
            'refunded' => $refunded->refresh(),
        ];
    }
}
