<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Insights;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use Illuminate\Contracts\View\View;

final class DashboardController extends PanelController
{
    public function index(TenantPanelRepository $repository): View
    {
        $overview = $repository->dashboardOverview();

        $latestOrdersColumns = [
            ['title' => 'Order'],
            ['title' => 'Customer'],
            ['title' => 'Total'],
            ['title' => 'Gateway'],
            ['title' => 'Created'],
        ];

        $latestOrdersRows = collect($overview['latest_orders'])->map(fn ($order) => [
            e($order->uuid),
            e($order->customer?->full_name ?? 'Guest'),
            '$'.e(number_format((float) $order->grand_total, 2)),
            e($order->paymentGateway?->name ?? str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString()),
            e($order->created_at?->format('M d, Y H:i') ?? ''),
        ])->all();

        $topCustomersColumns = [
            ['title' => 'Customer'],
            ['title' => 'Orders'],
            ['title' => 'Total Spend'],
            ['title' => 'Last Order'],
        ];

        $topCustomersRows = collect($overview['top_customers'])->map(fn (array $row) => [
            '<div class="entity-title">'.e($row['name']).'</div><div class="entity-subtitle">'.e($row['email']).'</div>',
            e((string) $row['orders']),
            '$'.e(number_format((float) $row['total'], 2)),
            e(optional($row['last_order'])->format('M d, Y') ?? 'N/A'),
        ])->all();

        $topProductsColumns = [
            ['title' => 'Product'],
            ['title' => 'Units'],
            ['title' => 'Revenue'],
            ['title' => 'Gross Profit'],
            ['title' => 'Margin'],
        ];

        $topProductsRows = collect($overview['top_products'])->map(fn (array $row) => [
            e($row['name']),
            e((string) $row['sold_qty']),
            '$'.e(number_format((float) $row['revenue'], 2)),
            '$'.e(number_format((float) $row['profit'], 2)),
            e(number_format((float) $row['margin'], 2)).'%',
        ])->all();

        return view('tenant.pages.dashboard.index', [
            'cards' => Metric::cards($overview['cards']),
            'statusRows' => $overview['status_rows'],
            'chartPayload' => $overview['chart_payload'],
            'latestOrdersColumns' => $latestOrdersColumns,
            'latestOrdersRows' => $latestOrdersRows,
            'topCustomersColumns' => $topCustomersColumns,
            'topCustomersRows' => $topCustomersRows,
            'topProductsColumns' => $topProductsColumns,
            'topProductsRows' => $topProductsRows,
        ]);
    }
}
