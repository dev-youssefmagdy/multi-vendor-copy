<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Subscriber;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Livewire\WithPagination;

class SubscribersPage extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    protected bool $exportable = true;

    protected function pageMeta(): array
    {
        return [
            'title' => __('Subscribers'),
            'badge' => __('Settings'),
            'description' => __('Review and manage storefront newsletter subscribers for this tenant.'),
            'tableTitle' => __('Subscriber List'),
            'actionLabel' => null,
            'headers' => [__('Email'), __('Subscribed At'), __('Actions')],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateSubscribers();
        $stats = $repository->subscriberStats();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'statistics' => [
                ['label' => __('Subscribers'), 'value' => number_format($stats['total']), 'caption' => __('All storefront subscribers'), 'dot' => 'dot-cyan'],
                ['label' => __('This Month'), 'value' => number_format($stats['new_this_month']), 'caption' => __('New subscriber growth this month'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('This Week'), 'value' => number_format($stats['new_this_week']), 'caption' => __('Recent weekly signups'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn(Subscriber $subscriber) => [
                e($subscriber->email),
                e($subscriber->created_at?->format('M d, Y H:i')),
                '<button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $subscriber->id . ')">' . e(__('Delete')) . '</button>',
            ])->all(),
        ]);
    }

    public function confirmDelete(int $subscriberId): void
    {
        $this->confirmAction('deleteSubscriber', [$subscriberId], ['title' => __('Delete subscriber?'), 'confirmButtonText' => __('Delete subscriber')]);
    }

    public function deleteSubscriber(int $subscriberId, TenantPanelService $service): void
    {
        $service->deleteSubscriber(Subscriber::query()->findOrFail($subscriberId));
        $this->toast(__('Subscriber deleted successfully.'));
    }

    public function clearFilters(): void
    {
    }

    protected function exportFileName(): string
    {
        return 'subscribers-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return [__('Email'), __('Subscribed At')];
    }

    protected function exportRows(): array
    {
        $repository = app(TenantPanelRepository::class);
        return $repository->exportSubscribers()
            ->map(fn(Subscriber $subscriber) => [
                $subscriber->email,
                $subscriber->created_at?->format('Y-m-d H:i') ?? '',
            ])->all();
    }
}
