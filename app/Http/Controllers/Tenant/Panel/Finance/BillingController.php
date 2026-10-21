<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Finance;

use App\Enums\OrderShippingStatus;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\PlanLimitService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class BillingController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
    ) {
    }

    public function index(Request $request): View
    {
        $stats = $this->repo->billingStats();

        return view('tenant.pages.finance.billing.index', [
            'stats' => Metric::cards([
                ['label' => 'Total billed', 'value' => $stats['revenue'], 'format' => 'currency', 'caption' => 'Net value of all valid orders.', 'dot' => 'dot-cyan'],
                ['label' => 'Collected', 'value' => $stats['collected'], 'format' => 'currency', 'caption' => 'Paid order value with confirmed collection.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Outstanding', 'value' => $stats['outstanding'], 'format' => 'currency', 'caption' => 'Unpaid order value still awaiting payment.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Orders', 'value' => $stats['orders'], 'format' => 'number', 'caption' => $stats['paid'].' paid · '.$stats['unpaid'].' unpaid', 'dot' => 'dot-cyan'],
                ['label' => 'Payment methods', 'value' => $stats['gateways'], 'format' => 'number', 'caption' => 'Distinct payment methods used in orders.', 'dot' => 'dot-violet'],
            ]),
            'planUsage' => $this->planUsage(),
            'gatewayOptions' => $this->repo->paymentMethodOptions(),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('uuid', 'Order'),
                TableColumn::make('customer', 'Customer')->orderable(false),
                TableColumn::make('totals', 'Totals')->name('grand_total'),
                TableColumn::make('payment', 'Payment')->orderable(false),
                TableColumn::make('gateway', 'Gateway')->name('payment_method'),
                TableColumn::make('placed_at', 'Placed At')->name('created_at'),
                TableColumn::make('payment_details', 'Payment Details')->orderable(false)->searchable(false),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'paid', 'gateway']);

        return DataTables::eloquent($this->repo->queryOrders($filters))
            ->addIndexColumn()
            ->editColumn('uuid', fn (Order $order) => view('tenant.pages.finance.billing._cols.order', ['order' => $order])->render())
            ->editColumn('customer', fn (Order $order) => view('tenant.pages.finance.billing._cols.customer', ['order' => $order])->render())
            ->editColumn('totals', fn (Order $order) => view('tenant.pages.finance.billing._cols.totals', ['order' => $order])->render())
            ->editColumn('payment', fn (Order $order) => view('tenant.pages.finance.billing._cols.payment', ['order' => $order])->render())
            ->editColumn('gateway', fn (Order $order) => view('tenant.pages.finance.billing._cols.gateway', ['order' => $order])->render())
            ->editColumn('placed_at', fn (Order $order) => $order->created_at?->format('M d, Y H:i'))
            ->editColumn('payment_details', fn (Order $order) => view('tenant.pages.finance.billing._cols.payment-details', ['order' => $order])->render())
            ->addColumn('actions', fn (Order $order) => view('tenant.pages.finance.billing._cols.actions', ['order' => $order])->render())
            ->orderColumn('totals', 'grand_total $1')
            ->orderColumn('placed_at', 'created_at $1')
            ->rawColumns(['uuid', 'customer', 'totals', 'payment', 'gateway', 'payment_details', 'actions'])
            ->toJson();
    }

    public function show(int $orderId): View
    {
        $order = Order::query()->findOrFail($orderId);

        return view('tenant.pages.finance.billing.show', [
            'orderId' => $orderId,
            'order' => $this->repo->orderDetail($order),
            'shippingStatuses' => OrderShippingStatus::cases(),
        ]);
    }

    /**
     * Plan usage-vs-limit rows for the "Plan usage" section (limit null = unlimited).
     *
     * @return list<array{label:string, used:int, limit:int|null, percent:int|null}>
     */
    private function planUsage(): array
    {
        $limitService = app(PlanLimitService::class);
        $tenant = tenant();

        $features = [
            PlanLimitService::FEATURE_PRODUCTS => 'Products',
            PlanLimitService::FEATURE_CATEGORIES => 'Categories',
            PlanLimitService::FEATURE_BANNERS => 'Banners',
            PlanLimitService::FEATURE_LANGUAGES => 'Languages',
            PlanLimitService::FEATURE_ORDERS_PER_MONTH => 'Orders this month',
            PlanLimitService::FEATURE_AI_CALLS => 'AI calls',
            PlanLimitService::FEATURE_IMAGE_SEARCHES => 'Image searches',
        ];

        return collect($features)->map(function (string $label, string $feature) use ($limitService, $tenant): array {
            $usage = $limitService->usage($tenant, $feature);
            $limit = $usage['limit'];

            return [
                'label' => $label,
                'used' => (int) $usage['used'],
                'limit' => $limit === null ? null : (int) $limit,
                'percent' => $limit ? (int) min(100, round($usage['used'] / $limit * 100)) : null,
            ];
        })->values()->all();
    }
}
