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
            'title' => __('Billing'),
            'badge' => __('Finance'),
            'description' => __('Review order billing records, payment states, gateway routing, and nested gateway payloads for the current tenant.'),
            'tableTitle' => __('Order Billing Ledger'),
            'headers' => [__('Order'), __('Customer'), __('Totals'), __('Payment'), __('Gateway'), __('Placed At'), __('Payment Details'), __('Actions')],
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
                ['label' => __('Search'), 'model' => 'search', 'placeholder' => __('Order UUID, customer, or gateway')],
                ['label' => __('Payment State'), 'model' => 'paidFilter', 'type' => 'select', 'options' => ['' => __('All'), 'paid' => __('Paid'), 'unpaid' => __('Unpaid')]],
                ['label' => __('Gateway'), 'model' => 'gatewayFilter', 'type' => 'select', 'options' => ['' => __('All')] + $repository->paymentMethodOptions()],
            ],
            'statisticsGridClass' => 'g-stats4',
            'statistics' => $this->presentMetricCards([
                ['label' => __('Orders'), 'value' => $stats['orders'], 'format' => 'number', 'caption' => __('Order billing rows stored for this tenant.'), 'dot' => 'dot-cyan'],
                ['label' => __('Collected'), 'value' => $stats['collected'], 'format' => 'currency', 'caption' => __('Paid order value with confirmed collection.'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Outstanding'), 'value' => $stats['outstanding'], 'format' => 'currency', 'caption' => __('Unpaid order value still awaiting settlement.'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => __('Gateways'), 'value' => $stats['gateways'], 'format' => 'number', 'caption' => __('Distinct payment methods seen in tenant orders.'), 'dot' => 'dot-violet'],
                ...$this->planUsageCards(),
            ]),
            'rows' => collect($records->items())->map(function (Order $order) {
                $gatewayName = __((string) str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()) ?? $order->paymentGateway?->name ?? __('Unknown');
                $transactionId = data_get($order->payment_details, 'transaction_id');

                return [
                    '<div class="entity-title">' . e($order->uuid) . '</div><div class="entity-subtitle">' . e(trans_choice(':count item|:count items', (int) $order->items_count, ['count' => $order->items_count])) . '</div>',
                    '<div class="entity-title">' . e($order->customer?->full_name ?? __('Guest')) . '</div><div class="entity-subtitle">' . e($order->customer?->email ?? __('No email')) . '</div>',
                    '<div class="entity-title">$' . e(number_format($order->grand_total, 2)) . '</div><div class="entity-subtitle">' . e(__('Shipping $:resolvedshippingcharge · Your profit $:profit', ['resolvedshippingcharge' => number_format($order->resolved_shipping_charge, 2), 'profit' => number_format(\App\Support\OrderProfitCalculator::effectiveTenantProfitForOrder($order), 2)])) . '</div>',
                    '<span class="badge ' . ($order->paid ? 'badge-green' : 'badge-amber') . '">' . e($order->paid ? __('Paid') : __('Unpaid')) . '</span><div class="entity-subtitle">' . e($transactionId ?: __('No transaction id')) . '</div>',
                    '<div class="entity-title">' . e($gatewayName ?: '-') . '</div><div class="entity-subtitle">' . e($order->payment_method ?: __('No method')) . '</div>',
                    e($order->created_at?->format('M d, Y H:i')),
                    view('components.json-collapse', [
                        'title' => __('Gateway payload'),
                        'summary' => $transactionId ? __('Transaction :id', ['id' => $transactionId]) : __('Open nested gateway response'),
                        'data' => $order->payment_details,
                    ])->render(),
                    '<a href="' . route('tenant.finance.billing.detail', $order->id) . '" class="btn btn-secondary btn-sm">' . e(__('Inspect')) . '</a>',
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
            PlanLimitService::FEATURE_PRODUCTS => ['label' => __('Products'), 'dot' => 'dot-cyan'],
            PlanLimitService::FEATURE_CATEGORIES => ['label' => __('Categories'), 'dot' => 'dot-green'],
            PlanLimitService::FEATURE_BANNERS => ['label' => __('Banners'), 'dot' => 'dot-amber'],
            PlanLimitService::FEATURE_LANGUAGES => ['label' => __('Languages'), 'dot' => 'dot-violet'],
            PlanLimitService::FEATURE_ORDERS_PER_MONTH => ['label' => __('Orders this month'), 'dot' => 'dot-cyan'],
            PlanLimitService::FEATURE_AI_CALLS => ['label' => __('AI calls'), 'dot' => 'dot-violet'],
            PlanLimitService::FEATURE_IMAGE_SEARCHES => ['label' => __('Image searches'), 'dot' => 'dot-amber'],
        ];

        return $this->presentMetricCards(collect($features)->map(function (array $meta, string $feature) use ($limitService, $tenant) {
            $usage = $limitService->usage($tenant, $feature);
            $value = $usage['limit'] === null
                ? number_format($usage['used']) . ' / Unlimited'
                : number_format($usage['used']) . ' / ' . number_format($usage['limit']);

            return [
                'label' => $meta['label'],
                'value' => $value,
                'caption' => __('Plan usage for :feature.', ['feature' => mb_strtolower($meta['label'])]),
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
