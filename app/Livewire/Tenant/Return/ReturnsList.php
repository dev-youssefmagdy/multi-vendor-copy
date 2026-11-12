<?php

namespace App\Livewire\Tenant\Return;

use App\Enums\ReturnStatus;
use App\Livewire\Tenant\Base\ListPage;
use App\Models\ReturnRequest;
use Livewire\WithPagination;

class ReturnsList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => __('Return Requests'),
            'badge' => __('Store'),
            'description' => __('Review and manage product return requests from your customers.'),
            'actionLabel' => null,
            'filtersDescription' => __('Filter returns by status and search by order number.'),
            'tableTitle' => __('Return Requests'),
            'headers' => [__('Order'), __('Status'), __('Reason'), __('Refund'), __('Date'), __('Actions')],
            'secondaryActionLabel' => __('Analytics'),
            'secondaryActionUrl' => route('tenant.returns.analytics'),
        ];
    }

    protected function pageData(): array
    {
        $tenantId = tenant()->id;

        $query = ReturnRequest::query()->where('tenant_id', $tenantId)->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $records = $query->paginate(20);

        $stats = [
            'total' => ReturnRequest::where('tenant_id', $tenantId)->count(),
            'pending' => ReturnRequest::where('tenant_id', $tenantId)->where('status', ReturnStatus::Pending->value)->count(),
            'approved' => ReturnRequest::where('tenant_id', $tenantId)->where('status', ReturnStatus::Approved->value)->count(),
            'refunded' => ReturnRequest::where('tenant_id', $tenantId)->where('status', ReturnStatus::Refunded->value)->count(),
        ];

        $statusOptions = ['' => __('All statuses')];
        foreach (ReturnStatus::cases() as $case) {
            $statusOptions[$case->value] = __($case->label());
        }

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => __('Search'), 'model' => 'search', 'placeholder' => __('Order number…')],
                ['label' => __('Status'), 'model' => 'statusFilter', 'type' => 'select', 'options' => $statusOptions],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => __('Total Returns'), 'value' => $stats['total'], 'format' => 'number', 'caption' => __('All return requests'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Pending Review'), 'value' => $stats['pending'], 'format' => 'number', 'caption' => __('Awaiting your decision'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => __('Approved'), 'value' => $stats['approved'], 'format' => 'number', 'caption' => __('Returns accepted'), 'dot' => 'dot-blue', 'glow' => 'card-glow-blue'],
                ['label' => __('Refunded'), 'value' => $stats['refunded'], 'format' => 'number', 'caption' => __('Amount returned'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => $records->map(fn(ReturnRequest $r) => [
                '<div class="entity-title">' . e($r->order_number) . '</div>',
                $this->statusBadge($r->status),
                '<div class="entity-subtitle">' . e(__($r->reason->label())) . '</div>',
                $r->refund_amount ? '<div class="entity-title">$' . number_format((float) $r->refund_amount, 2) . '</div>' : '<span class="entity-subtitle">—</span>',
                '<div class="entity-subtitle">' . $r->created_at?->format('M d, Y') . '</div>',
                '<a href="' . route('tenant.returns.show', $r->id) . '" class="link-btn">' . e(__('Review')) . '</a>',
            ])->all(),
        ]);
    }

    private function statusBadge(ReturnStatus $status): string
    {
        $class = match ($status->color()) {
            'green' => 'badge-green',
            'blue' => 'badge-blue',
            'red' => 'badge-red',
            'gray' => 'badge-gray',
            default => 'badge-yellow',
        };
        return '<span class="badge ' . $class . '">' . e(__($status->label())) . '</span>';
    }
}
