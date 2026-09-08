<?php

namespace App\Livewire\Admin\Store;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\CentralFlashSale;
use App\Models\Country;
use App\Repositories\ProductRepository;
use App\Services\Admin\CentralFlashSaleService;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class FlashSalesPage extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;
    use WithFileUploads;

    public ?int $countryId = null;

    public bool $showFormModal = false;
    public ?int $flashSaleId = null;
    public array $productIds = [];
    public string $discountPercentage = '0.00';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public bool $active = true;

    // Async product picker state
    public string $productSearch = '';
    public int $productSearchPage = 1;
    public array $productSearchResults = [];
    public bool $hasMoreProducts = false;
    public array $selectedProductLabels = [];

    public function mount(?int $countryId = null): void
    {
        $this->authorizePermission('store.flash-sales.manage');
        $this->countryId = $countryId;
    }

    protected function pageMeta(): array
    {
        $country = $this->countryId ? Country::query()->find($this->countryId) : null;

        return [
            'title' => $country ? "Central Flash Sales — {$country->flag_emoji} {$country->name}" : 'Central Flash Sales — Default',
            'badge' => 'Store',
            'description' => $country
                ? "Flash sale campaigns synced to tenants targeting {$country->name}."
                : 'Default flash sale campaigns synced to every tenant with no country-specific campaigns.',
            'actionLabel' => 'Add Flash Sale',
            'tableTitle' => 'Flash Sale Campaigns',
            'headers' => ['Products', 'Discount', 'Window', 'Status', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $this->authorizePermission('store.flash-sales.manage');

        $records = CentralFlashSale::query()
            ->with('products.translations.language')
            ->where('country_id', $this->countryId)
            ->orderByDesc('id')
            ->paginate(15);

        $total = CentralFlashSale::query()->where('country_id', $this->countryId)->count();
        $active = CentralFlashSale::query()->where('country_id', $this->countryId)->where('active', true)->count();
        $avgDisc = CentralFlashSale::query()->where('country_id', $this->countryId)->avg('discount_percentage') ?? 0;

        $rows = collect($records->items())->map(fn(CentralFlashSale $flashSale) => [
            $this->productSummaryCell($flashSale),
            e(number_format((float) $flashSale->discount_percentage, 2)) . '%',
            e(optional($flashSale->start_date)->format('M d, Y') ?: '-') . ' – ' . e(optional($flashSale->end_date)->format('M d, Y') ?: '-'),
            '<span class="badge ' . ($flashSale->active ? 'badge-green' : 'badge-amber') . '">' . e($flashSale->active ? 'Active' : 'Inactive') . '</span>',
            '<div class="flex gap-2">
                <button type="button" class="btn btn-secondary btn-sm" wire:click="editFlashSale(' . $flashSale->id . ')">Edit</button>
                <button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $flashSale->id . ')">Delete</button>
            </div>',
        ])->all();

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'secondaryActionLabel' => '← All Countries',
            'secondaryActionUrl' => route('admin.store.flash-sales.index'),
            'records' => $records,
            'rows' => $rows,
            'statistics' => [
                ['label' => 'Flash Sales', 'value' => number_format($total), 'caption' => 'Discount campaigns configured', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => number_format($active), 'caption' => 'Currently running campaigns', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Avg Discount', 'value' => number_format($avgDisc, 2) . '%', 'caption' => 'Average configured discount', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->flashSaleId ? 'Edit Flash Sale' : 'Add Flash Sale',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->flashSaleId ? 'Update Flash Sale' : 'Create Flash Sale',
            'modalContentView' => 'livewire.admin.store.partials.flash-sale-form',
            'modalContentData' => [],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->flashSaleId = null;
        $this->productIds = [];
        $this->discountPercentage = '0.00';
        $this->startDate = null;
        $this->endDate = null;
        $this->active = true;
        $this->productSearch = '';
        $this->productSearchPage = 1;
        $this->productSearchResults = [];
        $this->hasMoreProducts = false;
        $this->selectedProductLabels = [];
        $this->resetErrorBag();
        $this->loadProducts();
        $this->showFormModal = true;
    }

    public function editFlashSale(int $flashSaleId): void
    {
        $flashSale = CentralFlashSale::query()->with('products')->findOrFail($flashSaleId);
        $this->flashSaleId = $flashSale->id;
        $this->productIds = $flashSale->products->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->discountPercentage = number_format((float) $flashSale->discount_percentage, 2, '.', '');
        $this->startDate = optional($flashSale->start_date)->format('Y-m-d\TH:i');
        $this->endDate = optional($flashSale->end_date)->format('Y-m-d\TH:i');
        $this->active = $flashSale->active;
        $this->productSearch = '';
        $this->productSearchPage = 1;
        $this->productSearchResults = [];
        $this->hasMoreProducts = false;
        $intIds = array_map('intval', $this->productIds);
        $this->selectedProductLabels = app(ProductRepository::class)->productNamesForIds($intIds);
        $this->loadProducts();
        $this->showFormModal = true;
    }

    public function save(CentralFlashSaleService $service, CentralCatalogTenantSyncService $syncService): void
    {
        $this->authorizePermission('store.flash-sales.manage');

        $validated = $this->validate([
            'productIds' => ['required', 'array', 'min:1'],
            'productIds.*' => ['integer', 'exists:products,id'],
            'discountPercentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'active' => ['boolean'],
        ]);

        $existing = $this->flashSaleId
            ? CentralFlashSale::query()->findOrFail($this->flashSaleId)
            : null;

        $service->save([
            'product_ids' => $validated['productIds'],
            'discount_percentage' => $validated['discountPercentage'],
            'start_date' => $validated['startDate'] ?? null,
            'end_date' => $validated['endDate'] ?? null,
            'active' => $validated['active'],
            'country_id' => $this->countryId,
        ], $existing);

        // Sync to all tenants
        $syncService->syncAllTenants(['flash-sales']);

        $this->closeModal();
        $this->toast($this->flashSaleId ? __('Flash sale updated and synced to tenants.') : __('Flash sale created and synced to tenants.'));
    }

    public function confirmDelete(int $flashSaleId): void
    {
        $this->confirmAction('deleteFlashSale', [$flashSaleId], [
            'title' => 'Delete flash sale?',
            'confirmButtonText' => 'Delete flash sale',
        ]);
    }

    public function deleteFlashSale(int $flashSaleId, CentralFlashSaleService $service, CentralCatalogTenantSyncService $syncService): void
    {
        $this->authorizePermission('store.flash-sales.manage');

        $service->delete(CentralFlashSale::query()->findOrFail($flashSaleId));
        $syncService->syncAllTenants(['flash-sales']);
        $this->toast(__('Flash sale deleted and synced to tenants.'));
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->flashSaleId = null;
        $this->productIds = [];
        $this->discountPercentage = '0.00';
        $this->startDate = null;
        $this->endDate = null;
        $this->active = true;
        $this->productSearch = '';
        $this->productSearchPage = 1;
        $this->productSearchResults = [];
        $this->hasMoreProducts = false;
        $this->selectedProductLabels = [];
        $this->resetErrorBag();
    }

    public function clearFilters(): void
    {
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

        if (in_array($strId, $this->productIds, true)) {
            $this->productIds = array_values(array_filter($this->productIds, fn($v) => $v !== $strId));
            unset($this->selectedProductLabels[$id]);
        } else {
            $this->productIds[] = $strId;
            $this->selectedProductLabels[$id] = $this->productSearchResults[$id] ?? 'Product #' . $id;
        }
    }

    public function removeProduct(int $id): void
    {
        $strId = (string) $id;
        $this->productIds = array_values(array_filter($this->productIds, fn($v) => $v !== $strId));
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

    protected function productSummaryCell(CentralFlashSale $flashSale): string
    {
        $products = $flashSale->products;

        if ($products->isEmpty()) {
            return e('No products linked');
        }

        $names = $products->map(
            fn(\App\Models\Product $product) => $product->translationValue('name') ?? $product->slug ?? 'Product #' . $product->id
        )->values();

        $primary = e((string) $names->first());
        $extraCount = $names->count() - 1;
        $subtitle = $extraCount > 0
            ? '+' . $extraCount . ' more product' . ($extraCount > 1 ? 's' : '')
            : '1 product';

        return '<div class="entity-title">' . $primary . '</div><div class="entity-subtitle">' . e($subtitle) . '</div>';
    }
}
