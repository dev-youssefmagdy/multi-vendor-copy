<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Finance;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Order;
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
                ['label' => 'Orders', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Orders with a product cost owed to central.', 'dot' => 'dot-cyan'],
                ['label' => 'Unsettled', 'value' => $stats['unsettled_count'], 'format' => 'number', 'caption' => 'Orders not yet paid to central.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Amount Owed', 'value' => $stats['total_owed'], 'format' => 'currency', 'caption' => 'Total product + shipping cost still outstanding.', 'dot' => 'dot-red'],
                ['label' => 'Total Settled', 'value' => $stats['total_settled'], 'format' => 'currency', 'caption' => 'Total paid to central including gateway fees.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('uuid', 'Order'),
                TableColumn::make('customer', 'Customer')->orderable(false),
                TableColumn::make('paid_via', 'Paid Via')->orderable(false)->searchable(false),
                TableColumn::make('product_cost', 'Product Cost')->name('vendor_cost'),
                TableColumn::make('shipping', 'Shipping')->name('shipping_charge'),
                TableColumn::make('gateway_fee', 'Gateway Fee')->orderable(false)->searchable(false),
                TableColumn::make('total_due', 'Total Due')->orderable(false)->searchable(false),
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
            ->editColumn('product_cost', fn (Order $order) => '$'.number_format((float) $order->vendor_cost, 2))
            ->editColumn('shipping', fn (Order $order) => '$'.number_format((float) $order->shipping_charge, 2))
            ->editColumn('gateway_fee', fn (Order $order) => view('tenant.pages.finance.vendor-purchases._cols.gateway-fee', ['order' => $order])->render())
            ->editColumn('total_due', fn (Order $order) => '<strong>$'.number_format($order->vendor_total_due, 2).'</strong>')
            ->editColumn('settlement', fn (Order $order) => view('tenant.pages.finance.vendor-purchases._cols.settlement', ['order' => $order])->render())
            ->addColumn('actions', fn (Order $order) => view('tenant.pages.finance.vendor-purchases._cols.actions', ['order' => $order])->render())
            ->rawColumns(['uuid', 'customer', 'paid_via', 'gateway_fee', 'total_due', 'settlement', 'actions'])
            ->toJson();
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request, ['search', 'settled']);

        $headers = ['Order UUID', 'Customer', 'Customer Email', 'Items', 'Payment Method', 'Product Cost', 'Shipping', 'Total Due', 'Settled', 'Settled At', 'Settlement Ref', 'Placed At'];

        $rows = $this->repo->exportVendorPurchases($filters)->map(fn (Order $order) => [
            $order->uuid,
            $order->customer?->full_name ?? 'Guest',
            $order->customer?->email ?? '',
            $order->items_count,
            ucwords(str_replace('_', ' ', $order->payment_method ?? 'cod')),
            number_format((float) $order->vendor_cost, 2),
            number_format((float) $order->shipping_charge, 2),
            number_format((float) $order->vendor_total_due, 2),
            $order->vendor_settled ? 'Yes' : 'No',
            $order->vendor_settled_at?->format('Y-m-d') ?? '',
            $order->vendor_settlement_ref ?? '',
            $order->created_at?->format('Y-m-d H:i') ?? '',
        ]);

        return $this->streamCsv('vendor-purchases-'.now()->format('Y-m-d').'.csv', $headers, $rows);
    }
}
