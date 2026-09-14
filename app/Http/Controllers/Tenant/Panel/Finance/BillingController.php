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
                ['label' => 'Orders', 'value' => $stats['orders'], 'format' => 'number', 'caption' => 'Order billing rows stored for this tenant.', 'dot' => 'dot-cyan'],
                ['label' => 'Collected', 'value' => $stats['collected'], 'format' => 'currency', 'caption' => 'Paid order value with confirmed collection.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Outstanding', 'value' => $stats['outstanding'], 'format' => 'currency', 'caption' => 'Unpaid order value still awaiting settlement.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Gateways', 'value' => $stats['gateways'], 'format' => 'number', 'caption' => 'Distinct payment methods seen in tenant orders.', 'dot' => 'dot-violet'],
                ...$this->planUsageCards(),
            ]),
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
     * Plan usage-vs-limit cards ("Products: 45 / 100", "Languages: Unlimited", ...).
     * Ported verbatim from App\Livewire\Tenant\Finance\BillingPage::planUsageCards().
     */
    private function planUsageCards(): array
    {
        $limitService = app(PlanLimitService::class);
        $tenant = tenant();

        $features = [
            PlanLimitService::FEATURE_PRODUCTS => ['label' => 'Products', 'dot' => 'dot-cyan'],
            PlanLimitService::FEATURE_CATEGORIES => ['label' => 'Categories', 'dot' => 'dot-green'],
            PlanLimitService::FEATURE_BANNERS => ['label' => 'Banners', 'dot' => 'dot-amber'],
            PlanLimitService::FEATURE_LANGUAGES => ['label' => 'Languages', 'dot' => 'dot-violet'],
            PlanLimitService::FEATURE_ORDERS_PER_MONTH => ['label' => 'Orders this month', 'dot' => 'dot-cyan'],
            PlanLimitService::FEATURE_AI_CALLS => ['label' => 'AI calls', 'dot' => 'dot-violet'],
            PlanLimitService::FEATURE_IMAGE_SEARCHES => ['label' => 'Image searches', 'dot' => 'dot-amber'],
        ];

        return Metric::cards(collect($features)->map(function (array $meta, string $feature) use ($limitService, $tenant) {
            $usage = $limitService->usage($tenant, $feature);
            $value = $usage['limit'] === null
                ? number_format($usage['used']).' / Unlimited'
                : number_format($usage['used']).' / '.number_format($usage['limit']);

            return [
                'label' => $meta['label'],
                'value' => $value,
                'caption' => 'Plan usage for '.strtolower($meta['label']).'.',
                'dot' => $meta['dot'],
            ];
        })->values()->all());
    }
}
