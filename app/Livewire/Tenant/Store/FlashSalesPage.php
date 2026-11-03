<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Country;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Product as TenantProduct;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\FlashSaleMediaService;
use App\Services\Tenant\TenantPanelService;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class FlashSalesPage extends ListPage
{
    use InteractsWithTenantUi;
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
    public $bannerImage = null;
    public ?string $bannerImagePath = null;

    // Async product picker state
    public string $productSearch = '';
    public int $productSearchPage = 1;
    public array $productSearchResults = [];
    public bool $hasMoreProducts = false;
    public array $selectedProductLabels = [];
    public array $productSearchImages = [];   // [id => url] for search results
    public array $selectedProductImages = [];  // [id => url] for selected chips

    public function mount(?int $countryId = null): void
    {
        $this->countryId = $countryId;
    }

    protected function pageMeta(): array
    {
        $country = $this->countryId ? Country::query()->find($this->countryId) : null;

        return [
            'title' => $country ? "Flash Sales — {$country->flag_emoji} {$country->name}" : 'Flash Sales — Default',
            'badge' => 'Storefront',
            'description' => $country
                ? "Flash sale campaigns for visitors from {$country->name}."
                : 'Default flash sales shown when no country-specific campaigns exist.',
            'actionLabel' => 'Add Flash Sale',
            'tableTitle' => 'Flash Sale Campaigns',
            'headers' => ['Product', 'Banner', 'Discount', 'Window', 'Source', 'Status', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateFlashSales($this->countryId);
        $stats = $repository->flashSaleStats($this->countryId);

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'secondaryActionLabel' => '← All Countries',
            'secondaryActionUrl' => route('tenant.store.flash-sales.index'),
            'records' => $records,
            'statistics' => [
                ['label' => 'Flash Sales', 'value' => number_format($stats['total']), 'caption' => 'Discount campaigns configured', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'caption' => 'Currently running campaigns', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Avg Discount', 'value' => number_format($stats['avg_discount'], 2) . '%', 'caption' => 'Average configured discount', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Products', 'value' => number_format($stats['products']), 'caption' => 'Unique products enrolled in flash sales', 'dot' => 'dot-violet'],
            ],
            'rows' => collect($records->items())->map(fn(FlashSale $flashSale) => [
                $this->productSummaryCell($flashSale),
                ($flashSale->banner_url)
                ? '<img src="' . e($flashSale->banner_url) . '" style="height:40px;object-fit:cover;border-radius:6px" />'
                : '-',
                e(number_format((float) $flashSale->discount_percentage, 2)) . '%',
                e(optional($flashSale->start_date)->format('M d, Y') ?: '-') . ' - ' . e(optional($flashSale->end_date)->format('M d, Y') ?: '-'),
                $flashSale->central_flash_sale_id
                ? '<span class="badge badge-cyan">Platform</span>'
                : '<span class="badge badge-indigo">Store</span>',
                '<span class="badge ' . ($flashSale->active ? 'badge-green' : 'badge-amber') . '">' . e($flashSale->active ? 'Active' : 'Inactive') . '</span>',
                $flashSale->central_flash_sale_id
                ? '<span style="font-size:11.5px;color:var(--t3);">Managed by platform</span>'
                : '<div class="flex gap-2"><button type="button" class="btn btn-secondary btn-sm" wire:click="editFlashSale(' . $flashSale->id . ')">Edit</button><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $flashSale->id . ')">Delete</button></div>',
            ])->all(),
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->flashSaleId ? 'Edit Flash Sale' : 'Add Flash Sale',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->flashSaleId ? 'Update Flash Sale' : 'Create Flash Sale',
            'modalContentView' => 'livewire.tenant.store.partials.flash-sale-form',
            'modalContentData' => [],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->loadProducts();
        $this->showFormModal = true;
    }

    public function editFlashSale(int $flashSaleId): void
    {
        $flashSale = FlashSale::query()->with(['product', 'products'])->findOrFail($flashSaleId);
        $this->flashSaleId = $flashSale->id;
        $this->productIds = $flashSale->products->pluck('id')
            ->whenEmpty(fn($collection) => $flashSale->product ? $collection->push($flashSale->product->id) : $collection)
            ->map(fn($id) => (string) $id)
            ->all();
        $this->discountPercentage = number_format((float) $flashSale->discount_percentage, 2, '.', '');
        $this->startDate = optional($flashSale->start_date)->format('Y-m-d\TH:i');
        $this->endDate = optional($flashSale->end_date)->format('Y-m-d\TH:i');
        $this->active = $flashSale->active;
        $this->bannerImagePath = $flashSale->banner_url;

        $intIds = array_map('intval', $this->productIds);
        $repo = app(TenantPanelRepository::class);
        $this->selectedProductLabels = $repo->productNamesForIds($intIds);
        $this->selectedProductImages = $repo->productImagesForIds($intIds);

        $this->loadProducts();
        $this->showFormModal = true;
    }

    public function save(TenantPanelService $service, FlashSaleMediaService $mediaService): void
    {
        $flashSaleId = $this->flashSaleId;
        $validated = $this->validate([
            'productIds' => ['required', 'array', 'min:1'],
            'productIds.*' => ['integer', 'exists:products,id'],
            'discountPercentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'active' => ['boolean'],
            'bannerImage' => ['nullable', 'image'],
        ]);

        $maxPercentage = (float) (tenant('profit_percentage') ?? 0);
        if ((float) $validated['discountPercentage'] >= $maxPercentage) {
            $this->addError('discountPercentage', "Discount percentage must be less than the store's profit percentage ({$maxPercentage}%).");
            return;
        }

        // Validate that the discount does not exceed the vendor profit margin on any product.
        $profitErrors = $this->validateProfitMargins((float) $validated['discountPercentage']);
        if (!empty($profitErrors)) {
            foreach ($profitErrors as $error) {
                $this->addError('discountPercentage', $error);
            }
            return;
        }

        $flashSale = $service->saveFlashSale([
            'product_ids' => $validated['productIds'],
            'discount_percentage' => $validated['discountPercentage'],
            'start_date' => $validated['startDate'] ?? null,
            'end_date' => $validated['endDate'] ?? null,
            'active' => $validated['active'],
            'banner_image' => $this->bannerImagePath,
            'country_id' => $this->countryId,
        ], $flashSaleId ? FlashSale::query()->findOrFail($flashSaleId) : null);

        if ($this->bannerImage) {
            $mediaService->syncBanner($flashSale, $this->bannerImage);
        }

        // If no custom banner was uploaded and no path was stored, persist the default banner URL.
        if (!$this->bannerImage && !$flashSale->banner_url) {
            $flashSale->forceFill(['banner_image' => asset('elora/assets/images/flash-sales-banner.png')])->save();
        }

        $this->closeModal();
        $this->resetForm();
        $this->toast($flashSaleId ? 'Flash sale updated successfully.' : 'Flash sale created successfully.');
    }

    public function confirmDelete(int $flashSaleId): void
    {
        $this->confirmAction('deleteFlashSale', [$flashSaleId], ['title' => 'Delete flash sale?', 'confirmButtonText' => 'Delete flash sale']);
    }

    public function deleteFlashSale(int $flashSaleId, TenantPanelService $service): void
    {
        $service->deleteModel(FlashSale::query()->findOrFail($flashSaleId));
        $this->toast('Flash sale deleted successfully.');
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetErrorBag();
    }

    protected function resetForm(): void
    {
        $this->reset(['flashSaleId', 'productIds', 'startDate', 'endDate']);
        $this->discountPercentage = '0.00';
        $this->active = true;
        $this->bannerImage = null;
        $this->bannerImagePath = asset('elora/assets/images/flash-sales-banner.png');
        $this->productSearch = '';
        $this->productSearchPage = 1;
        $this->productSearchResults = [];
        $this->productSearchImages = [];
        $this->hasMoreProducts = false;
        $this->selectedProductLabels = [];
        $this->selectedProductImages = [];
        $this->resetErrorBag();
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
            unset($this->selectedProductLabels[$id], $this->selectedProductImages[$id]);
        } else {
            $this->productIds[] = $strId;
            $this->selectedProductLabels[$id] = $this->productSearchResults[$id] ?? 'Product #' . $id;
            $this->selectedProductImages[$id] = $this->productSearchImages[$id] ?? asset('elora/assets/images/product-default.png');
        }
    }

    public function removeProduct(int $id): void
    {
        $strId = (string) $id;
        $this->productIds = array_values(array_filter($this->productIds, fn($v) => $v !== $strId));
        unset($this->selectedProductLabels[$id], $this->selectedProductImages[$id]);
    }

    protected function loadProducts(bool $append = false): void
    {
        $repo = app(TenantPanelRepository::class);
        $result = $repo->searchProducts($this->productSearch, $this->productSearchPage);

        $this->productSearchResults = $append
            ? array_merge($this->productSearchResults, $result['items'])
            : $result['items'];

        $this->hasMoreProducts = $result['has_more'];

        // Fetch thumbnail URLs for the current result page.
        $ids = array_keys($this->productSearchResults);
        $newImages = $repo->productImagesForIds($ids);
        $this->productSearchImages = $append
            ? array_merge($this->productSearchImages, $newImages)
            : $newImages;
    }

    /**
     * Validate that the flash sale discount does not exceed the vendor profit
     * margin for any of the selected products.
     *
     * @return string[]  Empty when valid; one human-readable error per failing product.
     */
    private function validateProfitMargins(float $discountPct): array
    {
        if ($discountPct <= 0 || empty($this->productIds)) {
            return [];
        }

        $products = TenantProduct::query()
            ->with('centralProduct')
            ->whereIn('id', array_map('intval', $this->productIds))
            ->get(['id', 'central_product_id', 'default_price', 'is_own_product']);

        $errors = [];

        foreach ($products as $product) {
            $sellPrice = (float) ($product->default_price ?? 0);
            if ($sellPrice <= 0) {
                continue;
            }

            // Owner cost = what the vendor owes central per unit sold.
            if ($product->is_own_product) {
                $ownerCost = 0.0;
            } else {
                $central = $product->centralProduct;
                $ownerCost = (float) ($central?->sale_price ?: $central?->base_price ?? $sellPrice);
            }

            $vendorProfit = $sellPrice - $ownerCost;   // absolute currency
            $discountAmount = $sellPrice * ($discountPct / 100);

            if ($discountAmount > $vendorProfit) {
                $name = $this->selectedProductLabels[(int) $product->id] ?? ('Product #' . $product->id);
                $maxPct = $sellPrice > 0 ? floor(($vendorProfit / $sellPrice) * 100) : 0;
                $errors[] = __("The discount for ':product' cannot exceed :max% (vendor profit margin).", [
                    'product' => $name,
                    'max' => $maxPct,
                ]);
            }
        }

        return $errors;
    }

    public function clearFilters(): void
    {
    }

    protected function productSummaryCell(FlashSale $flashSale): string
    {
        $products = $flashSale->products->isNotEmpty()
            ? $flashSale->products
            : collect($flashSale->product)->filter();

        if ($products->isEmpty()) {
            return e('No products linked');
        }

        $names = $products
            ->map(fn(\App\Models\Tenant\Product $product) => $product->translationValue('name') ?? $product->slug ?? 'Product #' . $product->id)
            ->values();

        $primary = e((string) $names->first());
        $extraCount = $names->count() - 1;
        $subtitle = $extraCount > 0
            ? '+' . $extraCount . ' more product' . ($extraCount > 1 ? 's' : '')
            : '1 product';

        return '<div class="entity-title">' . $primary . '</div><div class="entity-subtitle">' . e($subtitle) . '</div>';
    }
}
