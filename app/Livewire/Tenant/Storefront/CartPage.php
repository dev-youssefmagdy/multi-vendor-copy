<?php

namespace App\Livewire\Tenant\Storefront;

use App\Enums\DeliveryScope;
use App\Enums\ShippingZoneStatus;
use App\Enums\Tenant\CouponType;
use App\Livewire\Tenant\Storefront\Concerns\CalculatesFreeShipping;
use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\Product as CentralProduct;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\CustomerCountryResolver;
use Livewire\Component;

class CartPage extends Component
{
    use HasStorefrontLayout;
    use ChecksCartStock;
    use CalculatesFreeShipping;

    public string $couponCode = '';

    public function mount(): void
    {
        // Restore applied coupon code from session so the UI reflects it on reload
        $this->couponCode = '';
    }

    // ─── Cart Mutations ──────────────────────────────────────────────────────

    public function addToCart(int $productId): void
    {
        $product = Product::with(['variants', 'centralProduct'])->where('active', true)->find($productId);
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
        $this->dispatch('tracking-event', name: 'add_to_cart', params: [
            'content_ids' => [$product->id],
            'content_name' => $product->translationValue('name') ?? $product->slug,
            'content_type' => 'product',
            'value' => $product->storefrontPricing()['current_price'] ?? null,
        ]);
    }

    public function removeFromCart(string $key): void
    {
        $cart = session('storefront_cart', []);
        if (array_key_exists($key, $cart)) {
            unset($cart[$key]);
            session(['storefront_cart' => $cart]);
            $this->toast(__('Item removed from your cart.'), 'success');
        }
        $this->dispatch('cartUpdated');
    }

    public function updateQty(string $key, int $qty): void
    {
        if ($qty < 1) {
            $this->removeFromCart($key);
            return;
        }

        $cart = session('storefront_cart', []);
        if (!array_key_exists($key, $cart)) {
            return;
        }

        // Stock check: qty here is the new total, not a delta
        [$product, $variant] = $this->resolveCartEntryModels($key, $cart[$key]);
        if ($product) {
            $stockError = $this->checkProductStock($product, $variant, $qty, 0);
            if ($stockError !== null) {
                $this->dispatch('storefront-toast', message: $stockError, type: 'error');
                return;
            }
        }

        $cart[$key]['qty'] = $qty;
        session(['storefront_cart' => $cart]);
        $this->toast(__('Cart quantity updated.'), 'success');
        $this->dispatch('cartUpdated');
    }

    // ─── Coupon ──────────────────────────────────────────────────────────────

    public function applyCoupon(): void
    {
        $code = trim($this->couponCode);

        if ($code === '') {
            $this->addError('coupon', __('Please enter a coupon code.'));
            $this->toast(__('Please enter a coupon code.'), 'error');
            return;
        }

        $coupon = Coupon::query()
            ->where('code', $code)
            ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->first();

        if (!$coupon) {
            $this->addError('coupon', __('This coupon code is invalid or has expired.'));
            $this->toast(__('This coupon code is invalid or has expired.'), 'error');
            return;
        }

        $cartTotal = app(StorefrontRepository::class)->cartTotal();

        if ($coupon->minimum_spend !== null && $cartTotal < (float) $coupon->minimum_spend) {
            $minimumSpend = number_format((float) $coupon->minimum_spend, 2);
            $message = __('This coupon requires a minimum spend of :amount.', ['amount' => $minimumSpend]);
            $this->addError('coupon', $message);
            $this->toast($message, 'warning');
            return;
        }

        session(['storefront_coupon' => $coupon->code]);
        $this->reset('couponCode');
        $this->resetErrorBag('coupon');
        $this->toast(__('Coupon applied successfully.'), 'success');
    }

