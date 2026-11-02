<?php

namespace App\Livewire\Tenant\Storefront\Layout;

use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Livewire\Attributes\On;
use Livewire\Component;

class CartManager extends Component
{
    use ChecksCartStock;

    #[On('add-to-cart-variant')]
    public function addWithVariant(int $productId, ?int $variantId = null): void
    {
        $error = $this->addLine($productId, $variantId);
        if ($error !== null) {
            if ($error !== '') {
                $this->dispatch('storefront-toast', message: $error, type: 'error');
            }
            return;
        }

        $this->dispatch('cartUpdated');
        $this->dispatch('storefront-cart-added');
    }

    /**
     * Adds one unit of each product (e.g. "Add all to cart" on favorites). Products with variants
     * use their first in-stock active variant (first active one if none are in stock).
     *
     * @param  int[]  $productIds
     */
    #[On('add-many-to-cart')]
    public function addMany(array $productIds): void
    {
        $added = 0;
        $firstError = null;

        foreach (array_unique(array_map('intval', $productIds)) as $productId) {
            $error = $this->addLine($productId, null, true);
            if ($error === null) {
                $added++;
            } elseif ($error !== '') {
                $firstError ??= $error;
            }
        }

        if ($firstError !== null) {
            $this->dispatch('storefront-toast', message: $firstError, type: 'error');
        }

        if ($added > 0) {
            $this->dispatch('cartUpdated');
            $this->dispatch('storefront-cart-added');
        }
    }

    /**
     * @return string|null null = added, '' = skipped silently (unknown product), otherwise the stock error
     */
    private function addLine(int $productId, ?int $variantId, bool $preferInStock = false): ?string
    {
        $product = Product::with(['variants', 'centralProduct'])->where('active', true)->find($productId);
        if (!$product) {
            return '';
        }

        $activeVariants = $product->variants->where('active', true);

        // Validate the requested variant belongs to this product and is active
        $variant = null;
        if ($variantId !== null) {
            $variant = $activeVariants->firstWhere('id', $variantId);
            if (!$variant) {
                $variant = $activeVariants->first();
                $variantId = $variant?->id;
            }
        } else {
            $variant = ($preferInStock ? $activeVariants->first(fn ($v) => (int) $v->stock > 0) : null)
                ?? $activeVariants->first();
            $variantId = $variant?->id;
        }

        $cart = session('storefront_cart', []);
        $key = $variantId ? 'v_' . $variantId : 'p_' . $productId;
        $existingQty = (int) ($cart[$key]['qty'] ?? 0);

        if ($variantId && !$variant) {
            $variant = ProductVariant::with(['product.centralProduct', 'centralVariant'])->find($variantId);
        }

        $stockError = $this->checkProductStock($product, $variant, 1, $existingQty);
        if ($stockError !== null) {
            return $stockError;
        }

        if (isset($cart[$key])) {
            $cart[$key]['qty']++;
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'qty' => 1,
            ];
        }

        session(['storefront_cart' => $cart]);
        app(\App\Services\UserInteractionTracker::class)->trackCart($productId);

        return null;
    }

    public function render()
    {
        return view('livewire.tenant.storefront.layout.cart-manager');
    }
}
