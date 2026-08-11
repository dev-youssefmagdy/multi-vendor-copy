<?php

namespace App\Livewire\Admin\Page;

use App\Enums\ContentStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\StaticPage;
use App\Repositories\StaticPageRepository;
use App\Services\StaticPageService;
use Livewire\WithPagination;

class StaticPagesList extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Static Pages',
            'badge' => 'Content',
            'description' => 'Manage translated static pages such as about, privacy, and public-facing support content.',
            'actionLabel' => 'Add Page',
            'tableTitle' => 'Static Pages Table',
            'headers' => ['Title', 'Slug', 'Status', 'Type', 'Updated At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $canManage = $this->hasPermission('content.pages.manage');
        $repository = app(StaticPageRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'actionUrl' => $canManage ? route('admin.pages.create') : null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Title or slug'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived']],
            ],
            'statistics' => [
                ['label' => 'Pages', 'value' => number_format($stats['total']), 'caption' => 'Static content pages', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'caption' => 'Visible public pages', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Drafts', 'value' => number_format($stats['drafts']), 'caption' => 'Pending publishing', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn($page) => [
                '<div class="entity-title">' . e($page->title) . '</div><div class="entity-subtitle">/' . e($page->slug) . '</div>',
                '/' . e($page->slug),
                '<span class="badge ' . ($page->status === ContentStatus::Active ? 'badge-green' : ($page->status === ContentStatus::Archived ? 'badge-red' : 'badge-amber')) . '">' . e($page->status->label()) . '</span>',
                $page->is_compliance
                ? '<span class="badge badge-violet">Compliance</span>'
                : '<span class="badge badge-secondary">Standard</span>',
                e($page->updated_at?->format('M d, Y')),
                $canManage
                ? '<div class="flex gap-2 flex-wrap"><a href="' . route('admin.pages.edit', $page->id) . '"  class="btn btn-secondary btn-sm">Edit</a>' . ($page->status === ContentStatus::Active
                    ? '<button type="button" class="btn btn-secondary btn-sm" wire:click="archivePage(' . $page->id . ')">Archive</button>'
                    : '<button type="button" class="btn btn-secondary btn-sm" wire:click="activatePage(' . $page->id . ')">Activate</button>') . '<button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $page->id . ')">Delete</button></div>'
                : '<span class="entity-subtitle">View only</span>',
            ])->all(),
            'tableDescription' => $records->total() . ' static pages matched the current filters.',
        ]);
    }

    public function activatePage(int $pageId): void
    {
        $this->authorizePermission('content.pages.manage');
        StaticPage::query()->findOrFail($pageId)->update(['status' => ContentStatus::Active->value]);
        $this->toast('Static page activated successfully.');
    }

    public function archivePage(int $pageId): void
    {
        $this->authorizePermission('content.pages.manage');
        StaticPage::query()->findOrFail($pageId)->update(['status' => ContentStatus::Archived->value]);
        $this->toast('Static page archived successfully.');
    }

    public function confirmDelete(int $pageId): void
    {
        $this->authorizePermission('content.pages.manage');
        $this->confirmAction('deletePage', [$pageId], [
            'title' => 'Delete static page?',
            'text' => 'This public content page will be removed.',
            'confirmButtonText' => 'Delete page',
        ]);
    }

    public function deletePage(int $pageId, StaticPageService $service): void
    {
        $this->authorizePermission('content.pages.manage');
        $service->delete(StaticPage::query()->findOrFail($pageId));
        $this->toast('Static page deleted successfully.');
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
