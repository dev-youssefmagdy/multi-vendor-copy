<?php

namespace App\Livewire\Admin\Order;

use App\Enums\ReturnStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Models\OrderReturn;
use Livewire\WithPagination;

class OrderReturnsList extends ListPage
{
    use WithPagination;

    public string $search      = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title'              => 'Return Requests',
            'badge'              => 'All Tenants',
            'description'        => 'Manage product return requests from customers across all tenant stores.',
            'actionLabel'        => null,
            'filtersDescription' => 'Filter returns by status and search by order number or tenant.',
            'tableTitle'         => 'Return Requests',
            'headers'            => ['Order', 'Tenant', 'Status', 'Reason', 'Refund', 'Date', 'Actions'],
            'statistics'         => [
                ['label' => 'Total Returns', 'value' => '0', 'caption' => 'All return requests', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Pending Review', 'value' => '0', 'caption' => 'Awaiting admin decision', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Approved', 'value' => '0', 'caption' => 'Returns accepted', 'dot' => 'dot-blue', 'glow' => 'card-glow-blue'],
                ['label' => 'Refunded', 'value' => '0', 'caption' => 'Amount returned to customer', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
        ];
    }

    protected function pageData(): array
    {
        $query = OrderReturn::query()->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('tenant_id', 'like', '%' . $this->search . '%')
                  ->orWhere('reason', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $records = $query->paginate(20);

        $stats = [
            'total'   => OrderReturn::count(),
            'pending' => OrderReturn::where('status', ReturnStatus::Pending->value)->count(),
            'approved'=> OrderReturn::where('status', ReturnStatus::Approved->value)->count(),
            'refunded'=> OrderReturn::where('status', ReturnStatus::Refunded->value)->count(),
        ];

        $statusOptions = ['' => 'All statuses'];
        foreach (ReturnStatus::cases() as $case) {
            $statusOptions[$case->value] = $case->label();
        }

        return array_merge(parent::pageData(), [
            'records'    => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Order number, tenant, reason…'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => $statusOptions],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Returns', 'value' => $stats['total'],   'format' => 'number', 'caption' => 'All return requests',      'dot' => 'dot-cyan',  'glow' => 'card-glow-cyan'],
                ['label' => 'Pending Review','value' => $stats['pending'],  'format' => 'number', 'caption' => 'Awaiting admin decision',  'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Approved',      'value' => $stats['approved'],'format' => 'number', 'caption' => 'Returns accepted',          'dot' => 'dot-blue',  'glow' => 'card-glow-blue'],
                ['label' => 'Refunded',      'value' => $stats['refunded'],'format' => 'number', 'caption' => 'Amount returned',           'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => $records->map(fn(OrderReturn $r) => [
                '<div class="entity-title">' . e($r->order_number) . '</div>',
                '<div class="entity-subtitle">' . e($r->tenant_id) . '</div>',
                $this->statusBadge($r->status),
                '<div class="entity-subtitle" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' . e($r->reason) . '</div>',
                $r->refund_amount ? '<div class="entity-title">$' . number_format((float) $r->refund_amount, 2) . '</div>' : '<span class="entity-subtitle">—</span>',
                '<div class="entity-subtitle">' . $r->created_at?->format('M d, Y') . '</div>',
                '<a href="' . route('admin.orders.returns.show', $r->id) . '" class="link-btn">Review</a>',
            ])->all(),
        ]);
    }

    private function statusBadge(ReturnStatus $status): string
    {
        $class = match($status) {
            ReturnStatus::Pending  => 'badge-yellow',
            ReturnStatus::Approved => 'badge-blue',
            ReturnStatus::Rejected => 'badge-red',
            ReturnStatus::Refunded => 'badge-green',
        };
        return '<span class="badge ' . $class . '">' . e($status->label()) . '</span>';
    }
}
