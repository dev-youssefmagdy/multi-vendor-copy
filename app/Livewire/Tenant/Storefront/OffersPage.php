<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Component;

class OffersPage extends Component
{
    use HasStorefrontLayout;
    use ChecksCartStock;

    public function addToCart(int $productId): void
    {
        $product = \App\Models\Tenant\Product::with(['variants', 'centralProduct'])
            ->where('active', true)
            ->find($productId);

        if (!$product) {
            return;
        }

        $cart = session('storefront_cart', []);
        $key = 'p_' . $productId;
        $existingQty = (int) ($cart[$key]['qty'] ?? 0);
        $activeVariant = $product->variants->where('active', true)->first();

        $stockError = $this->checkProductStock($product, $activeVariant, 1, $existingQty);
        if ($stockError !== null) {
            $this->dispatch('storefront-toast', message: $stockError, type: 'error');
            return;
        }

        $cart[$key] = isset($cart[$key])
            ? array_merge($cart[$key], ['qty' => $cart[$key]['qty'] + 1])
            : ['product_id' => $productId, 'qty' => 1];
        session(['storefront_cart' => $cart]);
        $this->dispatch('cartUpdated');
        $this->dispatch('storefront-cart-added');
    }

    public function render()
    {
        $repo = app(StorefrontRepository::class);

        $banners = $repo->activeBanners();
        $flashSales = $repo->activeFlashSales();
        $flashBanner = optional($flashSales->first())->banner_url;

        // Every section on this page is a genuine flash-sale offer — no fallback to
        // latest/best-selling products that aren't actually discounted.
        $allFlashProducts = $flashSales->flatMap(fn($fs) => $fs->products)->unique('id')->values();

        $flashDiscount = fn($product) => (float) optional($product->flashSales->first())->discount_percentage;
        $soldCount = fn($product) => (int) ($product->centralProduct?->sold_count ?? 0);

        // Mega Sale: the biggest active discounts.
        $megaSaleProducts = $allFlashProducts->sortByDesc($flashDiscount)->take(10)->values();
        $remaining = $allFlashProducts->reject(fn($p) => $megaSaleProducts->contains('id', $p->id));

        // Super Sale: the best-selling among the remaining flash offers.
        $superSaleProducts = $remaining->sortByDesc($soldCount)->take(10)->values();
        $remaining = $remaining->reject(fn($p) => $superSaleProducts->contains('id', $p->id));

        // Season Sale: whatever flash offers are left, most recent first.
        $seasonSaleProducts = $remaining->sortByDesc('created_at')->take(10)->values();

        // Real countdown target for the sale sections (null hides the timer instead
        // of showing a static, non-counting clock).
        $saleEndsAt = optional($flashSales->first())->end_date;
        $saleEndsAtIso = $saleEndsAt ? \Carbon\Carbon::parse($saleEndsAt)->toIso8601String() : null;

        $data = array_merge($this->sharedData(), [
            'banners' => $banners,
            'flashSales' => $flashSales,
            'flashBanner' => $flashBanner,
            'megaSaleProducts' => $megaSaleProducts,
            'seasonSaleProducts' => $seasonSaleProducts,
            'superSaleProducts' => $superSaleProducts,
            'saleEndsAtIso' => $saleEndsAtIso,
        ]);

        $storeName = $repo->storeName();

        return view($this->pageView('offers'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Offers') . " — {$storeName}" : __('Offers'),
                'metaDescription' => $storeName ? __('Browse the latest deals and offers at :store.', ['store' => $storeName]) : __('Browse the latest deals and offers.'),
                'canonicalUrl' => route('tenant.storefront.offers'),
            ]);
    }
}