    public function removeCoupon(): void
    {
        session()->forget('storefront_coupon');
        $this->reset('couponCode');
        $this->resetErrorBag('coupon');
        $this->toast(__('Coupon removed successfully.'), 'success');
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Estimate shipping cost using the same zone/rate logic as CheckoutPage.
     * Resolves the country via the customer's default address, else the
     * session-detected country; returns 0.0 when it cannot be determined or
     * no matching rate exists for that zone.
     */
    private function estimateShippingCost(array $cartItems, float $totalWeightKg): float
    {
        if (empty($cartItems)) {
            return 0.0;
        }

        // Only count items that require physical shipping (same filter as CheckoutPage)
        $shippableWeight = (float) collect($cartItems)->sum(function (array $item) {
            $centralProduct = $item['product']?->centralProduct;
            if (
                !$centralProduct instanceof CentralProduct
                || !(bool) $centralProduct->requires_shipping
                || $centralProduct->delivery_scope === DeliveryScope::Digital
            ) {
                return 0.0;
            }

            return $this->cartItemWeightKg($item);
        });
        //        dd($shippableWeight);
        if ($shippableWeight <= 0) {
            return 0.0;
        }

        $countryId = app(CustomerCountryResolver::class)->resolveId();

        if (!$countryId) {
            return 0.0;
        }

        $zone = \App\Models\ShippingZone::query()
            ->where('status', ShippingZoneStatus::Active)
            ->where('country_id', $countryId)
            ->with(['rates' => fn($q) => $q->where('is_active', true)->orderBy('min_weight')->orderBy('max_weight')])
            ->first();

        if (!$zone) {
            return 0.0;
        }

        $shippableGrams = $shippableWeight * 1000;

        $rate = $zone->rates->first(function ($zoneRate) use ($shippableGrams): bool {
            $min = $zoneRate->min_weight !== null ? (float) $zoneRate->min_weight : null;
            $max = $zoneRate->max_weight !== null ? (float) $zoneRate->max_weight : null;

            return ($min === null || $shippableGrams >= $min)
                && ($max === null || $shippableGrams <= $max);
        });

        return $rate ? round((float) $rate->price, 2) : 0.0;
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    public function render()
    {
        $repo = app(StorefrontRepository::class);
        $cartItems = $repo->cartItems();
        $cartTotal = $repo->cartTotal();

        // Resolve applied coupon
        $appliedCoupon = null;
        $couponCode = session('storefront_coupon');
        if ($couponCode) {
            $appliedCoupon = Coupon::query()->where('code', $couponCode)->first();
            if ($appliedCoupon && $appliedCoupon->minimum_spend !== null && $cartTotal < (float) $appliedCoupon->minimum_spend) {
                $minimumSpend = number_format((float) $appliedCoupon->minimum_spend, 2);
                session()->forget('storefront_coupon');
                $this->reset('couponCode');
                $this->addError('coupon', __('Coupon removed: your cart total is below the minimum spend of :amount.', ['amount' => $minimumSpend]));
                $this->toast(__('Coupon removed: your cart total is below the minimum spend of :amount.', ['amount' => $minimumSpend]), 'error');
                $appliedCoupon = null;
            }
        }

        // Compute discount
        $cartDiscount = 0.0;
        if ($appliedCoupon) {
            $cartDiscount = match ($appliedCoupon->type) {
                CouponType::Percentage => $cartTotal * (float) $appliedCoupon->value / 100,
                CouponType::Fixed => min((float) $appliedCoupon->value, $cartTotal),
            };
        }

        $cartFinalTotal = max(0.0, $cartTotal - $cartDiscount);

        // Cart weight — mirrors CheckoutPage::resolveCartItemWeight() (kg per item, grams for the progress bar)
        $cartWeight = $this->cartWeightGrams($cartItems);
        $cartWeightKg = $cartWeight / 1000;

        $shippingThreshold = $this->detectFreeShippingThreshold();

        // Shipping cost — zone-based lookup matching CheckoutPage logic, country resolved via
        // customer default address, else session
        $shippingCost = $this->estimateShippingCost($cartItems, $cartWeightKg);
        // $cartTax = $cartFinalTotal * 0.08;
        $cartTax = $cartFinalTotal * 0;
        $orderTotal = $cartFinalTotal + $shippingCost + $cartTax;

        // Highest active flash-sale discount, shown as decorative text on the free-shipping banner
        $flashSaleDiscount = (int) round($repo->activeFlashSales()->max('discount_percentage') ?? 0);

        // Product carousels
        $freeShippingProducts = $repo->latestProducts(5);
        $collection = $repo->latestProducts(8);
        $recommended = $repo->latestProducts(10);
        $hotDeals = $repo->featuredProducts(10);
        $explore = $repo->featuredProducts(10);

        $relatedProductIds = collect($cartItems)->pluck('product.central_product_id')->filter()->unique()->values();
        if ($relatedProductIds->isNotEmpty()) {
            $relatedProducts = Product::query()
                ->whereIn('id', $relatedProductIds)
                ->get();
        } else {
            $relatedProducts = collect();
        }

        $data = array_merge($this->sharedData(), [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
            'appliedCoupon' => $appliedCoupon,
            'cartDiscount' => $cartDiscount,
            'cartFinalTotal' => $cartFinalTotal,
            'cartWeight' => $cartWeight,
            'shippingThreshold' => $shippingThreshold,
            'shippingCost' => $shippingCost,
            'cartTax' => $cartTax,
            'orderTotal' => $orderTotal,
            'freeShippingProducts' => $freeShippingProducts,
            'flashSaleDiscount' => $flashSaleDiscount,
            'collection' => $collection,
            'recommended' => $recommended,
            'hotDeals' => $hotDeals,
            'explore' => $explore,
            'relatedProducts' => $relatedProducts,
        ]);

        $storeName = $repo->storeName();

        return view($this->pageView('cart'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Cart') . " — {$storeName}" : __('Cart'),
                'metaDescription' => '',
            ]);
    }
}
