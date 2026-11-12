<?php

namespace App\Livewire\Tenant\Category;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Category;
use App\Repositories\Tenant\TenantPanelRepository;
use Livewire\WithPagination;

class CategoriesList extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $ownerFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => __('Categories'),
            'badge' => __('Catalog'),
            'description' => __('Manage tenant category ordering, featured state, and translated storefront copy.'),
            'actionLabel' => __('Add Category'),
            'actionUrl' => route('tenant.categories.create'),
            'secondaryActionLabel' => __('Sort Categories'),
            'secondaryActionUrl' => route('tenant.categories.sort'),
            'tableTitle' => __('Tenant Categories'),
            'headers' => [__('Category'), __('Parent'), __('Status'), __('Products'), __('Updated At'), __('Actions')],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateCategories(['search' => $this->search, 'status' => $this->statusFilter, 'mine' => $this->ownerFilter === 'mine']);

        $stats = $repository->categoryStats();
        $centralSnapshots = $repository->centralCategorySnapshots(collect($records->items())->pluck('central_category_id')->all());

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => __('Search'), 'model' => 'search', 'placeholder' => __('Name or slug')],
                ['label' => __('Status'), 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => __('All'), 'active' => __('Active'), 'inactive' => __('Inactive')]],
                ['label' => __('Ownership'), 'model' => 'ownerFilter', 'type' => 'select', 'options' => ['' => __('All categories'), 'mine' => __('My categories only')]],
            ],
            'statistics' => [
                ['label' => __('Categories'), 'value' => number_format($stats['total']), 'caption' => __('Tenant storefront categories'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Active'), 'value' => number_format($stats['active']), 'caption' => __('Visible categories'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Featured'), 'value' => number_format($stats['featured']), 'caption' => __('Highlighted category groups'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(function (Category $category) use ($centralSnapshots) {
                $centralCategory = $centralSnapshots[$category->central_category_id] ?? null;

                $imageMarkup = !empty($centralCategory['image_url'])
                    ? '<img src="' . e($centralCategory['image_url']) . '" alt="' . e($centralCategory['name'] ?? ($category->translationValue('name') ?? $category->slug ?? __('Category'))) . '" style="width:48px;height:48px;object-fit:cover;border-radius:12px;border:1px solid rgba(255,255,255,.12);flex-shrink:0;">'
                    : '<div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);flex-shrink:0;">—</div>';

                return [
                    '<div style="display:flex;align-items:center;gap:12px;">' . $imageMarkup . '<div><div class="entity-title">' . e($category->translationValue('name') ?? $category->slug ?? __('Category #:id', ['id' => $category->id])) . '</div><div class="entity-subtitle">/' . e($category->slug) . '</div>' . ($centralCategory ? '<div class="entity-subtitle">' . e(__('Central:')) . ' ' . e($centralCategory['name']) . '</div>' : '') . '</div></div>',
                    e($category->parent?->translationValue('name') ?? __('Root')),
                    '<span class="badge ' . ($category->active ? 'badge-green' : 'badge-amber') . '">' . e($category->active ? __('Active') : __('Inactive')) . '</span>' . ($category->featured ? ' <span class="badge badge-cyan">' . e(__('Featured')) . '</span>' : ''),
                    e((string) $category->products->count()),
                    e($category->updated_at?->format('M d, Y')),
                    '<div class="flex gap-2 flex-wrap"><a href="' . route('tenant.categories.edit', $category) . '"  class="btn btn-secondary btn-sm">' . e(__('Edit')) . '</a><a href="' . route('tenant.categories.products', $category) . '" class="btn btn-secondary btn-sm">' . e(__('Sort Products')) . '</a><button type="button" class="btn btn-secondary btn-sm" wire:click="toggleActive(' . $category->id . ')">' . ($category->active ? __('Disable') : __('Enable')) . '</button><button type="button" class="btn btn-secondary btn-sm" wire:click="toggleFeatured(' . $category->id . ')">' . ($category->featured ? __('Unfeature') : __('Feature')) . '</button>' . ($category->central_category_id === null ? '<button type="button" class="btn btn-danger btn-sm" wire:click="deleteCategory(' . $category->id . ')" wire:confirm="Are you sure you want to delete this category?">' . e(__('Delete')) . '</button>' : '') . '</div>',
                ];
            })->all(),
            'tableDescription' => $records->total() . ' categories matched the storefront filters.',
        ]);
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        abort_if($category->central_category_id !== null, 403, __('Only tenant-created categories can be deleted.'));

        $category->delete();
        $this->toast(__('Category deleted successfully.'));
    }

    public function toggleActive(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);
        $category->update(['active' => !$category->active]);
        $this->toast(__('Category status updated successfully.'));
    }

    public function toggleFeatured(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);
        $category->update(['featured' => !$category->featured]);
        $this->toast(__('Category featured state updated successfully.'));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedOwnerFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'ownerFilter']);
        $this->resetPage();
    }
}
