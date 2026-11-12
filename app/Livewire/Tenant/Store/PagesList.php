<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Page;
use App\Models\Tenant\PaymentGateway;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Livewire\WithPagination;

class PagesList extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public function mount(): void
    {
        if (!PaymentGateway::query()->where('is_active', true)->exists()) {
            $this->toast(__('Please activate at least one payment gateway before managing store pages.'), 'warning');
            $this->redirect(route('tenant.settings.payment-gateways'), navigate: true);
        }
    }

    protected function pageMeta(): array
    {
        return [
            'title' => __('Pages'),
            'badge' => __('Storefront'),
            'description' => __('Maintain storefront static pages for this tenant workspace.'),
            'actionLabel' => __('Add Page'),
            'actionUrl' => route('tenant.store.pages.create'),
            'tableTitle' => __('Store Pages'),
            'headers' => [__('Page'), __('Slug'), __('Status'), __('Updated At'), __('Actions')],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginatePages();
        $stats = $repository->pageStats();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'statistics' => [
                ['label' => __('Pages'), 'value' => number_format($stats['total']), 'caption' => __('Tenant storefront pages'), 'dot' => 'dot-cyan'],
                ['label' => __('Active'), 'value' => number_format($stats['active']), 'caption' => __('Visible published pages'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Draft'), 'value' => number_format($stats['draft']), 'caption' => __('Pages hidden from storefront'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn(Page $page) => [
                e($page->title ?? $page->slug),
                e($page->slug),
                '<span class="badge ' . ($page->active ? 'badge-green' : 'badge-amber') . '">' . e($page->active ? __('Active') : __('Draft')) . '</span>',
                e($page->updated_at?->format('M d, Y')),
                '<div class="flex gap-2"><a href="' . route('tenant.store.pages.edit', $page) . '"  class="btn btn-secondary btn-sm">' . e(__('Edit')) . '</a><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $page->id . ')">' . e(__('Delete')) . '</button></div>',
            ])->all(),
        ]);
    }

    public function confirmDelete(int $pageId): void
    {
        $this->confirmAction('deletePage', [$pageId], ['title' => __('Delete page?'), 'confirmButtonText' => __('Delete page')]);
    }

    public function deletePage(int $pageId, TenantPanelService $service): void
    {
        $service->deleteModel(Page::query()->findOrFail($pageId));
        $this->toast(__('Page deleted successfully.'));
    }

    public function clearFilters(): void
    {
    }
}
