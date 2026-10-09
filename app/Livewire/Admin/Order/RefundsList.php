<?php

namespace App\Livewire\Admin\Order;

use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Models\Refund;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;

/**
 * Orders → Refunds: every refund across all tenants (cancellation, return and manual) read straight
 * from the central `refunds` table (RETURN_EXCHANGE_REFUND_PLAN.md B.8 Admin).
 */
class RefundsList extends ListPage
{
    use WithPagination;

    protected bool $exportable = true;

    public string $search = '';

    public string $tenantFilter = '';

    public string $statusFilter = '';

    public string $sourceFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Refunds',
            'badge' => 'All Tenants',
            'description' => 'Track every refund issued across all tenant stores: cancellations, returns and manual refunds.',
            'actionLabel' => null,
            'filtersDescription' => 'Filter refunds by store, status, source and request date, or search by reference or order number.',
            'tableTitle' => 'Refunds',
            'headers' => ['Reference', 'Order', 'Store', 'Source', 'Amount', 'Method', 'Status', 'Requested', 'Processed'],
            'secondaryActionLabel' => 'Return Requests',
            'secondaryActionUrl' => route('admin.orders.returns.index'),
        ];
    }

    protected function pageData(): array
    {
        $records = $this->query()->latest('refunds.id')->paginate(20);

        $stats = $this->stats();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Reference or order number'],
                ['label' => 'Store', 'model' => 'tenantFilter', 'type' => 'select', 'options' => $this->tenantOptions()],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses'] + collect(RefundStatus::cases())->mapWithKeys(fn (RefundStatus $s) => [$s->value => $s->label()])->all()],
                ['label' => 'Source', 'model' => 'sourceFilter', 'type' => 'select', 'options' => ['' => 'All sources'] + collect(RefundSource::cases())->mapWithKeys(fn (RefundSource $s) => [$s->value => $s->label()])->all()],
                ['label' => 'Requested from', 'model' => 'dateFrom', 'type' => 'date'],
                ['label' => 'Requested to', 'model' => 'dateTo', 'type' => 'date'],
            ],
            'filtersNote' => 'Refunds are read from the central refunds table, so every tenant is included.',
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Refunded', 'value' => $stats['refunded_amount'], 'format' => 'currency', 'caption' => 'Completed refunds', 'dot' => 'dot-violet', 'glow' => 'card-glow-violet'],
                ['label' => 'Pending', 'value' => $stats['pending'], 'format' => 'number', 'caption' => 'Waiting to be processed', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Failed', 'value' => $stats['failed'], 'format' => 'number', 'caption' => 'Need a retry or manual completion', 'dot' => 'dot-red', 'glow' => 'card-glow-violet'],
                ['label' => 'Completed', 'value' => $stats['completed'], 'format' => 'number', 'caption' => 'Refunds completed', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'tableDescription' => $records->total().' refunds matched the current filters.',
            'emptyTitle' => 'No refunds found',
            'emptyCopy' => 'No refunds matched the current filters.',
            'rows' => $records->getCollection()->map(fn (Refund $refund) => [
                '<div class="entity-title" style="font-family:ui-monospace,monospace">'.e($refund->reference).'</div>'
                    .($refund->return_request_id
                        ? '<a class="link-btn" href="'.e(route('admin.orders.returns.show', $refund->return_request_id)).'">Return #'.(int) $refund->return_request_id.'</a>'
                        : ''),
                '<a class="link-btn" href="'.e(route('admin.orders.show', [$refund->tenant_id, $refund->order_number])).'">'.e($refund->order_number).'</a>',
                '<div class="entity-subtitle">'.e($this->tenantLabel($refund)).'</div>',
                '<div class="entity-title">'.e($refund->source->label()).'</div>'
                    .($refund->reason ? '<div class="entity-subtitle">'.e($refund->reason).'</div>' : ''),
                '<div class="entity-title">'.e($refund->currency).' '.number_format((float) $refund->amount, 2).'</div>'
                    .'<div class="entity-subtitle">Items '.number_format((float) $refund->items_amount, 2)
                    .' · Shipping '.number_format((float) $refund->shipping_amount, 2)
                    .' · Fee −'.number_format((float) $refund->return_fee, 2).'</div>',
                '<div class="entity-title">'.e($refund->refund_method->label()).'</div>'
                    .($refund->gateway ? '<div class="entity-subtitle">'.e(str($refund->gateway)->replace(['_', '-'], ' ')->headline()->toString()).'</div>' : ''),
                '<span class="badge badge-'.e($refund->status->color()).'">'.e($refund->status->label()).'</span>'
                    .($refund->failure_reason ? '<div class="entity-subtitle" style="color:var(--red)">'.e($refund->failure_reason).'</div>' : ''),
                '<div class="entity-subtitle">'.e((string) $refund->requested_at?->format('M d, Y H:i')).'</div>',
                $refund->processed_at
                    ? '<div class="entity-subtitle">'.e($refund->processed_at->format('M d, Y H:i')).'</div>'
                    : '<span class="entity-subtitle">—</span>',
            ])->all(),
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTenantFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'tenantFilter', 'statusFilter', 'sourceFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    /** Filtered refunds + the store name (single join, so no per-row tenant lookups). */
    protected function query(): Builder
    {
        $query = Refund::query()
            ->leftJoin('tenants', 'tenants.id', '=', 'refunds.tenant_id')
            ->select('refunds.*', 'tenants.data->shop_name as tenant_shop_name', 'tenants.data->name as tenant_name');

        if (($search = trim($this->search)) !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn (Builder $q) => $q->where('refunds.reference', 'like', $like)->orWhere('refunds.order_number', 'like', $like));
        }

        if ($this->tenantFilter !== '') {
            $query->where('refunds.tenant_id', $this->tenantFilter);
        }

        if (RefundStatus::tryFrom($this->statusFilter)) {
            $query->where('refunds.status', $this->statusFilter);
        }

        if (RefundSource::tryFrom($this->sourceFilter)) {
            $query->where('refunds.source', $this->sourceFilter);
        }

        if ($from = $this->parseDate($this->dateFrom)) {
            $query->where('refunds.requested_at', '>=', $from->startOfDay());
        }

        if ($to = $this->parseDate($this->dateTo)) {
            $query->where('refunds.requested_at', '<=', $to->endOfDay());
        }

        return $query;
    }

    /** @return array{refunded_amount: float, pending: int, failed: int, completed: int} */
    protected function stats(): array
    {
        $rows = Refund::query()
            ->selectRaw('status, count(*) as total, coalesce(sum(amount), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row) => $row->status->value);

        $count = fn (RefundStatus ...$statuses) => (int) collect($statuses)->sum(fn (RefundStatus $status) => (int) ($rows[$status->value]->total ?? 0));

        return [
            'refunded_amount' => round((float) ($rows[RefundStatus::Completed->value]->amount ?? 0), 2),
            'pending' => $count(RefundStatus::Pending, RefundStatus::Processing),
            'failed' => $count(RefundStatus::Failed),
            'completed' => $count(RefundStatus::Completed),
        ];
    }

    /** @return array<string, string> */
    protected function tenantOptions(): array
    {
        return ['' => 'All stores'] + Tenant::query()
            ->get(['id', 'data->shop_name as shop_name', 'data->name as name'])
            ->mapWithKeys(fn ($tenant) => [(string) $tenant->id => (string) ($tenant->shop_name ?: $tenant->name ?: $tenant->id)])
            ->sort()
            ->all();
    }

    protected function tenantLabel(Refund $refund): string
    {
        return (string) ($refund->tenant_shop_name ?: $refund->tenant_name ?: $refund->tenant_id);
    }

    private function parseDate(string $value): ?Carbon
    {
        try {
            return $value !== '' ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Neutralise spreadsheet formulas in customer-influenced text (a leading = + - @ or control char). */
    private function csvSafe(?string $value): ?string
    {
        return $value !== null && $value !== '' && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    protected function exportFileName(): string
    {
        return 'refunds-'.now()->format('Y-m-d').'.csv';
    }

    protected function exportHeaders(): array
    {
        return ['Reference', 'Store', 'Order #', 'Return ID', 'Source', 'Reason', 'Currency', 'Items', 'Shipping', 'Fee', 'Amount', 'Payment Method', 'Gateway', 'Refund Method', 'Status', 'Failure Reason', 'Approved By', 'Requested At', 'Approved At', 'Processed At'];
    }

    protected function exportRows(): array
    {
        return $this->query()->latest('refunds.id')->get()->map(fn (Refund $refund) => [
            $refund->reference,
            $this->tenantLabel($refund),
            $refund->order_number,
            $refund->return_request_id,
            $refund->source->label(),
            $this->csvSafe($refund->reason),
            $refund->currency,
            number_format((float) $refund->items_amount, 2, '.', ''),
            number_format((float) $refund->shipping_amount, 2, '.', ''),
            number_format((float) $refund->return_fee, 2, '.', ''),
            number_format((float) $refund->amount, 2, '.', ''),
            $refund->payment_method,
            $refund->gateway,
            $refund->refund_method->label(),
            $refund->status->label(),
            $this->csvSafe($refund->failure_reason),
            $this->csvSafe($refund->approved_by_name),
            $refund->requested_at?->format('Y-m-d H:i:s'),
            $refund->approved_at?->format('Y-m-d H:i:s'),
            $refund->processed_at?->format('Y-m-d H:i:s'),
        ])->all();
    }
}
