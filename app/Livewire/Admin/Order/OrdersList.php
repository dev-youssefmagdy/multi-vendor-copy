<?php

namespace App\Livewire\Admin\Order;

use App\Enums\OrderPaymentStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Repositories\OrderRepository;
use Livewire\WithPagination;

class OrdersList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $paymentFilter = '';
    public string $gatewayFilter = '';

    protected bool $exportable = true;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Orders List',
            'badge' => 'All Tenants',
            'description' => 'Track orders aggregated from all tenant databases with payment state and lifecycle status.',
            'actionLabel' => 'Export Orders',
            'filtersDescription' => 'Filter by tenant, order status, payment state, and gateway across all storefront databases.',
            'tableTitle' => 'Marketplace Orders',
            'headers' => ['Order', 'Tenant', 'Customer', 'Financials', 'Payment', 'Status', 'Actions'],
            'statistics' => [
                ['label' => 'Total Orders', 'value' => '0', 'caption' => 'Across all tenant databases', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Paid Orders', 'value' => '0', 'caption' => 'Paid through owner or tenant gateways', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Needs Shipping', 'value' => '0', 'caption' => 'Ready for delivery workflows', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(OrderRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'payment_status' => $this->paymentFilter,
            'gateway' => $this->gatewayFilter,
        ]);

        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Order, customer, or tenant'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected']],
                ['label' => 'Payment', 'model' => 'paymentFilter', 'type' => 'select', 'options' => ['' => 'All payments', 'paid' => 'Paid', 'unpaid' => 'Unpaid', 'pending' => 'Pending']],
                ['label' => 'Gateway', 'model' => 'gatewayFilter', 'type' => 'select', 'options' => $repository->paymentMethodOptions()],
            ],
            'filtersNote' => 'Track aggregated tenant order flow across all stores by lifecycle and payment state.',
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Orders', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Across all tenants', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Paid Orders', 'value' => $stats['paid'], 'format' => 'number', 'caption' => 'Confirmed payments', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Needs Shipping', 'value' => $stats['shipping'], 'format' => 'number', 'caption' => 'Pending delivery work', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Gross Revenue', 'value' => $stats['gross_revenue'], 'format' => 'currency', 'caption' => 'Marketplace sales volume', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => collect($records->items())->map(fn($order) => [
                '<div class="entity-title">' . $order->order_number . '</div><div class="entity-subtitle">'
                . $order->placed_at?->format('M d, Y H:i')
                . (!empty($order->tracking_number) ? ' · <span title="Tracking">🔖 ' . e($order->tracking_number) . '</span>' : '')
                . '</div>',
                '<div class="entity-title">' . e($order->store_name ?: 'Unknown tenant') . '</div><div class="entity-subtitle">' . e($order->tenant?->email ?? 'No email') . '</div>',
                '<div class="entity-title">' . e($order->customer_name ?: 'Guest') . '</div><div class="entity-subtitle">' . e($order->customer_email ?: 'No email') . '</div>',
                '<div class="entity-title">$' . number_format((float) $order->total_amount, 2) . '</div>'
                . '<div class="entity-subtitle">'
                . ($order->discount_percentage > 0 ? e(number_format($order->discount_percentage, 1)) . '% off · ' : '')
                . '$' . e(number_format((float) $order->owner_profit, 2)) . ' owner profit'
                . '</div>',
                '<div class="entity-title"><span class="badge ' . match ($order->payment_status) { OrderPaymentStatus::Paid => 'badge-green', OrderPaymentStatus::PendingPayment => 'badge-yellow', default => 'badge-amber'} . '">' . e($order->payment_status->label()) . '</span></div><div class="entity-subtitle">' . e($order->payment_method ?: $order->payment_gateway ?: 'Unknown gateway') . '</div>',
                '<div class="entity-title"><span class="badge badge-cyan">' . e($order->status->label()) . '</span></div><div class="entity-subtitle">' . e($order->shipping_status->label()) . '</div>',
                '<a href="' . route('admin.orders.show', [$order->tenant_id, $order->order_number]) . '" class="link-btn">View</a>',
            ])->all(),
            'tableDescription' => $records->total() . ' aggregated tenant orders matched the current filters.',
            'emptyCopy' => 'No tenant orders matched the current search and filter combination.',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedGatewayFilter(): void
    {
        $this->resetPage();
    }

    protected function exportFileName(): string
    {
        return 'orders-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return ['Order #', 'Placed At', 'Store', 'Store Email', 'Customer', 'Customer Email', 'Total', 'Owner Profit', 'Discount %', 'Payment Status', 'Payment Gateway', 'Order Status', 'Shipping Status', 'Tracking #'];
    }

    protected function exportRows(): array
    {
        $repository = app(OrderRepository::class);
        return $repository->export([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'payment_status' => $this->paymentFilter,
            'gateway' => $this->gatewayFilter,
        ])->map(fn($order) => [
                $order->order_number,
                $order->placed_at?->format('Y-m-d H:i'),
                $order->store_name ?: 'Unknown',
                $order->tenant?->email ?? '',
                $order->customer_name ?: 'Guest',
                $order->customer_email ?: '',
                number_format((float) $order->total_amount, 2),
                number_format((float) $order->owner_profit, 2),
                number_format((float) $order->discount_percentage, 2),
                $order->payment_status->label(),
                $order->payment_gateway ?: $order->payment_method ?: '',
                $order->status->label(),
                $order->shipping_status->label(),
                $order->tracking_number ?? '',
            ])->all();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'paymentFilter', 'gatewayFilter']);
        $this->resetPage();
    }


}
