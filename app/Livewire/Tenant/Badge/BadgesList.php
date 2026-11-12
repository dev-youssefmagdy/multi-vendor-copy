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
            'title' => __('Product Badges'),
            'badge' => __('Catalog'),
            'description' => __('Synced badge definitions from central admin. Product assignments are managed centrally and propagated to this tenant.'),
            'actionLabel' => null,
            'tableTitle' => __('Badges'),
            'headers' => [__('Badge'), __('Products'), __('Status'), __('Updated At')],
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
                ['label' => __('Search'), 'model' => 'search', 'placeholder' => __('Badge text')],
            ],
            'statistics' => [
                ['label' => __('Total Badges'), 'value' => $total, 'caption' => __('Central badge definitions synced into this tenant'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Active Badges'), 'value' => $active, 'caption' => __('Visible on storefront when assigned to products'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'rows' => collect($records->items())->map(function (ProductBadge $badge) {
                return [
                    '<div class="entity-title">' . e($badge->text) . '</div>',
                    e((string) $badge->products_count),
                    '<span class="badge ' . ($badge->active ? 'badge-green' : 'badge-amber') . '">'
                    . e($badge->active ? __('Active') : __('Inactive')) . '</span>',
                    e($badge->updated_at?->format('M d, Y')),
                ];
            })->all(),
            'tableDescription' => $records->total() . ' synced badges in the tenant catalog.',
            'filtersNote' => __('Badges and their product assignments are synced from central admin.'),
        ]);
    }
}
