<?php

namespace App\Livewire\Tenant\Storefront;

use App\Enums\OrderStatus;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Livewire\Tenant\Storefront\Concerns\ManagesOrderCancellation;
use App\Models\ReturnRequest;
use App\Models\Tenant\Order;
use App\Models\Tenant\ProductRate;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\ReturnRequestService;
use App\Support\Tenant\Storefront\OrderAfterSalesPresenter;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrderStatusPage extends Component
{
    use HasStorefrontLayout;
    use ManagesOrderCancellation;

    public string $uuid = '';

    public string $mode = 'details';

    // ── Review modal state ───────────────────────────────────────────────────
    public bool $showReviewModal = false;

    public ?int $reviewProductId = null;

    public int $reviewStars = 5;

    public string $reviewComment = '';

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;

        $this->fireOrderConfirmationTracking();

        // ?cancel=1 (e.g. the "Cancel order" link of the tracking page / account order list)
        // opens the cancel modal right away when the customer may cancel this order.
        if (request()->boolean('cancel')) {
            $order = $this->customerOrderForCancellation($uuid);

            if ($order && $this->customerCancelDecision($order)->allowed) {
                $this->openCancelModal($uuid);
            }
        }
    }

    protected function cancellationToastEvent(): string
    {
        return 'order-status-swal';
    }

    /**
     * Fire the "purchase" tracking event once per order — guarded by a
     * session flag so refreshing/re-visiting the order-status page doesn't
     * double-count the conversion in Facebook/TikTok/Snapchat/GA.
     */
    protected function fireOrderConfirmationTracking(): void
    {
        $sessionKey = 'tracking_purchase_fired_'.$this->uuid;
        if (session()->has($sessionKey)) {
            return;
        }

        $order = app(StorefrontRepository::class)->orderByUuid($this->uuid);
        if (! $order) {
            return;
        }

        session()->put($sessionKey, true);

        $this->dispatch('tracking-event', name: 'purchase', params: [
            'content_ids' => $order->items->pluck('product_variant_id')->filter()->values()->all(),
            'content_type' => 'product',
            'num_items' => (int) $order->items->sum('qty'),
            'value' => (float) $order->grand_total,
            'order_id' => $order->uuid,
        ]);
    }

    public function showTracking(): void
    {
        $this->mode = 'tracking';
    }

    public function showDetails(): void
    {
        $this->mode = 'details';
    }

    // ── Review ───────────────────────────────────────────────────────────────

    public function openReviewModal(): void
    {
        $customer = Auth::guard('storefront')->user();

        if (! $customer) {
            $this->dispatch('order-status-swal', message: __('Please login to leave a review.'), type: 'error');

            return;
        }

        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        if (! $order) {
            return;
        }

        // Auto-select the first product
        if (! $this->orderIsDelivered($order)) {
            $this->dispatch('order-status-swal', message: __('You can rate a product once your order is delivered.'), type: 'error');

            return;
        }

        $this->reviewProductId = $order->items->first(fn ($i) => $i->catalog_product_key)?->catalog_product_key;
        $this->fillExistingReview($customer->id);
        $this->showReviewModal = true;
    }

    public function updatedReviewProductId(): void
    {
        $customer = Auth::guard('storefront')->user();

        if ($customer) {
            $this->fillExistingReview($customer->id);
        }
    }

    /** Prefill the modal with the customer's existing rating of the selected product (edit mode). */
    /** The customer's rating of a product by storefront key: typed pair in central mode, tenant id otherwise. */
    protected function rateQuery(int $key, int $customerId)
    {
        $ref = \App\Repositories\Tenant\Catalog\ProductCatalog::enabled()
            ? \App\Repositories\Tenant\Catalog\ProductCatalog::refColumnsForKey($key)
            : null;

        return ProductRate::where($ref
            ? ['product_type' => $ref['product_type'], 'catalog_product_id' => $ref['catalog_product_id']]
            : ['product_id' => $key])->where('customer_id', $customerId);
    }

    protected function rateKey(ProductRate $r): ?int
    {
        return \App\Repositories\Tenant\Catalog\ProductCatalog::enabled() && $r->product_key ? $r->product_key : ($r->product_id ? (int) $r->product_id : null);
    }

    protected function fillExistingReview(int $customerId): void
    {
        $existing = $this->reviewProductId
            ? $this->rateQuery((int) $this->reviewProductId, $customerId)->first()
            : null;

        $this->reviewStars = $existing ? (int) $existing->stars : 5;
        $this->reviewComment = $existing ? (string) $existing->comment : '';
    }

    protected function orderIsDelivered($order): bool
    {
        return in_array($order->status, [\App\Enums\OrderStatus::Delivered, \App\Enums\OrderStatus::Completed], true);
    }

    public function setReviewStars(int $stars): void
    {
        $this->reviewStars = max(1, min(5, $stars));
    }

    public function submitReview(): void
    {
        $customer = Auth::guard('storefront')->user();

        if (! $customer) {
            $this->dispatch('order-status-swal', message: __('Please login to leave a review.'), type: 'error');

            return;
        }

        $this->validate([
            'reviewProductId' => 'required|integer',
            'reviewStars' => 'required|integer|min:1|max:5',
            'reviewComment' => 'nullable|string|max:1000',
        ], [], [
            'reviewProductId' => __('product'),
            'reviewStars' => __('rating'),
            'reviewComment' => __('comment'),
        ]);

        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        $productIds = $order?->items->pluck('catalog_product_key')->filter()->map(fn ($k) => (int) $k)->all() ?? [];

        if (! in_array((int) $this->reviewProductId, $productIds, true)) {
            $this->dispatch('order-status-swal', message: __('Invalid product selected.'), type: 'error');

            return;
        }

        if (! $order || ! $this->orderIsDelivered($order)) {
            $this->showReviewModal = false;
            $this->dispatch('order-status-swal', message: __('You can rate a product once your order is delivered.'), type: 'error');

            return;
        }

        $existing = $this->rateQuery((int) $this->reviewProductId, $customer->id)->first();
        $refCols = \App\Repositories\Tenant\Catalog\ProductCatalog::enabled()
            ? \App\Repositories\Tenant\Catalog\ProductCatalog::refColumnsForKey((int) $this->reviewProductId)
            : null;

        if ($existing) {
            $existing->update(['stars' => $this->reviewStars, 'comment' => $this->reviewComment ?: null]);
            $this->showReviewModal = false;
            $this->dispatch('order-status-swal', message: __('Review updated successfully.'), type: 'success');

            return;
        }

        ProductRate::create(($refCols ?? ['product_id' => (int) $this->reviewProductId]) + [
            'customer_id' => $customer->id,
            'stars' => $this->reviewStars,
            'comment' => $this->reviewComment ?: null,
        ]);

        $this->showReviewModal = false;
        $this->dispatch('order-status-swal', message: __('Your review has been submitted. Thank you!'), type: 'success');
    }

    public function closeReviewModal(): void
    {
        $this->showReviewModal = false;
    }

    public function reorder(): void
    {
        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        if (! $order) {
            $this->dispatch('order-status-swal', message: __('Order not found.'), type: 'error');

            return;
        }

        $cart = session('storefront_cart', []);
        $addedQty = 0;

        foreach ($order->items as $item) {
            $pKey = $item->catalog_product_key;
            if (! $pKey) {
                continue;
            }
            $vKey = $item->catalog_variant_key;

            $addedQty += max(1, (int) $item->qty);

            $key = $vKey
                ? 'v_'.$vKey
                : 'p_'.$pKey;

            if (isset($cart[$key])) {
                $cart[$key]['qty'] += max(1, (int) $item->qty);
            } else {
                $cart[$key] = [
                    'product_id' => $pKey,
                    'variant_id' => $vKey ?: null,
                    'qty' => max(1, (int) $item->qty),
                ];
            }
        }

        if ($addedQty === 0) {
            $this->dispatch('order-status-swal', message: __('None of the items in this order can be added to the cart.'), type: 'error');

            return;
        }

        session(['storefront_cart' => $cart]);
        // Same events as the regular add-to-cart: `cartUpdated` refreshes Livewire cart widgets and
        // `storefront-cart-added` bumps the theme header/footer badges (elora.js, souqify scripts,
        // ecommet.js) by `qty` and shows the standard "added to cart" toast.
        $this->dispatch('cartUpdated');
        $this->dispatch('storefront-cart-added', itemName: '', qty: $addedQty);
    }

    public function render()
    {
        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);
        $customer = Auth::guard('storefront')->user();

        if (! $order) {
            abort(404);
        }

        $returnRequests = ReturnRequest::where('tenant_id', tenant()->id)
            ->where('order_number', $order->uuid)
            ->with('notes')
            ->latest('id')
            ->get();

        $returnService = app(ReturnRequestService::class);
        $returnWindowOpen = $returnService->isWithinReturnWindow(tenant()->id, $order->uuid);

        $isOwner = $customer && (int) $order->customer_id === (int) $customer->id;
        $cancelDecision = $this->customerCancelDecision($order);

        $data = array_merge($this->sharedData(), [
            'order' => $order,
            'myReviews' => $customer
                ? ProductRate::where('customer_id', $customer->id)->get(['product_id', 'product_type', 'catalog_product_id', 'stars', 'comment'])
                    ->mapWithKeys(fn ($r) => [$this->rateKey($r) => ['stars' => (int) $r->stars, 'comment' => (string) $r->comment]])
                    ->all()
                : [],
            'reviewedProductIds' => $customer
                ? ProductRate::where('customer_id', $customer->id)->get(['product_id', 'product_type', 'catalog_product_id'])->map(fn ($r) => $this->rateKey($r))->filter()->values()->toArray()
                : [],
            'returnRequests' => $returnRequests,
            'returnWindowOpen' => $returnWindowOpen,
            // After-sales (RETURN_EXCHANGE_REFUND_PLAN.md B.3.4 / B.8) — read by the shared partials
            // order-cancel-action, order-cancellation-summary, order-refunds-summary, return-item-action.
            'canCancel' => $isOwner && $cancelDecision->allowed,
            'cancelViaModal' => true,
            'cancelDecision' => $cancelDecision,
            'cancelReasons' => $this->customerCancellationReasons(),
            'cancellation' => OrderAfterSalesPresenter::cancellation($order),
            'refunds' => OrderAfterSalesPresenter::refundsFor($order),
            'paymentState' => $order->paymentState(),
            'returnItems' => $this->returnItems($order, $returnService),
        ]);

        $viewName = $this->mode === 'tracking' ? 'order-tracking' : 'order-status';

        $storeName = $repo->storeName();

        return view($this->pageView($viewName), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Order Status')." — {$storeName}" : __('Order Status'),
                'metaDescription' => '',
            ]);
    }

    /**
     * Per order line: units left to return and whether a new request is possible (keyed by
     * order item id), so return-item-action doesn't query per item. Only delivered orders can be
     * returned, so other statuses skip the lookups.
     *
     * @return array<int, array{remaining: int, returnable: bool, has_open_request: bool, errors: list<string>}>
     */
    private function returnItems(Order $order, ReturnRequestService $service): array
    {
        if (! $order->status instanceof OrderStatus || ! $order->status->isDelivered()) {
            return [];
        }

        $rows = [];

        foreach ($service->eligibleItems($order, (string) tenant('id'), withExchangeOptions: false) as $row) {
            $rows[$row['order_item_id']] = [
                'remaining' => $row['remaining'],
                'returnable' => $row['returnable'],
                'has_open_request' => $row['has_open_request'],
                'errors' => $row['errors'],
            ];
        }

        return $rows;
    }
}
