<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Attributes\Url;
use Livewire\Component;

class BestSellingPage extends Component
{
    use HasStorefrontLayout;
    use ChecksCartStock;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 30)]
    public int $days = 30;

    #[Url(as: 'category_id', except: '')]
    public string $categoryId = '';

    public bool $isSearchRoute = false;
    public bool $isImageSearch = false;
    public int $perPage = 20;
    public bool $hasMore = false;

    public function mount(): void
    {
        $this->isSearchRoute = request()->routeIs('tenant.storefront.search') || request()->routeIs('tenant.path.storefront.search');

        // Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
        // ImageSearchController stashes the ranked central product IDs in the
        // session, then redirects here with ?mode=image.
        $this->isImageSearch = $this->isSearchRoute && request('mode') === 'image' && session()->has('image_search_product_ids');

        // Bootstrap from URL on first load (Livewire #[Url] handles subsequent updates)
        if ($this->isSearchRoute && request()->has('q') && $this->search === '') {
            $this->search = trim((string) request('q', ''));
        }

        $days = (int) request('days', 30);
        if ($this->days === 30 && in_array($days, [7, 14, 30], true)) {
            $this->days = $days;
        }

        if (!in_array($this->days, [7, 14, 30], true)) {
            $this->days = 30;
        }
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'days', 'categoryId'], true)) {
            $this->perPage = 20;
        }
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function setDays(int $days): void
    {
        if (in_array($days, [7, 14, 30], true)) {
            $this->days = $days;
            $this->perPage = 20;
        }
    }

    public function setCategory(string $id): void
    {
        $this->categoryId = $id;
        $this->perPage = 20;
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

        $categories = $repo->activeCategories();

        $categoryId = is_numeric($this->categoryId) && (int) $this->categoryId > 0
            ? (int) $this->categoryId
            : null;

        $products = match (true) {
            $this->isImageSearch => $repo->imageSearchProducts(
                (array) session('image_search_product_ids', []),
                $this->perPage,
            ),
            $this->isSearchRoute => $repo->paginatedSearchProducts([
                'keyword' => $this->search,
                'category_id' => $categoryId,
            ], $this->perPage),
            default => $repo->paginatedBestSellingProducts($this->days, [
                'keyword' => $this->search,
                'category_id' => $categoryId,
            ], $this->perPage),
        };

        $this->hasMore = $products->hasMorePages();

        $data = array_merge($this->sharedData(), [
            'products' => $products,
            'categories' => $categories,
            'search' => $this->search,
            'days' => $this->days,
            'currentCategoryId' => $categoryId,
            'isSearchRoute' => $this->isSearchRoute,
            'isImageSearch' => $this->isImageSearch,
            'hasMore' => $this->hasMore,
        ]);

        $storeName = $repo->storeName();

        return view($this->pageView('best-selling'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Best Selling') . " — {$storeName}" : __('Best Selling'),
                'metaDescription' => $storeName ? __('Shop the best selling products at :store.', ['store' => $storeName]) : __('Shop our best selling products.'),
                'canonicalUrl' => route('tenant.storefront.best-selling'),
            ]);
    }
}
