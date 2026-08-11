<?php

namespace App\Livewire\Admin\Blog;

use App\Enums\ContentStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\BlogCategory;
use App\Repositories\BlogCategoryRepository;
use App\Services\BlogCategoryService;
use Livewire\WithPagination;

class BlogCategoriesList extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Blog Categories',
            'badge' => 'Content',
            'description' => 'Manage translated blog categories for the central marketing and help content system.',
            'actionLabel' => 'Add Category',
            'tableTitle' => 'Blog Categories',
            'headers' => ['Category', 'Status', 'Posts Count', 'Updated At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $canManage = $this->hasPermission('content.blog-categories.manage');
        $repository = app(BlogCategoryRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'actionUrl' => $canManage ? route('admin.blog.categories.create') : null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Name or slug'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'active' => 'Active', 'draft' => 'Draft']],
            ],
            'statistics' => [
                ['label' => 'Categories', 'value' => number_format($stats['total']), 'caption' => 'Content grouping records', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'caption' => 'Visible content categories', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Posts Linked', 'value' => number_format($stats['posts']), 'caption' => 'Posts assigned to categories', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn($category) => [
                '<div class="entity-title">' . e($category->name) . '</div><div class="entity-subtitle">/' . e($category->slug) . '</div>',
                '<span class="badge ' . ($category->status === ContentStatus::Active ? 'badge-green' : 'badge-amber') . '">' . e($category->status->label()) . '</span>',
                e((string) $category->posts_count),
                e($category->updated_at?->format('M d, Y')),
                $canManage
                ? '<div class="flex gap-2"><a href="' . route('admin.blog.categories.edit', $category->id) . '" class="btn btn-secondary btn-sm">Edit</a><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $category->id . ')">Delete</button></div>'
                : '<span class="entity-subtitle">View only</span>',
            ])->all(),
            'tableDescription' => $records->total() . ' blog categories matched the content filters.',
        ]);
    }

    public function confirmDelete(int $categoryId): void
    {
        $this->authorizePermission('content.blog-categories.manage');
        $this->confirmAction('deleteCategory', [$categoryId], [
            'title' => 'Delete blog category?',
            'text' => 'Linked posts will lose this category assignment.',
            'confirmButtonText' => 'Delete category',
        ]);
    }

    public function deleteCategory(int $categoryId, BlogCategoryService $service): void
    {
        $this->authorizePermission('content.blog-categories.manage');
        $service->delete(BlogCategory::query()->findOrFail($categoryId));
        $this->toast('Blog category deleted successfully.');
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }
}

