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
    public string $stockFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => __('My Products'),
            'badge' => __('Own Catalog'),
            'description' => __('Manage products you have added directly — these are not shipped from central inventory.'),
            'actionLabel' => __('Add Product'),
            'actionUrl' => route('tenant.own-products.create'),
            'tableTitle' => __('Own Products'),
            'headers' => [__('Product'), __('Price'), __('Stock'), __('Status'), __('Added'), __('Actions')],
        ];
    }

    protected function pageData(): array
    {
        $records = Product::query()
            ->with(['badges', 'variants'])
            ->where('is_own_product', true)
            ->when(filled($this->search), function ($q) {
                $q->whereHas('translations', fn($t) => $t->where('value', 'like', "%{$this->search}%"));
            })
            ->when(filled($this->statusFilter), fn($q) => $q->where('active', $this->statusFilter === 'active'))
            ->when($this->stockFilter === 'out', function ($q) {
                $q->where(function ($noVar) {
                    $noVar->whereDoesntHave('variants')->where('manage_stock', true)->where('stock', '<=', 0);
                })->orWhere(function ($hasVar) {
                    $hasVar->whereHas('variants')->whereDoesntHave('variants', fn($v) => $v->where('stock', '>', 0));
                });
            })
            ->when($this->stockFilter === 'in', function ($q) {
                $q->where(function ($noVar) {
                    $noVar->whereDoesntHave('variants')->where(function ($nv) {
                        $nv->where('manage_stock', false)->orWhere('stock', '>', 0);
                    });
                })->orWhere(function ($hasVar) {
                    $hasVar->whereHas('variants')->whereDoesntHave('variants', fn($v) => $v->where('stock', '<=', 0));
                });
            })
            ->when($this->stockFilter === 'partial', function ($q) {
                $q->whereHas('variants', fn($v) => $v->where('stock', '<=', 0))
                    ->whereHas('variants', fn($v) => $v->where('stock', '>', 0));
            })
            ->latest()
            ->paginate(15);

        $stats = [
            'total' => Product::where('is_own_product', true)->count(),
            'active' => Product::where('is_own_product', true)->where('active', true)->count(),
        ];

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => __('Search'), 'model' => 'search', 'placeholder' => __('Search by name...')],
                ['label' => __('Status'), 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => __('All'), 'active' => __('Active'), 'inactive' => __('Inactive')]],
                ['label' => __('Stock'), 'model' => 'stockFilter', 'type' => 'select', 'options' => ['' => __('All'), 'in' => __('In Stock'), 'partial' => __('Partially Out of Stock'), 'out' => __('Out of Stock')]],
            ],
            'statistics' => [
                ['label' => __('Total Own Products'), 'value' => $stats['total'], 'caption' => __('Products you added directly.'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Active'), 'value' => $stats['active'], 'caption' => __('Currently visible in your store.'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Inactive'), 'value' => $stats['total'] - $stats['active'], 'caption' => __('Disabled from your storefront.'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rowClasses' => collect($records->items())->map(fn(Product $product) => match ($product->stockStatus()) {
                'out_of_stock' => 'row-out-of-stock',
                'partial' => 'row-partial-stock',
                default => '',
            })->values()->all(),
            'rows' => collect($records->items())->map(function (Product $product) {
                $badgeTones = [
                    'featured' => 'badge-violet',
                    'recommended' => 'badge-green',
                    'best-selling' => 'badge-amber',
                    'new-in' => 'badge-cyan',
                ];
                $badgePills = $product->badges->map(fn($badge) => '<span class="badge ' . ($badgeTones[$badge->text] ?? 'badge-amber') . '" style="font-size:10px;">' . e(__(ucfirst(str_replace('-', ' ', $badge->text)))) . '</span>')->implode(' ');
                $badgeCell = $badgePills !== '' ? '<div style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px;">' . $badgePills . '</div>' : '';

                $stockStatus = $product->stockStatus();
                $titleStockBadge = match ($stockStatus) {
                    'out_of_stock' => '<span class="badge badge-red" style="font-size:10px;margin-top:4px;display:inline-block;">' . e(__('Out of Stock')) . '</span>',
                    'partial' => '<span class="badge badge-amber" style="font-size:10px;margin-top:4px;display:inline-block;">' . e(__('Partial')) . '</span>',
                    default => '',
                };

                return [
                '<div class="entity-title">' . e($product->translationValue('name') ?? $product->slug ?? __('Product #:id', ['id' => $product->id])) . '</div>'
                . '<div class="entity-subtitle">' . e(__('Own product · No central shipping')) . '</div>' . $titleStockBadge . $badgeCell,
                '<div class="entity-title">$' . e(number_format((float) $product->default_price, 2)) . '</div>',
                '<div class="entity-title">' . ($product->stock !== null ? e($product->stock) : '<span class="entity-subtitle">' . e(__('Unlimited')) . '</span>') . '</div>',
                '<span class="badge ' . ($product->active ? 'badge-green' : 'badge-amber') . '">' . e($product->active ? __('Active') : __('Inactive')) . '</span>',
                e($product->created_at?->format('M d, Y')),
                '<div class="flex gap-2 flex-wrap">'
                . '<a href="' . route('tenant.own-products.edit', $product) . '"  class="btn btn-secondary btn-sm">' . e(__('Edit')) . '</a>'
                . '<button type="button" class="btn btn-secondary btn-sm btn-danger" wire:click="deleteProduct(' . $product->id . ')" wire:confirm="Delete this product permanently?">' . e(__('Delete')) . '</button>'
                . '</div>',
                ];
            })->all(),
            'emptyTitle' => __('No own products yet'),
            'emptyCopy' => __('Click "Add Product" to create your first own product.'),
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
        $this->toast(__('Product deleted.'));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'stockFilter']);
        $this->resetPage();
    }
}
