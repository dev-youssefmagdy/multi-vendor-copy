<?php

namespace App\Livewire\Admin\Product;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBadge;
use App\Repositories\ProductRepository;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class BadgeProductsList extends Component
{
    use AuthorizesAdminPermissions;

    public int $badgeId;
    public string $badgeText = '';
    public string $badgeTitle = '';
    public array $selectedProducts = [];
    public ?int $filterCategoryId = null;

    // Async product picker state
    public string $productSearch = '';
    public int $productSearchPage = 1;
    public array $productSearchResults = [];
    public bool $hasMoreProducts = false;
    public array $selectedProductLabels = [];

    public function mount(): void
    {
        $badgeText = match (Route::currentRouteName()) {
            'admin.badges.new-in' => 'new-in',
            'admin.badges.best-selling' => 'best-selling',
            default => abort(404),
        };

        $badge = ProductBadge::query()->where('text', $badgeText)->firstOrFail();
        $this->badgeId = $badge->id;
        $this->badgeText = $badge->text;
        $this->badgeTitle = match ($badgeText) {
            'new-in' => 'New In Products',
            'best-selling' => 'Best Selling Products',
            default => ucwords(str_replace('-', ' ', $badgeText)) . ' Products',
        };

        // Pre-load currently assigned product IDs into the multiselect
        $this->selectedProducts = $badge->products()->pluck('products.id')->map(fn($id) => (string) $id)->all();

        // Pre-load labels for selected products and prime the search results
        $this->selectedProductLabels = app(ProductRepository::class)->productNamesForIds(
            array_map('intval', $this->selectedProducts)
        );
        $this->loadProducts();
    }

    public function assignAllInCategory(): void
    {
        $this->authorizePermission('catalog.badges.manage');

        if (!$this->filterCategoryId) {
            session()->flash('status', 'Please choose a category first.');
            return;
        }

        $productIds = Product::query()
            ->whereHas('categories', fn($q) => $q->where('categories.id', $this->filterCategoryId))
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        if (!$productIds) {
            session()->flash('status', 'No products found in that category.');
            return;
        }

        $this->selectedProducts = array_values(array_unique(array_merge($this->selectedProducts, $productIds)));

        // Refresh labels to cover the newly merged products
        $this->selectedProductLabels = app(ProductRepository::class)->productNamesForIds(
            array_map('intval', $this->selectedProducts)
        );

        session()->flash('status', count($productIds) . ' products merged into the selection. Click Save to apply.');
    }

    public function save(): void
    {
        $this->authorizePermission('catalog.badges.manage');

        ProductBadge::query()->findOrFail($this->badgeId)
            ->products()
            ->sync($this->selectedProducts);

        app(CentralCatalogTenantSyncService::class)->syncAllTenants(['badges']);

        session()->flash('status', 'Badge assignment saved — ' . count($this->selectedProducts) . ' products assigned. Tenant sync triggered.');
    }

    // ── Async product picker ────────────────────────────────────────────────

    public function updatedProductSearch(): void
    {
        $this->productSearchPage = 1;
        $this->loadProducts();
    }

    public function loadMoreProducts(): void
    {
        $this->productSearchPage++;
        $this->loadProducts(append: true);
    }

    public function toggleProduct(int $id): void
    {
        $strId = (string) $id;

        if (in_array($strId, $this->selectedProducts, true)) {
            $this->selectedProducts = array_values(array_filter($this->selectedProducts, fn($v) => $v !== $strId));
            unset($this->selectedProductLabels[$id]);
        } else {
            $this->selectedProducts[] = $strId;
            $this->selectedProductLabels[$id] = $this->productSearchResults[$id] ?? 'Product #' . $id;
        }
    }

    public function removeProduct(int $id): void
    {
        $strId = (string) $id;
        $this->selectedProducts = array_values(array_filter($this->selectedProducts, fn($v) => $v !== $strId));
        unset($this->selectedProductLabels[$id]);
    }

    protected function loadProducts(bool $append = false): void
    {
        $result = app(ProductRepository::class)->searchForPicker(
            $this->productSearch,
            $this->productSearchPage,
        );

        $this->productSearchResults = $append
            ? array_merge($this->productSearchResults, $result['items'])
            : $result['items'];

        $this->hasMoreProducts = $result['has_more'];
    }

    public function render()
    {
        return view('livewire.admin.product.badge-products-list', [
            'assignedCount' => count($this->selectedProducts),
            'catalogCount' => Product::query()->count(),
            'canManage' => $this->hasPermission('catalog.badges.manage'),
            'categoryTree' => $this->buildCategoryTree(),
        ]);
    }

    /**
     * @return list<array{id:int,name:string,depth:int}>
     */
    protected function buildCategoryTree(): array
    {
        $roots = Category::query()
            ->with(['translations.language', 'children.translations.language', 'children.children.translations.language', 'children.children.children.translations.language'])
            ->whereNull('parent_id')
            ->get();

        $flat = [];
        $walk = function ($items, int $depth) use (&$walk, &$flat) {
            foreach ($items as $item) {
                $flat[] = [
                    'id' => (int) $item->id,
                    'name' => (string) ($item->translationValue('name') ?? ('Category #' . $item->id)),
                    'depth' => $depth,
                ];
                if ($item->children && $item->children->count()) {
                    $walk($item->children, $depth + 1);
                }
            }
        };
        $walk($roots, 0);

        return $flat;
    }
}
