<?php

namespace App\Livewire\Admin\Product;

use App\Enums\DeliveryScope;
use App\Enums\ProductStatus;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\Category;
use App\Models\FixedShippingCost;
use App\Models\ProductTenantAssignment;
use App\Repositories\CategoryRepository;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Livewire\Component;
use Livewire\WithPagination;

class ProductsList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $categoryFilter = '';
    public string $deliveryScopeFilter = '';
    public string $stockFilter = '';
    public bool $showDeleted = false;
    public array $imageSearchIds = [];
    public bool $imageSearchActive = false;

    // ── Price list modal ───────────────────────────────────────────────────
    public bool $priceListOpen = false;
    public string $priceListProductName = '';
    /** @var array<int, array{country: string, shipping: float, total_cost: float}> */
    public array $priceListRows = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDeliveryScopeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function updatedShowDeleted(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'categoryFilter', 'deliveryScopeFilter', 'stockFilter', 'showDeleted']);
        $this->clearImageSearch();
        $this->resetPage();
    }

    public function applyImageSearch(array $ids): void
    {
        $this->imageSearchIds = array_values(array_map('intval', $ids));
        $this->imageSearchActive = !empty($this->imageSearchIds);
        $this->resetPage();
    }

    public function clearImageSearch(): void
    {
        $this->imageSearchIds = [];
        $this->imageSearchActive = false;
        $this->resetPage();
    }

    public function deleteProduct(int $productId, CentralCatalogTenantSyncService $tenantSyncService): void
    {
        $this->authorizePermission('catalog.products.manage');
        $product = \App\Models\Product::query()->findOrFail($productId);
        $product->delete();

        $this->syncTenantVisibility($product, $tenantSyncService);

        session()->flash('status', 'Product deleted. It is hidden from tenant panels and storefronts until restored.');
    }

    public function restoreProduct(int $productId, CentralCatalogTenantSyncService $tenantSyncService): void
    {
        $this->authorizePermission('catalog.products.manage');
        $product = \App\Models\Product::withTrashed()->findOrFail($productId);
        $product->restore();

        $this->syncTenantVisibility($product, $tenantSyncService);

        session()->flash('status', 'Product restored successfully.');
    }

    protected function syncTenantVisibility(Product $product, CentralCatalogTenantSyncService $tenantSyncService): void
    {
        $tenantSyncService->syncProduct($product);

        $assignedTenantIds = ProductTenantAssignment::where('product_id', $product->id)
            ->pluck('tenant_id')
            ->all();

        $tenantSyncService->syncProductToAssignedTenants($product, $assignedTenantIds);
    }

    public function openPriceListModal(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);
        $salePrice = (float) ($product->sale_price ?: $product->base_price ?? 0);

        $shippingCosts = FixedShippingCost::query()
            ->where('is_active', true)
            ->with('country')
            ->get();

        $fixedJson = is_array($product->fixed_shipping_costs) ? $product->fixed_shipping_costs : [];
        $weight = (int) ($product->weight_grams ?? 0);

        $rows = [['country' => 'Default (no country)', 'shipping' => 0.0, 'total_cost' => $salePrice]];
        foreach ($shippingCosts as $record) {
            $key = (string) $record->country_id;
            $shipping = array_key_exists($key, $fixedJson)
                ? (float) $fixedJson[$key]
                : ($weight > 0 ? round((float) $record->price_per_gram * $weight, 2) : 0.0);
            $rows[] = [
                'country' => $record->country?->name ?? 'Country #' . $record->country_id,
                'shipping' => $shipping,
                'total_cost' => round($salePrice + $shipping, 2),
            ];
        }

        $this->priceListProductName = $product->slug ?? 'Product #' . $productId;
        $this->priceListRows = $rows;
        $this->priceListOpen = true;
    }

    public function closePriceListModal(): void
    {
        $this->priceListOpen = false;
        $this->priceListProductName = '';
        $this->priceListRows = [];
    }

    public function render(ProductRepository $products)
    {
        $stats = $products->stats();
        $paginator = $products->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'category_id' => $this->categoryFilter,
            'delivery_scope' => $this->deliveryScopeFilter,
            'stock' => $this->stockFilter,
            'show_deleted' => $this->showDeleted,
            'image_search_ids' => $this->imageSearchIds,
        ]);

        return view('livewire.admin.product.products-list', [
            'products' => $paginator,
            'stats' => $stats,
            'categories' => app(CategoryRepository::class)->flatTree(),
            'statusOptions' => ProductStatus::cases(),
            'deliveryScopes' => DeliveryScope::cases(),
            'canManageProducts' => $this->hasPermission('catalog.products.manage'),
            'imageSearchIds' => $this->imageSearchIds,
            'imageSearchActive' => $this->imageSearchActive,
            'priceListOpen' => $this->priceListOpen,
            'priceListProductName' => $this->priceListProductName,
            'priceListRows' => $this->priceListRows,
        ]);
    }
}
