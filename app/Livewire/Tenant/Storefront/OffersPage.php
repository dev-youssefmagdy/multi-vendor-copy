<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Attributes\Url;
use Livewire\Component;

class OffersPage extends Component
{
    use HasStorefrontLayout;
    use ChecksCartStock;

    public function mount(): void
    {
        // Only some themes ship an offers page; the others list the same flash-sale
        // products through their category listing instead of failing with a 500.
        if (!view()->exists($this->pageView('offers'))) {
            $this->redirect(route('tenant.storefront.category', ['section' => 'flash_sale']));
        }
    }

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

    #[Url(except: 'best_selling')]
    public string $sort = 'best_selling';

    #[Url(as: 'category_id', except: '')]
    public string $categoryId = '';

    #[Url(as: 'flash', except: false)]
    public bool $flashOnly = false;

    // String so a hand-edited URL (?min_discount=abc) cannot throw a TypeError on hydration; normalised to an int in render().
    #[Url(as: 'min_discount', except: '0')]
    public string $minDiscount = '0';

    public int $perPage = 20;

    public function updating(string $property): void
    {
        if (in_array($property, ['sort', 'categoryId', 'flashOnly', 'minDiscount'], true)) {
            $this->perPage = 20;
        }
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function setCategory(string $id): void
    {
        $this->categoryId = $id;
        $this->perPage = 20;
    }

    public function clearFilters(): void
    {
        $this->reset(['sort', 'categoryId', 'flashOnly', 'minDiscount']);
        $this->perPage = 20;
    }

    public function render()
    {
        if (!view()->exists($this->pageView('offers'))) {
            return '<div></div>'; // mount() already redirected to the category listing
        }

        $repo = app(StorefrontRepository::class);

        if (!in_array($this->sort, ['best_selling', 'ascending', 'descending', 'discount', 'newest'], true)) {
            $this->sort = 'best_selling';
        }
        $minDiscount = (int) $this->minDiscount;
        if (!in_array($minDiscount, [0, 10, 20, 30, 50], true)) {
            $minDiscount = 0;
        }
        $this->minDiscount = (string) $minDiscount;

        // Every product with a real active discount (flash sale or price drop), best sellers first.
        $result = $repo->discountedProducts([
            'sort' => $this->sort,
            'category_id' => $this->categoryId,
            'flash_only' => $this->flashOnly,
            'min_discount' => $minDiscount,
        ], $this->perPage);

        $data = array_merge($this->sharedData(), [
            'products' => $result['products'],
            'totalItems' => $result['total'],
            'hasMore' => $result['has_more'],
            'maxDiscount' => $result['max_discount'],
            'categories' => $repo->activeCategories(),
            'currentCategoryId' => is_numeric($this->categoryId) && (int) $this->categoryId > 0 ? (int) $this->categoryId : null,
            'sort' => $this->sort,
            'flashOnly' => $this->flashOnly,
            'minDiscount' => $minDiscount,
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
