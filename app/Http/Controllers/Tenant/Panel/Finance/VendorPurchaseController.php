<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Finance;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Order;
use App\Support\OrderProfitCalculator;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class VendorPurchaseController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
    ) {
    }

    public function index(Request $request): View
    {
        $stats = $this->repo->vendorPurchaseStats();

        return view('tenant.pages.finance.vendor-purchases.index', [
            'stats' => Metric::cards([
                ['label' => 'Orders', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Vendor-collected orders with a platform share.', 'dot' => 'dot-cyan'],
                ['label' => 'Unsettled', 'value' => $stats['unsettled_count'], 'format' => 'number', 'caption' => 'Orders with a platform share still unpaid.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Amount Owed', 'value' => $stats['total_owed'], 'format' => 'currency', 'caption' => 'Total platform profit still owed to the platform.', 'dot' => 'dot-red'],
                ['label' => 'Total Settled', 'value' => $stats['total_settled'], 'format' => 'currency', 'caption' => 'Total paid to the platform.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('uuid', 'Order'),
                TableColumn::make('customer', 'Customer')->orderable(false),
                TableColumn::make('paid_via', 'Paid Via')->orderable(false)->searchable(false),
                TableColumn::make('platform_profit', 'Platform Profit')->name('platform_profit'),
                TableColumn::make('paid', 'Paid')->orderable(false)->searchable(false),
                TableColumn::make('remaining', 'Remaining')->orderable(false)->searchable(false),
                TableColumn::make('gateway_fee', 'Gateway Fee')->orderable(false)->searchable(false),
                TableColumn::make('settlement', 'Settlement')->orderable(false)->searchable(false),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'settled']);

        return DataTables::eloquent($this->repo->queryVendorPurchases($filters))
            ->addIndexColumn()
            ->editColumn('uuid', fn (Order $order) => view('tenant.pages.finance.vendor-purchases._cols.order', ['order' => $order])->render())
            ->editColumn('customer', fn (Order $order) => view('tenant.pages.finance.vendor-purchases._cols.customer', ['order' => $order])->render())
            ->editColumn('paid_via', fn (Order $order) => view('tenant.pages.finance.vendor-purchases._cols.paid-via', ['order' => $order])->render())
            ->editColumn('platform_profit', fn (Order $order) => '$'.number_format(OrderProfitCalculator::platformProfitForOrder($order), 2))
            ->addColumn('paid', fn (Order $order) => '$'.number_format($order->vendor_settlements_paid, 2))
            ->addColumn('remaining', fn (Order $order) => '<strong>$'.number_format(OrderProfitCalculator::vendorOwnPlatformForOrder($order), 2).'</strong>')
            ->addColumn('gateway_fee', fn (Order $order) => view('tenant.pages.finance.vendor-purchases._cols.gateway-fee', ['order' => $order])->render())
            ->editColumn('settlement', fn (Order $order) => view('tenant.pages.finance.vendor-purchases._cols.settlement', ['order' => $order])->render())
            ->addColumn('actions', fn (Order $order) => view('tenant.pages.finance.vendor-purchases._cols.actions', ['order' => $order])->render())
            ->rawColumns(['uuid', 'customer', 'paid_via', 'remaining', 'gateway_fee', 'settlement', 'actions'])
            ->toJson();
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request, ['search', 'settled']);

        $headers = ['Order UUID', 'Customer', 'Customer Email', 'Items', 'Payment Method', 'Platform Profit', 'Paid', 'Remaining', 'Settled', 'Placed At'];

        $rows = $this->repo->exportVendorPurchases($filters)->map(fn (Order $order) => [
            $order->uuid,
            $order->customer?->full_name ?? 'Guest',
            $order->customer?->email ?? '',
            $order->items_count,
            ucwords(str_replace('_', ' ', $order->payment_method ?? 'cod')),
            number_format(OrderProfitCalculator::platformProfitForOrder($order), 2),
            number_format($order->vendor_settlements_paid, 2),
            number_format(OrderProfitCalculator::vendorOwnPlatformForOrder($order), 2),
            OrderProfitCalculator::vendorOwnPlatformForOrder($order) > 0 ? 'No' : 'Yes',
            $order->created_at?->format('Y-m-d H:i') ?? '',
        ]);

        return $this->streamCsv('vendor-purchases-'.now()->format('Y-m-d').'.csv', $headers, $rows);
    }
}
