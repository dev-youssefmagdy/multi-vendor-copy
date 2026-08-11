<?php

namespace App\Livewire\Tenant\Manufacturing;

use App\Enums\ManufacturingRequestStatus;
use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\ManufacturingRequest;
use App\Repositories\Tenant\TenantPanelRepository;

class AddManufacturingRequest extends TenantPage
{
    use InteractsWithTenantUi;

    public string $productName = '';
    public string $description = '';
    public int $quantity = 1;
    public ?int $linkedProductId = null;
    public string $linkedProductName = '';

    // Product search state
    public string $productSearch = '';
    public int $productSearchPage = 1;
    public array $productSearchResults = [];
    public bool $hasMoreProducts = false;

    protected function pageMeta(): array
    {
        return [
            'pageTitle' => 'New Manufacturing Request',
            'badge' => 'Manufacturing',
            'pageDescription' => 'Submit a new manufacturing request. Our team will review it and update the status.',
        ];
    }

    public function mount(): void
    {
        $this->loadProducts();
    }

    protected function pageData(): array
    {
        return parent::pageData();
    }

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

    public function selectProduct(int $id): void
    {
        $this->linkedProductId = $id;
        $this->linkedProductName = $this->productSearchResults[$id] ?? 'Product #' . $id;
        $this->productSearch = '';
        $this->productSearchPage = 1;
        $this->productSearchResults = [];
        $this->hasMoreProducts = false;
    }

    public function clearLinkedProduct(): void
    {
        $this->linkedProductId = null;
        $this->linkedProductName = '';
        $this->loadProducts();
    }

    protected function loadProducts(bool $append = false): void
    {
        $result = app(TenantPanelRepository::class)->searchProducts(
            $this->productSearch,
            $this->productSearchPage,
        );

        $this->productSearchResults = $append
            ? array_merge($this->productSearchResults, $result['items'])
            : $result['items'];

        $this->hasMoreProducts = $result['has_more'];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.manufacturing.add-manufacturing-request';
    }

    public function save(): void
    {
        $this->validate([
            'productName' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'quantity' => 'required|integer|min:1|max:99999',
            'linkedProductId' => 'nullable|integer|exists:products,id',
        ]);

        ManufacturingRequest::create([
            'tenant_id' => tenant('id'),
            'product_id' => $this->linkedProductId,
            'product_name' => $this->productName,
            'description' => $this->description ?: null,
            'quantity' => $this->quantity,
            'status' => ManufacturingRequestStatus::Pending->value,
        ]);

        session()->flash('status', 'Manufacturing request submitted successfully. We will update you when the status changes.');
        $this->redirect(route('tenant.manufacturing.index'));
    }
}
