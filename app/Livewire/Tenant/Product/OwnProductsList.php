<?php

namespace App\Livewire\Tenant\Product;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Product;
use Livewire\WithPagination;

class OwnProductsList extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $outOfStockFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'My Products',
            'badge' => 'Own Catalog',
            'description' => 'Manage products you have added directly — these are not shipped from central inventory.',
            'actionLabel' => 'Add Product',
            'actionUrl' => route('tenant.own-products.create'),
            'tableTitle' => 'Own Products',
            'headers' => ['Product', 'Price', 'Stock', 'Status', 'Added', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $records = Product::query()
            ->with('badges')
            ->where('is_own_product', true)
            ->when(filled($this->search), function ($q) {
                $q->whereHas('translations', fn($t) => $t->where('value', 'like', "%{$this->search}%"));
            })
            ->when(filled($this->statusFilter), fn($q) => $q->where('active', $this->statusFilter === 'active'))
            ->when($this->outOfStockFilter === '1', fn($q) => $q->where('manage_stock', true)->where('stock', '<=', 0))
            ->latest()
            ->paginate(15);

        $stats = [
            'total' => Product::where('is_own_product', true)->count(),
            'active' => Product::where('is_own_product', true)->where('active', true)->count(),
        ];

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Search by name...'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']],
                ['label' => 'Stock', 'model' => 'outOfStockFilter', 'type' => 'select', 'options' => ['' => 'All stock levels', '1' => 'Out of stock']],
            ],
            'statistics' => [
                ['label' => 'Total Own Products', 'value' => $stats['total'], 'caption' => 'Products you added directly.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => $stats['active'], 'caption' => 'Currently visible in your store.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Inactive', 'value' => $stats['total'] - $stats['active'], 'caption' => 'Disabled from your storefront.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(function (Product $product) {
                $badgeTones = [
                    'featured' => 'badge-violet',
                    'recommended' => 'badge-green',
                    'best-selling' => 'badge-amber',
                    'new-in' => 'badge-cyan',
                ];
                $badgePills = $product->badges->map(fn($badge) => '<span class="badge ' . ($badgeTones[$badge->text] ?? 'badge-amber') . '" style="font-size:10px;">' . e(ucfirst(str_replace('-', ' ', $badge->text))) . '</span>')->implode(' ');
                $badgeCell = $badgePills !== '' ? '<div style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px;">' . $badgePills . '</div>' : '';

                return [
                '<div class="entity-title">' . e($product->translationValue('name') ?? $product->slug ?? 'Product #' . $product->id) . '</div>'
                . '<div class="entity-subtitle">Own product · No central shipping</div>' . $badgeCell,
                '<div class="entity-title">$' . e(number_format((float) $product->default_price, 2)) . '</div>',
                '<div class="entity-title">' . ($product->stock !== null ? e($product->stock) : '<span class="entity-subtitle">Unlimited</span>') . '</div>',
                '<span class="badge ' . ($product->active ? 'badge-green' : 'badge-amber') . '">' . e($product->active ? 'Active' : 'Inactive') . '</span>',
                e($product->created_at?->format('M d, Y')),
                '<div class="flex gap-2 flex-wrap">'
                . '<a href="' . route('tenant.own-products.edit', $product) . '"  class="btn btn-secondary btn-sm">Edit</a>'
                . '<button type="button" class="btn btn-secondary btn-sm btn-danger" wire:click="deleteProduct(' . $product->id . ')" wire:confirm="Delete this product permanently?">Delete</button>'
                . '</div>',
                ];
            })->all(),
            'emptyTitle' => 'No own products yet',
            'emptyCopy' => 'Click "Add Product" to create your first own product.',
            'tableDescription' => $records->total() . ' own products found.',
        ]);
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.pages.list-page';
    }

    public function deleteProduct(int $id): void
    {
        $product = Product::where('id', $id)->where('is_own_product', true)->firstOrFail();
        $product->delete();
        $this->toast('Product deleted.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedOutOfStockFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'outOfStockFilter']);
        $this->resetPage();
    }
}
