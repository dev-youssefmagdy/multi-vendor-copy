<?php

namespace App\Livewire\Tenant\Storefront\ThemeKit;

use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Models\Tenant\Product;
use Livewire\Component;

/**
 * Drop-in add-to-cart button for vendor Blade themes.
 * Usage: @livewire('storefront.add-to-cart-button', ['product' => $product->id])
 */
class AddToCartButton extends Component
{
    use ChecksCartStock;

    public int $product = 0;
    public ?int $variant = null;
    public int $qty = 1;
    public string $label = 'Add to Cart';

    public function mount(int|Product $product, ?int $variant = null, int $qty = 1, string $label = 'Add to Cart'): void
    {
        $this->product = $product instanceof Product ? $product->id : $product;
        $this->variant = $variant;
        $this->qty = $qty;
        $this->label = $label;
    }

    public function add(): void
    {
        $productModel = Product::with(['variants', 'centralProduct'])
            ->where('active', true)
            ->find($this->product);

        if (!$productModel) {
            $this->dispatch('storefront-toast', message: 'Product not found.', type: 'error');
            return;
        }

        $variantModel = $this->variant
            ? $productModel->variants->firstWhere('id', $this->variant)
            : $productModel->variants->where('active', true)->first();

        $cart = session('storefront_cart', []);
        $key = $variantModel ? 'v_' . $variantModel->id : 'p_' . $this->product;
        $existingQty = (int) ($cart[$key]['qty'] ?? 0);

        $stockError = $this->checkProductStock($productModel, $variantModel, $this->qty, $existingQty);
        if ($stockError !== null) {
            $this->dispatch('storefront-toast', message: $stockError, type: 'error');
            return;
        }

        $cart[$key] = [
            'product_id' => $this->product,
            'variant_id' => $variantModel?->id,
            'qty' => $existingQty + $this->qty,
        ];
        session(['storefront_cart' => $cart]);

        // Vendor-facing simplified event
        $this->dispatch('cart-updated');

        // Keep the platform's native cart badge / dropdown in sync too
        $this->dispatch('cartUpdated');
        $this->dispatch('storefront-cart-added', itemName: $productModel->translationValue('name') ?? $productModel->slug, qty: $this->qty);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.tenant.storefront.theme-kit.add-to-cart-button');
    }
}
