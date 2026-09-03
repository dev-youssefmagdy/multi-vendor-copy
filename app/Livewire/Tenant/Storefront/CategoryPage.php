<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Attributes\Url;
use Livewire\Component;

class CategoryPage extends Component
{
    use HasStorefrontLayout;
    use ChecksCartStock;

    public $slug;

    #[Url(except: '')]
    public string $keyword = '';

    #[Url(except: 'latest')]
    public string $sort = 'latest';

    #[Url(except: '')]
    public string $availability = '';

    #[Url(as: 'product_flag', except: '')]
    public string $productFlag = '';

    #[Url(as: 'on_sale', except: '')]
    public string $sale = '';

    #[Url(except: '')]
    public string $ratings = '';

    #[Url(except: '')]
    public string $section = '';

    #[Url(except: '')]
    public string $min = '';

    #[Url(except: '')]
    public string $max = '';

    public int $perPage = 15;
    public bool $hasMore = false;

    public function mount($slug = null): void
    {
        $this->slug = $slug;

        // Sanitize values coming from the URL
        if (!in_array($this->sort, ['latest', 'old', 'ascending', 'descending', 'rating'], true)) {
            $this->sort = 'latest';
        }

        if (!in_array($this->availability, ['in_stock', 'out_of_stock', ''], true)) {
            $this->availability = '';
        }

        if ($this->productFlag !== 'featured') {
            $this->productFlag = '';
        }

        if (!in_array($this->sale, ['on_sale', 'flash_sale', ''], true)) {
            $this->sale = '';
        }

        if (!in_array($this->ratings, ['1', '2', '3', '4', ''], true)) {
            $this->ratings = '';
        }

        $this->min = $this->normalizePrice($this->min);
        $this->max = $this->normalizePrice($this->max);

        if ($this->min !== '' && $this->max !== '' && (float) $this->min > (float) $this->max) {
            [$this->min, $this->max] = [$this->max, $this->min];
        }

        $validSections = ['trending', 'flash_sale', 'new_arrivals', 'top_products', 'explore', 'featured', 'hot_deals', 'special_offers', 'top_rated', 'recommended'];
        if (!in_array($this->section, $validSections, true)) {
            $this->section = '';
        }
    }

    protected function normalizePrice(string $value): string
    {
        if ($value === '' || !is_numeric($value)) {
            return '';
        }

        $normalized = number_format((float) $value, 2, '.', '');

        return rtrim(rtrim($normalized, '0'), '.');
    }

    /**
     * Reset page size when any filter property is about to change.
     */
    public function updating(string $property): void
    {
        if (in_array($property, ['sort', 'availability', 'ratings', 'min', 'max', 'keyword', 'sale', 'productFlag', 'section'], true)) {
            $this->perPage = 15;
        }
    }

    public function loadMore(): void
    {
        $this->perPage += 15;
    }

    /** Toggle a sale filter (flash_sale / on_sale) and clear conflicting tab filters. */
    public function toggleSale(string $value): void
    {
        $this->sale = $this->sale === $value ? '' : $value;
        $this->ratings = '';
        $this->productFlag = '';
        $this->perPage = 15;
    }

    /** Toggle the product-flag filter (e.g. "featured") and clear conflicting tab filters. */
    public function toggleProductFlag(string $value): void
    {
        $this->productFlag = $this->productFlag === $value ? '' : $value;
        $this->sale = '';
        $this->ratings = '';
        $this->perPage = 15;
    }

    /** Toggle a minimum-star rating filter and clear conflicting tab filters. */
    public function toggleRatings(string $value): void
    {
        $this->ratings = $this->ratings === $value ? '' : $value;
        $this->sale = '';
        $this->productFlag = '';
        $this->perPage = 15;
    }

    public function clearFilters(): void
    {
        $this->keyword = '';
        $this->sort = 'latest';
        $this->availability = '';
        $this->productFlag = '';
        $this->sale = '';
        $this->ratings = '';
        $this->min = '';
        $this->max = '';
        $this->section = '';
        $this->perPage = 15;

        if ($this->slug) {
            $this->redirect(route('tenant.storefront.category', $this->slug));
        } else {
            $this->redirect(route('tenant.storefront.category'));
        }
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

        if ($this->slug) {
            $category = $repo->categoryBySlug($this->slug);
            if (!$category) {
                abort(404);
            }
        } else {
            $category = null;
        }

        $categories = $repo->activeCategories();
        $allCategories = clone $categories;
        $parentCategory = $category && $category->parent_id
            ? $categories->firstWhere('id', $category->parent_id)
            : null;

        // $siblingCategories = $category
        //     ? ($parentCategory
        //         ? $categories->where('parent_id', $parentCategory->id)->values()
        //         : $categories->where('parent_id', $category->id)->values())
        //     : $categories->whereNull('parent_id')->values();

        // if ($category && $siblingCategories->isEmpty()) {
        //     $siblingCategories = $categories->whereNull('parent_id')->values();
        // }

        $childrenOrSiblings = $category
            ? ($category->children->isNotEmpty()
                ? $category->children
                : ($parentCategory
                    ? $categories->where('parent_id', $parentCategory->id)->values()
                    : $categories->whereNull('parent_id')->values()))
            : $categories->whereNull('parent_id')->values();

        $products = $repo->paginatedProductsByCategory($category, [
            'keyword' => $this->keyword,
            'sort' => $this->sort,
            'availability' => $this->availability,
            'product_flag' => $this->productFlag,
            'on_sale' => $this->sale,
            'ratings' => $this->ratings,
            'min' => $this->min,
            'max' => $this->max,
            'section' => $this->section,
        ], $this->perPage);

        $this->hasMore = $products->hasMorePages();

        // dd($category, $parentCategory);

        $data = array_merge($this->sharedData(), [
            'category' => $category,
            'products' => $products,
            'categories' => $categories,
            'parentCategory' => $parentCategory,
            'relatedCategories' => /*$siblingCategories*/ $childrenOrSiblings,
            'keyword' => $this->keyword,
            'sort' => $this->sort,
            'availability' => $this->availability,
            'productFlag' => $this->productFlag,
            'sale' => $this->sale,
            'ratings' => $this->ratings,
            'min' => $this->min,
            'max' => $this->max,
            'section' => $this->section,
            'hasMore' => $this->hasMore,
            'allCategories' => $allCategories,
        ]);

        $storeName = $repo->storeName();
        $categoryName = $category ? ($category->translationValue('name') ?? $category->slug) : __('All Products');
        $categoryDesc = $category ? mb_substr(strip_tags($category->translationValue('description') ?? ''), 0, 160) : '';
        $categoryImage = $category?->thumb_url ?? null;

        return view($this->pageView('category'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? "{$categoryName} — {$storeName}" : $categoryName,
                'metaDescription' => $categoryDesc ?: ($storeName ? __('Shop :category at :store', ['category' => $categoryName, 'store' => $storeName]) : $categoryName),
                'ogImage' => $categoryImage,
                'ogType' => 'website',
                'canonicalUrl' => $category ? route('tenant.storefront.category', $category->slug) : route('tenant.storefront.category'),
            ]);
    }
}
