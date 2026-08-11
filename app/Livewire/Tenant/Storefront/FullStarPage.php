<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Component;

class FullStarPage extends Component
{
    use HasStorefrontLayout;
    use ChecksCartStock;

    public string $search = '';
    public ?int $categoryId = null;

    public function mount(): void
    {
        $this->search = trim((string) request('keyword', ''));

        $categoryId = request('category_id');
        $this->categoryId = is_numeric($categoryId) && (int) $categoryId > 0 ? (int) $categoryId : null;
    }

    public function addToCart(int $productId): void
    {
        $product = \App\Models\Tenant\Product::with(['variants', 'centralProduct'])->where('active', true)->find($productId);
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

        $data = array_merge($this->sharedData(), [
            'products' => $repo->paginatedTopRatedProducts([
                'keyword' => $this->search,
                'category_id' => $this->categoryId,
            ], 20, 5),
            'categories' => $repo->activeCategories(),
            'search' => $this->search,
            'currentCategoryId' => $this->categoryId,
        ]);

        return view($this->pageView('full-star'), $data)
            ->layout($this->storefrontLayout());
    }
}
