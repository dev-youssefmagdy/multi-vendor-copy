<?php

namespace App\Livewire\Tenant\Badge;

use App\Livewire\Tenant\Base\ListPage;
use App\Models\Tenant\ProductBadge;
use Livewire\WithPagination;

class BadgesList extends ListPage
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search');
        $this->resetPage();
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Product Badges',
            'badge' => 'Catalog',
            'description' => 'Synced badge definitions from central admin. Product assignments are managed centrally and propagated to this tenant.',
            'actionLabel' => null,
            'tableTitle' => 'Badges',
            'headers' => ['Badge', 'Products', 'Status', 'Updated At'],
        ];
    }

    protected function pageData(): array
    {
        $records = ProductBadge::query()
            ->when($this->search !== '', fn($query) => $query->where('text', 'like', '%' . $this->search . '%'))
            ->withCount('products')
            ->orderBy('text')
            ->paginate(20);

        $total = ProductBadge::query()->count();
        $active = ProductBadge::query()->where('active', true)->count();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Badge text'],
            ],
            'statistics' => [
                ['label' => 'Total Badges', 'value' => $total, 'caption' => 'Central badge definitions synced into this tenant', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active Badges', 'value' => $active, 'caption' => 'Visible on storefront when assigned to products', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'rows' => collect($records->items())->map(function (ProductBadge $badge) {
                return [
                    '<div class="entity-title">' . e($badge->text) . '</div>',
                    e((string) $badge->products_count),
                    '<span class="badge ' . ($badge->active ? 'badge-green' : 'badge-amber') . '">'
                    . e($badge->active ? 'Active' : 'Inactive') . '</span>',
                    e($badge->updated_at?->format('M d, Y')),
                ];
            })->all(),
            'tableDescription' => $records->total() . ' synced badges in the tenant catalog.',
            'filtersNote' => 'Badges and their product assignments are synced from central admin.',
        ]);
    }
}
