<?php

namespace App\Repositories;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Services\Admin\TenantAdminAggregateService;
use App\Support\OrderProfitCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Pagination\Paginator as PaginationState;
use Illuminate\Support\Collection;

class OrderRepository
{
    public function __construct(protected TenantAdminAggregateService $aggregateService)
    {
    }

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->paginateCollection($this->applyFilters($filters), $perPage);
    }

    public function export(array $filters): Collection
    {
        return $this->applyFilters($filters);
    }

    protected function applyFilters(array $filters): Collection
    {
        return $this->aggregateService->orders()
            ->filter(function (object $order) use ($filters) {
                $search = trim((string) ($filters['search'] ?? ''));

                if ($search !== '') {
                    $haystack = strtolower(implode(' ', array_filter([
                        $order->order_number,
                        $order->customer_name,
                        $order->customer_email,
                        $order->tenant?->name,
                    ])));

                    if (!str_contains($haystack, strtolower($search))) {
                        return false;
                    }
                }

                if (filled($filters['status'] ?? null) && $order->status->value !== $filters['status']) {
                    return false;
                }

                if (filled($filters['payment_status'] ?? null)) {
                    $filter = (string) $filters['payment_status'];

                    if (in_array($filter, ['pending', 'unpaid'], true) && $order->payment_status !== OrderPaymentStatus::Unpaid) {
                        return false;
                    }

                    if ($filter === 'paid' && $order->payment_status !== OrderPaymentStatus::Paid) {
                        return false;
                    }
                }

                if (filled($filters['gateway'] ?? null) && ($order->payment_gateway ?: $order->payment_method) !== $filters['gateway']) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn(object $order) => $order->placed_at?->getTimestamp() ?? 0)
            ->values();
    }

    public function deliveryQueue(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        if (($filters['shipping_status'] ?? '') === 'all') {
            $filters['shipping_status'] = '';
        }

        $records = $this->aggregateService->orders()
            ->filter(function (object $order) use ($filters) {
                $search = trim((string) ($filters['search'] ?? ''));

                if ($search !== '') {
                    $haystack = strtolower(implode(' ', array_filter([
                        $order->order_number,
                        $order->customer_name,
                        $order->tenant?->name,
                    ])));

                    if (!str_contains($haystack, strtolower($search))) {
                        return false;
                    }
                }

                return !filled($filters['shipping_status'] ?? null)
                    || $order->shipping_status->value === $filters['shipping_status'];
            })
            ->sortByDesc(fn(object $order) => $order->placed_at?->getTimestamp() ?? 0)
            ->values();

        return $this->paginateCollection($records, $perPage);
    }

    public function stats(): array
    {
        $orders = $this->aggregateService->orders();

        return [
            'total' => $orders->count(),
            'paid' => $orders->where('payment_status', OrderPaymentStatus::Paid)->count(),
            'shipping' => $orders->filter(fn(object $order) => in_array($order->shipping_status, [OrderShippingStatus::Pending, OrderShippingStatus::InDelivery], true))->count(),
            'gross_revenue' => (float) $orders->sum('total_amount'),
            'owner_profit' => (float) $orders->sum('owner_profit'),
        ];
    }

    public function reportSummary(): array
    {
        $orders = $this->aggregateService->orders();

        return [
            'gross_revenue' => (float) $orders->sum('total_amount'),
            'collected_revenue' => (float) $orders->where('payment_status', OrderPaymentStatus::Paid)->sum('total_amount'),
            'owner_profit' => (float) $orders->where('payment_status', OrderPaymentStatus::Paid)->sum('owner_profit'),
            'paid_orders' => $orders->where('payment_status', OrderPaymentStatus::Paid)->count(),
            'cancelled_orders' => $orders->where('status', OrderStatus::Cancelled)->count(),
        ];
    }

    public function paymentMethodOptions(): array
    {
        return ['' => 'All gateways'] + $this->aggregateService->orders()
            ->map(fn(object $order) => $order->payment_gateway ?: $order->payment_method)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn(string $gateway) => [$gateway => $gateway])
            ->all();
    }

    public function find(string $tenantId, string $orderNumber): ?object
    {
        return $this->aggregateService->orders()
            ->first(fn(object $order) => (string) $order->tenant_id === $tenantId && (string) $order->order_number === $orderNumber);
    }

    public function orderDetail(object $order): array
    {
        return [
            'tenant' => [
                'id' => $order->tenant_id,
                'name' => $order->store_name,
                'email' => $order->tenant?->email,
            ],
            'tenant_id' => $order->tenant_id,
            'uuid' => $order->order_number,
            'status_value' => $order->status->value,
            'shipping_status_value' => $order->shipping_status->value,
            'status' => $order->status->label(),
            'shipping_status' => $order->shipping_status->label(),
            'paid' => $order->payment_status === OrderPaymentStatus::Paid,
            'customer' => [
                'name' => $order->customer_name ?: 'Guest',
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
            'payment_gateway' => $order->payment_gateway,
            'payment_method' => $order->payment_method,
            'payment_reference' => $order->payment_reference,
            'vendor_cost' => $order->vendor_cost,
            'tracking_number' => $order->tracking_number ?? null,
            'created_at' => $order->created_at,
            'shipping_address' => $order->shipping_address,
            'payment_details' => $order->payment_details,
            'financials' => [
                'subtotal' => $order->subtotal,
                'items_discount' => $order->items_discount ?? collect($order->items)->sum('discount'),
                'discount' => $order->discount_amount,
                'items_tax' => $order->items_tax ?? collect($order->items)->sum('tax'),
                'tax' => $order->tax_amount,
                'shipping_total' => $order->shipping_charge,
                'grand_total' => $order->total_amount,
                'owner_profit' => OrderProfitCalculator::effectiveOwnerProfit($order->vendor_gateway_id, $order->vendor_cost, $order->owner_profit),
                'vendor_net_total' => $order->vendor_net_total,
                'tenant_own_central' => $order->tenant_own_central,
                'central_own_tenant' => $order->central_own_tenant,
                'remaining_owner_profit' => $order->remaining_owner_profit,
                'remaining_tenant_profit' => $order->remaining_tenant_profit,
            ],
            'items' => $order->items,
            'activities' => $order->activities,
        ];
    }

    public function reportOverview(): array
    {
        $orders = $this->aggregateService->orders();
        $summary = $this->reportSummary();
        $months = collect(range(5, 0))
            ->map(fn(int $offset) => Carbon::now()->startOfMonth()->subMonths($offset))
            ->values();

        $series = $months->map(function (Carbon $month) use ($orders) {
            $monthlyOrders = $orders->filter(
                fn(object $order) => $order->placed_at?->year === $month->year && $order->placed_at?->month === $month->month
            );

            return [
                'label' => $month->format('M'),
                'gross' => round((float) $monthlyOrders->sum('total_amount'), 2),
                'owner_profit' => round((float) $monthlyOrders->where('payment_status', OrderPaymentStatus::Paid)->sum('owner_profit'), 2),
                'paid_orders' => $monthlyOrders->where('payment_status', OrderPaymentStatus::Paid)->count(),
                'unpaid_orders' => $monthlyOrders->reject(fn(object $order) => $order->payment_status === OrderPaymentStatus::Paid)->count(),
            ];
        })->values();

        $statusBreakdown = collect(OrderStatus::cases())
            ->map(fn(OrderStatus $status) => [
                'label' => $status->label(),
                'value' => $orders->where('status', $status)->count(),
            ])
            ->filter(fn(array $row) => $row['value'] > 0)
            ->values();

        $topTenants = $orders
            ->groupBy('tenant_id')
            ->map(function (Collection $tenantOrders) {
                $first = $tenantOrders->first();

                return [
                    'tenant' => $first?->store_name ?? 'Unknown store',
                    'orders' => $tenantOrders->count(),
                    'gross' => round((float) $tenantOrders->sum('total_amount'), 2),
                    'owner_profit' => round((float) $tenantOrders->where('payment_status', OrderPaymentStatus::Paid)->sum('owner_profit'), 2),
                ];
            })
            ->sortByDesc('gross')
            ->take(6)
            ->values();

        $gatewayMix = $orders
            ->groupBy(fn(object $order) => $order->payment_gateway ?: $order->payment_method ?: 'Unknown')
            ->map(fn(Collection $gatewayOrders, string $gateway) => [
                'gateway' => $gateway,
                'orders' => $gatewayOrders->count(),
                'gross' => round((float) $gatewayOrders->sum('total_amount'), 2),
            ])
            ->sortByDesc('gross')
            ->take(6)
            ->values();

        $recentOrders = $orders
            ->sortByDesc(fn(object $order) => $order->placed_at?->getTimestamp() ?? 0)
            ->take(8)
            ->values();

        return [
            'cards' => [
                ['label' => 'Gross Revenue', 'value' => $summary['gross_revenue'], 'format' => 'currency', 'caption' => 'All tenant marketplace orders', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Collected', 'value' => $summary['collected_revenue'], 'format' => 'currency', 'caption' => 'Paid orders only', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Owner Profit', 'value' => $summary['owner_profit'], 'format' => 'currency', 'caption' => 'From paid tenant orders', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Paid Orders', 'value' => $summary['paid_orders'], 'format' => 'number', 'caption' => 'Confirmed marketplace orders', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
            ],
            'chartPayload' => [
                'revenueLabels' => $series->pluck('label')->all(),
                'revenueDatasets' => [
                    ['label' => 'Gross Sales', 'data' => $series->pluck('gross')->all(), 'color' => 'cyan', 'fill' => true, 'format' => 'currency'],
                    ['label' => 'Owner Profit', 'data' => $series->pluck('owner_profit')->all(), 'color' => 'green', 'fill' => true, 'format' => 'currency'],
                ],
                'donutLabels' => $statusBreakdown->pluck('label')->all(),
                'donutData' => $statusBreakdown->pluck('value')->all(),
                'barLabels' => $topTenants->pluck('tenant')->all(),
                'barDatasets' => [
                    ['label' => 'Sales', 'data' => $topTenants->pluck('gross')->all(), 'color' => 'amber', 'format' => 'currency'],
                    ['label' => 'Owner Profit', 'data' => $topTenants->pluck('owner_profit')->all(), 'color' => 'green', 'format' => 'currency'],
                ],
                'lineLabels' => $series->pluck('label')->all(),
                'lineDatasets' => [
                    ['label' => 'Paid Orders', 'data' => $series->pluck('paid_orders')->all(), 'color' => 'violet', 'format' => 'number'],
                    ['label' => 'Unpaid Orders', 'data' => $series->pluck('unpaid_orders')->all(), 'color' => 'cyan', 'format' => 'number', 'dashed' => true],
                ],
                'radarLabels' => $gatewayMix->pluck('gateway')->all(),
                'radarDatasets' => [
                    ['label' => 'Gateway Mix', 'data' => $gatewayMix->pluck('orders')->all(), 'color' => 'violet', 'format' => 'number'],
                ],
            ],
            'chartSections' => [
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Revenue vs Owner Profit', 'description' => 'Rolling six-month marketplace sales and owner take.', 'canvas' => 'revenueChart'],
                        ['title' => 'Order Status Mix', 'description' => 'How tenant orders are distributed by lifecycle state.', 'canvas' => 'donutChart', 'legend' => $statusBreakdown->map(fn(array $row) => ['label' => $row['label'], 'value' => number_format((int) $row['value'])])->all()],
                    ],
                ],
                [
                    'layoutClass' => 'g-r2',
                    'cards' => [
                        ['title' => 'Top Tenants', 'description' => 'Stores driving the largest gross sales volume.', 'canvas' => 'barChart'],
                        ['title' => 'Paid vs Unpaid Orders', 'description' => 'Monthly order flow split by payment state.', 'canvas' => 'lineChart'],
                    ],
                ],
                [
                    'layoutClass' => 'g-r1',
                    'cards' => [
                        ['title' => 'Gateway Order Distribution', 'description' => 'Order counts grouped by tenant payment gateway.', 'canvas' => 'radarChart', 'metrics' => $gatewayMix->map(fn(array $row) => ['label' => $row['gateway'], 'value' => '$' . number_format((float) $row['gross'], 2) . ' / ' . number_format((int) $row['orders']) . ' orders'])->all()],
                    ],
                ],
            ],
            'tableSections' => [
                [
                    'title' => 'Top Tenants',
                    'description' => 'Highest-contributing stores by gross marketplace sales.',
                    'headers' => ['Tenant', 'Orders', 'Gross', 'Owner Profit'],
                    'rows' => $topTenants->map(fn(array $row) => [
                        e($row['tenant']),
                        number_format((int) $row['orders']),
                        '$' . number_format((float) $row['gross'], 2),
                        '$' . number_format((float) $row['owner_profit'], 2),
                    ])->all(),
                ],
                [
                    'title' => 'Recent Marketplace Orders',
                    'description' => 'Latest paid and unpaid orders coming from tenant stores.',
                    'headers' => ['Order', 'Tenant', 'Customer', 'Total', 'Payment', 'Status'],
                    'rows' => $recentOrders->map(fn(object $order) => [
                        e($order->order_number),
                        e($order->store_name),
                        '<div class="entity-title">' . e($order->customer_name ?: 'Guest') . '</div><div class="entity-subtitle">' . e($order->customer_email ?: 'No email') . '</div>',
                        '$' . number_format((float) $order->total_amount, 2),
                        '<span class="badge ' . ($order->payment_status === OrderPaymentStatus::Paid ? 'badge-green' : 'badge-amber') . '">' . e($order->payment_status->label()) . '</span>',
                        '<span class="badge badge-cyan">' . e($order->status->label()) . '</span>',
                    ])->all(),
                ],
            ],
        ];
    }

    protected function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = PaginationState::resolveCurrentPage() ?: 1;
        $results = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new Paginator(
            $results,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => PaginationState::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }
}
