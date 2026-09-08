<?php

namespace App\Livewire\Admin\Faq;

use App\Enums\ContentStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Faq;
use App\Repositories\FaqRepository;
use App\Services\FaqService;
use Livewire\WithPagination;

class FaqsList extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'FAQs',
            'badge' => 'Support Content',
            'description' => 'Manage frequently asked questions with translated answers and publishing controls.',
            'actionLabel' => 'Add FAQ',
            'tableTitle' => 'FAQ Entries',
            'headers' => ['Question', 'Category', 'Status', 'Updated At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $canManage = $this->hasPermission('content.faqs.manage');
        $repository = app(FaqRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'actionUrl' => $canManage ? route('admin.faqs.create') : null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Question or category'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'active' => 'Active', 'draft' => 'Draft']],
            ],
            'statistics' => [
                ['label' => 'FAQs', 'value' => number_format($stats['total']), 'caption' => 'Support entries in the central help center', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'caption' => 'Visible support content', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Categories', 'value' => number_format($stats['categories']), 'caption' => 'Question groups with content', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn($faq) => [
                '<div class="entity-title">' . e(str($faq->question)->limit(80)) . '</div>',
                e($faq->category ?: 'General'),
                '<span class="badge ' . ($faq->status === ContentStatus::Active ? 'badge-green' : 'badge-amber') . '">' . e($faq->status->label()) . '</span>',
                e($faq->updated_at?->format('M d, Y')),
                $canManage
                ? '<div class="flex gap-2"><a href="' . route('admin.faqs.edit', $faq->id) . '" class="btn btn-secondary btn-sm">Edit</a><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $faq->id . ')">Delete</button></div>'
                : '<span class="entity-subtitle">View only</span>',
            ])->all(),
            'tableDescription' => $records->total() . ' FAQ entries matched the support filters.',
        ]);
    }

    public function confirmDelete(int $faqId): void
    {
        $this->authorizePermission('content.faqs.manage');
        $this->confirmAction('deleteFaq', [$faqId], [
            'title' => 'Delete FAQ?',
            'text' => 'This support entry will be removed from the central knowledge base.',
            'confirmButtonText' => 'Delete FAQ',
        ]);
    }

    public function deleteFaq(int $faqId, FaqService $service): void
    {
        $this->authorizePermission('content.faqs.manage');
        $service->delete(Faq::query()->findOrFail($faqId));
        $this->toast('FAQ deleted successfully.');
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

