<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Sales;

use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Exceptions\Tenant\PanelActionException;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Sales\UpdateShippingStatusRequest;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\OrderLifecycleService;
use App\Support\OrderProfitCalculator;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Yajra\DataTables\Facades\DataTables;

final class OrdersController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
    ) {
    }

    public function index(Request $request): View
    {
        $stats = $this->repo->orderStats();

        return view('tenant.pages.sales.orders.index', [
            'stats' => Metric::cards([
                ['label' => 'Orders', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Orders in this tenant database.', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Paid', 'value' => $stats['paid'], 'format' => 'number', 'caption' => 'Orders with successful payment capture.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Processing', 'value' => $stats['processing'], 'format' => 'number', 'caption' => 'Orders currently moving through fulfillment.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Collected', 'value' => $stats['collected'], 'format' => 'currency', 'caption' => 'Paid order value collected by the tenant.', 'dot' => 'dot-violet'],
            ]),
            'statusOptions' => collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status) => [$status->value => $status->label()])->all(),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('uuid', 'Order'),
                TableColumn::make('customer', 'Customer')->orderable(false),
                TableColumn::make('value', 'Value')->name('grand_total'),
                TableColumn::make('commission', 'Commission')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('gateway', 'Gateway')->orderable(false)->searchable(false),
                TableColumn::make('placed_at', 'Placed At')->name('created_at'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);

        return DataTables::eloquent($this->repo->queryOrders($filters))
            ->addIndexColumn()
            ->editColumn('uuid', fn (Order $order) => view('tenant.pages.sales.orders._cols.order', ['order' => $order])->render())
            ->editColumn('customer', fn (Order $order) => view('tenant.pages.sales.orders._cols.customer', ['order' => $order])->render())
            ->editColumn('value', fn (Order $order) => view('tenant.pages.sales.orders._cols.value', ['order' => $order])->render())
            ->editColumn('commission', fn (Order $order) => view('tenant.pages.sales.orders._cols.commission', ['order' => $order])->render())
            ->editColumn('status', fn (Order $order) => view('tenant::components.status-badge', ['status' => $order->status])->render())
            ->editColumn('gateway', fn (Order $order) => view('tenant.pages.sales.orders._cols.gateway', ['order' => $order])->render())
            ->editColumn('placed_at', fn (Order $order) => $order->created_at?->format('M d, Y H:i'))
            ->addColumn('actions', fn (Order $order) => view('tenant.pages.sales.orders._cols.actions', ['order' => $order])->render())
            ->orderColumn('value', 'grand_total $1')
            ->orderColumn('placed_at', 'created_at $1')
            ->rawColumns(['uuid', 'customer', 'value', 'commission', 'status', 'gateway', 'actions'])
            ->toJson();
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request, ['search', 'status']);

        $headers = ['Order UUID', 'Customer', 'Customer Email', 'Items', 'Grand Total', 'Discount %', 'Shipping', 'Vendor Commission', 'Status', 'Payment Method', 'Paid', 'Placed At'];

        $rows = $this->repo->exportOrders($filters)->map(fn (Order $order) => [
            $order->uuid,
            $order->customer?->full_name ?? 'Guest',
            $order->customer?->email ?? '',
            $order->items_count,
            number_format((float) $order->grand_total, 2),
            number_format((float) $order->discount_percentage, 2),
            number_format((float) $order->resolved_shipping_charge, 2),
            number_format(OrderProfitCalculator::effectiveTenantProfitForOrder($order), 2),
            $order->status->label(),
            str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString(),
            $order->paid ? 'Paid' : 'Unpaid',
            $order->created_at?->format('Y-m-d H:i') ?? '',
        ]);

        return $this->streamCsv('orders-'.now()->format('Y-m-d').'.csv', $headers, $rows);
    }

    public function show(int $orderId): View
    {
        $order = Order::query()->findOrFail($orderId);

        return view('tenant.pages.sales.orders.show', [
            'orderId' => $orderId,
            'order' => $this->repo->orderDetail($order),
            'shippingStatuses' => OrderShippingStatus::cases(),
        ]);
    }

    public function updateShippingStatus(UpdateShippingStatusRequest $request, int $orderId): JsonResponse
    {
        $order = Order::query()->findOrFail($orderId);
        $detail = $this->repo->orderDetail($order);

        if (!($detail['can_update_shipping'] ?? false)) {
            throw new PanelActionException('Shipping status can only be updated for your own products orders.', 403);
        }

        $status = OrderShippingStatus::tryFrom($request->validated('shipping_status'));

        if (!$status) {
            return $this->failure('Invalid shipping status selected.', 422);
        }

        try {
            $updated = app(OrderLifecycleService::class)->updateShippingStatus($order, $status);
        } catch (InvalidArgumentException $e) {
            return $this->failure($e->getMessage(), 422);
        }

        $freshDetail = $this->repo->orderDetail($updated);

        return $this->success('Shipping status updated successfully.', [
            'shipping_status' => $freshDetail['shipping_status'],
            'shipping_status_value' => $freshDetail['shipping_status_value'],
            'status' => $freshDetail['status'],
            'status_value' => $freshDetail['status_value'],
            'panel' => view('tenant.pages.sales.orders._partials.shipping-status', [
                'order' => $freshDetail,
                'orderId' => $orderId,
                'shippingStatuses' => OrderShippingStatus::cases(),
            ])->render(),
        ]);
    }

    public function validateUpdateShippingStatus(UpdateShippingStatusRequest $request, int $orderId): JsonResponse
    {
        return $this->validFormResponse();
    }
}
