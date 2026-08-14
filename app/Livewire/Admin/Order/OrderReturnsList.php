<?php

namespace App\Livewire\Admin\Order;

use App\Enums\ReturnStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Models\ReturnRequest;
use App\Models\Tenant;
use Livewire\WithPagination;

class OrderReturnsList extends ListPage
{
    use WithPagination;

    protected bool $exportable = true;

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
        ];
    }

    protected function pageData(): array
    {
        $query = ReturnRequest::query()->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('tenant_id', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $records = $query->paginate(20);

        $tenantNames = Tenant::whereIn('id', $records->pluck('tenant_id')->unique())->pluck('name', 'id');

        $stats = [
            'total'    => ReturnRequest::count(),
            'pending'  => ReturnRequest::where('status', ReturnStatus::Pending->value)->count(),
            'approved' => ReturnRequest::where('status', ReturnStatus::Approved->value)->count(),
            'refunded' => ReturnRequest::where('status', ReturnStatus::Refunded->value)->count(),
        ];

        $statusOptions = ['' => 'All statuses'];
        foreach (ReturnStatus::cases() as $case) {
            $statusOptions[$case->value] = $case->label();
        }

        return array_merge(parent::pageData(), [
            'records'    => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Order number, tenant…'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => $statusOptions],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Returns', 'value' => $stats['total'],   'format' => 'number', 'caption' => 'All return requests',      'dot' => 'dot-cyan',  'glow' => 'card-glow-cyan'],
                ['label' => 'Pending Review','value' => $stats['pending'],  'format' => 'number', 'caption' => 'Awaiting admin decision',  'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Approved',      'value' => $stats['approved'],'format' => 'number', 'caption' => 'Returns accepted',          'dot' => 'dot-blue',  'glow' => 'card-glow-blue'],
                ['label' => 'Refunded',      'value' => $stats['refunded'],'format' => 'number', 'caption' => 'Amount returned',           'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => $records->map(fn(ReturnRequest $r) => [
                '<div class="entity-title">' . e($r->order_number) . '</div>',
                '<div class="entity-subtitle">' . e($tenantNames[$r->tenant_id] ?? $r->tenant_id) . '</div>',
                $this->statusBadge($r->status),
                '<div class="entity-subtitle">' . e($r->reason->label()) . '</div>',
                $r->refund_amount ? '<div class="entity-title">$' . number_format((float) $r->refund_amount, 2) . '</div>' : '<span class="entity-subtitle">—</span>',
                '<div class="entity-subtitle">' . $r->created_at?->format('M d, Y') . '</div>',
                '<a href="' . route('admin.orders.returns.show', $r->id) . '" class="link-btn">Review</a>',
            ])->all(),
        ]);
    }

    private function statusBadge(ReturnStatus $status): string
    {
        $class = match ($status->color()) {
            'green' => 'badge-green',
            'blue'  => 'badge-blue',
            'red'   => 'badge-red',
            'gray'  => 'badge-gray',
            default => 'badge-yellow',
        };
        return '<span class="badge ' . $class . '">' . e($status->label()) . '</span>';
    }

    protected function exportHeaders(): array
    {
        return ['ID', 'Order Number', 'Tenant', 'Status', 'Reason', 'Refund Amount', 'Created At'];
    }

    protected function exportRows(): array
    {
        $records = ReturnRequest::query()->latest()->get();
        $tenantNames = Tenant::whereIn('id', $records->pluck('tenant_id')->unique())->pluck('name', 'id');

        return $records->map(fn(ReturnRequest $r) => [
            $r->id,
            $r->order_number,
            $tenantNames[$r->tenant_id] ?? $r->tenant_id,
            $r->status->label(),
            $r->reason->label(),
            $r->refund_amount,
            $r->created_at?->format('Y-m-d H:i:s'),
        ])->all();
    }

    protected function exportFileName(): string
    {
        return 'return-requests-' . now()->format('Y-m-d') . '.csv';
    }
}
