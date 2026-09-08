<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\ReturnRequest;
use App\Models\Tenant\ProductRate;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\ReturnRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrderStatusPage extends Component
{
    use HasStorefrontLayout;

    public string $uuid = '';
    public string $mode = 'details';

    // ── Review modal state ───────────────────────────────────────────────────
    public bool   $showReviewModal  = false;
    public ?int   $reviewProductId  = null;
    public int    $reviewStars      = 5;
    public string $reviewComment    = '';

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;

        $this->fireOrderConfirmationTracking();
    }

    /**
     * Fire the "purchase" tracking event once per order — guarded by a
     * session flag so refreshing/re-visiting the order-status page doesn't
     * double-count the conversion in Facebook/TikTok/Snapchat/GA.
     */
    protected function fireOrderConfirmationTracking(): void
    {
        $sessionKey = 'tracking_purchase_fired_' . $this->uuid;
        if (session()->has($sessionKey)) {
            return;
        }

        $order = app(StorefrontRepository::class)->orderByUuid($this->uuid);
        if (!$order) {
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

        if (!$customer) {
            $this->dispatch('order-status-swal', message: __('Please login to leave a review.'), type: 'error');
            return;
        }

        $repo  = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        if (!$order) {
            return;
        }

        // Auto-select the first product
        $this->reviewProductId = $order->items->first()?->product_id;
        $this->reviewStars     = 5;
        $this->reviewComment   = '';
        $this->showReviewModal = true;
    }

    public function setReviewStars(int $stars): void
    {
        $this->reviewStars = max(1, min(5, $stars));
    }

    public function submitReview(): void
    {
        $customer = Auth::guard('storefront')->user();

        if (!$customer) {
            $this->dispatch('order-status-swal', message: __('Please login to leave a review.'), type: 'error');
            return;
        }

        $this->validate([
            'reviewProductId' => 'required|integer',
            'reviewStars'     => 'required|integer|min:1|max:5',
            'reviewComment'   => 'nullable|string|max:1000',
        ]);

        $repo  = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        $productIds = $order?->items->pluck('product_id')->filter()->toArray() ?? [];

        if (!in_array($this->reviewProductId, $productIds)) {
            $this->dispatch('order-status-swal', message: __('Invalid product selected.'), type: 'error');
            return;
        }

        if (ProductRate::where('product_id', $this->reviewProductId)->where('customer_id', $customer->id)->exists()) {
            $this->showReviewModal = false;
            $this->dispatch('order-status-swal', message: __('You have already reviewed this product.'), type: 'error');
            return;
        }

        ProductRate::create([
            'product_id'  => $this->reviewProductId,
            'customer_id' => $customer->id,
            'stars'       => $this->reviewStars,
            'comment'     => $this->reviewComment ?: null,
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

        if (!$order) {
            $this->dispatch('order-status-swal', message: __('Order not found.'), type: 'error');
            return;
        }

        $cart = session('storefront_cart', []);

        foreach ($order->items as $item) {
            if (!$item->product_id) {
                continue;
            }

            $key = $item->product_variant_id
                ? 'v_' . $item->product_variant_id
                : 'p_' . $item->product_id;

            if (isset($cart[$key])) {
                $cart[$key]['qty'] += max(1, (int) $item->qty);
            } else {
                $cart[$key] = [
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id ?: null,
                    'qty'        => max(1, (int) $item->qty),
                ];
            }
        }

        session(['storefront_cart' => $cart]);
        $this->dispatch('cartUpdated');
        $this->dispatch('order-status-swal', message: __('Items added to your cart.'), type: 'success');
    }

    public function render()
    {
        $repo     = app(StorefrontRepository::class);
        $order    = $repo->orderByUuid($this->uuid);
        $customer = Auth::guard('storefront')->user();

        if (!$order) {
            abort(404);
        }

        $returnRequests = ReturnRequest::where('tenant_id', tenant()->id)
            ->where('order_number', $order->uuid)
            ->with('notes')
            ->get();

        $returnWindowOpen = app(ReturnRequestService::class)->isWithinReturnWindow(tenant()->id, $order->uuid);

        $data = array_merge($this->sharedData(), [
            'order'              => $order,
            'reviewedProductIds' => $customer
                ? ProductRate::where('customer_id', $customer->id)->pluck('product_id')->toArray()
                : [],
            'returnRequests'     => $returnRequests,
            'returnWindowOpen'   => $returnWindowOpen,
        ]);

        $viewName = $this->mode === 'tracking' ? 'order-tracking' : 'order-status';

        $storeName = $repo->storeName();

        return view($this->pageView($viewName), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Order Status') . " — {$storeName}" : __('Order Status'),
                'metaDescription' => '',
            ]);
    }
}
