<?php

namespace App\Livewire\Tenant\Finance;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\PlanLimitService;
use Livewire\WithPagination;

class BillingPage extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $search = '';
    public string $paidFilter = '';
    public string $gatewayFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Billing',
            'badge' => 'Finance',
            'description' => 'Review order billing records, payment states, gateway routing, and nested gateway payloads for the current tenant.',
            'tableTitle' => 'Order Billing Ledger',
            'headers' => ['Order', 'Customer', 'Totals', 'Payment', 'Gateway', 'Placed At', 'Payment Details', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateOrders([
            'search' => $this->search,
            'paid' => $this->paidFilter,
            'gateway' => $this->gatewayFilter,
        ]);
        $stats = $repository->billingStats();

        return array_merge(parent::pageData(), [
            'actionLabel' => null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Order UUID, customer, or gateway'],
                ['label' => 'Payment State', 'model' => 'paidFilter', 'type' => 'select', 'options' => ['' => 'All', 'paid' => 'Paid', 'unpaid' => 'Unpaid']],
                ['label' => 'Gateway', 'model' => 'gatewayFilter', 'type' => 'select', 'options' => ['' => 'All'] + $repository->paymentMethodOptions()],
            ],
            'statisticsGridClass' => 'g-stats4',
            'statistics' => $this->presentMetricCards([
                ['label' => 'Orders', 'value' => $stats['orders'], 'format' => 'number', 'caption' => 'Order billing rows stored for this tenant.', 'dot' => 'dot-cyan'],
                ['label' => 'Collected', 'value' => $stats['collected'], 'format' => 'currency', 'caption' => 'Paid order value with confirmed collection.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Outstanding', 'value' => $stats['outstanding'], 'format' => 'currency', 'caption' => 'Unpaid order value still awaiting settlement.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Gateways', 'value' => $stats['gateways'], 'format' => 'number', 'caption' => 'Distinct payment methods seen in tenant orders.', 'dot' => 'dot-violet'],
                ...$this->planUsageCards(),
            ]),
            'rows' => collect($records->items())->map(function (Order $order) {
                $gatewayName = str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString() ?? $order->paymentGateway?->name ?? 'Unknown';
                $transactionId = data_get($order->payment_details, 'transaction_id');

                return [
                    '<div class="entity-title">' . e($order->uuid) . '</div><div class="entity-subtitle">' . e($order->items_count) . ' items</div>',
                    '<div class="entity-title">' . e($order->customer?->full_name ?? 'Guest') . '</div><div class="entity-subtitle">' . e($order->customer?->email ?? 'No email') . '</div>',
                    '<div class="entity-title">$' . e(number_format($order->grand_total, 2)) . '</div><div class="entity-subtitle">Ship $' . e(number_format($order->resolved_shipping_charge, 2)) . ' · Profit $' . e(number_format(\App\Support\OrderProfitCalculator::effectiveTenantProfitForOrder($order), 2)) . '</div>',
                    '<span class="badge ' . ($order->paid ? 'badge-green' : 'badge-amber') . '">' . e($order->paid ? 'Paid' : 'Unpaid') . '</span><div class="entity-subtitle">' . e($transactionId ?: 'No transaction id') . '</div>',
                    '<div class="entity-title">' . e($gatewayName ?: '-') . '</div><div class="entity-subtitle">' . e($order->payment_method ?: 'No method') . '</div>',
                    e($order->created_at?->format('M d, Y H:i')),
                    view('components.json-collapse', [
                        'title' => 'Gateway payload',
                        'summary' => $transactionId ? ('Transaction ' . $transactionId) : 'Open nested gateway response',
                        'data' => $order->payment_details,
                    ])->render(),
                    '<a href="' . route('tenant.finance.billing.detail', $order->id) . '" class="btn btn-secondary btn-sm">Inspect</a>',
                ];
            })->all(),
            'tableDescription' => $records->total() . ' billing records matched the current payment filters.',
        ]);
    }

    /**
     * Plan usage-vs-limit cards ("Products: 45 / 100", "Languages: Unlimited", ...).
     */
    protected function planUsageCards(): array
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

        return $this->presentMetricCards(collect($features)->map(function (array $meta, string $feature) use ($limitService, $tenant) {
            $usage = $limitService->usage($tenant, $feature);
            $value = $usage['limit'] === null
                ? number_format($usage['used']) . ' / Unlimited'
                : number_format($usage['used']) . ' / ' . number_format($usage['limit']);

            return [
                'label' => $meta['label'],
                'value' => $value,
                'caption' => 'Plan usage for ' . strtolower($meta['label']) . '.',
                'dot' => $meta['dot'],
            ];
        })->values()->all());
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaidFilter(): void
    {
        $this->resetPage();
    }

    public function updatedGatewayFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'paidFilter', 'gatewayFilter']);
        $this->resetPage();
    }
}
