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
        $product = Product::with(['variants', 'centralProduct'])->where('active', true)->find($productId);
        if (!$product) {
            return;
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
            $variant = $activeVariants->first();
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
            $this->dispatch('storefront-toast', message: $stockError, type: 'error');
            return;
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
        $this->dispatch('cartUpdated');
        $this->dispatch('storefront-cart-added');
    }

    public function render()
    {
        return view('livewire.tenant.storefront.layout.cart-manager');
    }
}
