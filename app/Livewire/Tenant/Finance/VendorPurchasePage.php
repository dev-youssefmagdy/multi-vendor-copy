<?php

namespace App\Livewire\Tenant\Finance;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use Livewire\WithPagination;

/**
 * Vendor Purchases page – lets the vendor/tenant review what they owe the
 * central platform for fulfilled customer orders and navigate to the
 * dedicated settlement payment page.
 */
class VendorPurchasePage extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    protected bool $exportable = true;

    // ── Filters ──────────────────────────────────────────────────────────────
    public string $search = '';
    public string $settledFilter = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        if (session()->has('vendor_settlement_success')) {
            $this->toast(session()->pull('vendor_settlement_success'));
        }
        if (session()->has('vendor_settlement_error')) {
            $this->toast(session()->pull('vendor_settlement_error'), 'error');
        }
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Vendor Purchases',
            'badge' => 'Finance',
            // Hide the default "Add New" action button for this page.
            'actionLabel' => '',
            'description' => 'Review what you owe central for product costs and shipping on each order, then settle via a payment gateway.',
            'tableTitle' => 'Purchase Ledger',
            'headers' => ['Order', 'Customer', 'Paid Via', 'Product Cost', 'Shipping', 'Gateway Fee', 'Total Due', 'Settlement', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);

        $records = $repository->paginateVendorPurchases([
            'search' => $this->search,
            'settled' => $this->settledFilter,
        ]);
        $stats = $repository->vendorPurchaseStats();

        $rows = collect($records->items())->map(function (Order $order) {
            $productCost = number_format((float) $order->vendor_cost, 2);
            $shippingCost = number_format((float) $order->shipping_charge, 2);
            $totalDue = number_format($order->vendor_total_due, 2);
            $settled = $order->vendor_settled;

            $paymentMethod = e(ucwords(str_replace('_', ' ', $order->payment_method ?? 'cod')));
            $paidViaCell = '<div class="entity-title">' . $paymentMethod . '</div>';

            $feePreset = $order->vendor_gateway_id !== null;
            $gwFeeDisplay = $feePreset
                ? '$' . number_format((float) $order->vendor_gateway_fee, 2)
                . '<div class="entity-subtitle">Pre-calculated</div>'
                : '<span class="entity-subtitle">&mdash;</span><div class="entity-subtitle">Set on settle</div>';

            $settlementCell = $settled
                ? '<span class="badge badge-green">Settled</span>'
                . '<div class="entity-subtitle">' . e(optional($order->vendor_settled_at)->format('M d, Y')) . '</div>'
                . ($order->vendor_settlement_ref
                    ? '<div class="entity-subtitle" style="font-family:monospace;font-size:11px;">'
                    . e(str()->limit($order->vendor_settlement_ref, 24)) . '</div>'
                    : '')
                : '<span class="badge badge-amber">Pending</span>';

            $actionBtn = $settled
                ? ''
                : '<a href="' . route('tenant.finance.vendor-purchase-settle', $order->id) . '" class="btn btn-primary btn-sm">Pay Central</a>';

            return [
                '<div class="entity-title">' . e($order->uuid) . '</div>'
                . '<div class="entity-subtitle">' . e($order->items_count) . ' items · ' . e($order->created_at?->format('M d, Y')) . '</div>',
                '<div class="entity-title">' . e($order->customer?->full_name ?? 'Guest') . '</div>'
                . '<div class="entity-subtitle">' . e($order->customer?->email ?? '') . '</div>',
                $paidViaCell,
                '$' . $productCost,
                '$' . $shippingCost,
                $gwFeeDisplay,
                '<strong>$' . $totalDue . '</strong>',
                $settlementCell,
                $actionBtn,
            ];
        })->all();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Order UUID or customer'],
                [
                    'label' => 'Settlement',
                    'model' => 'settledFilter',
                    'type' => 'select',
                    'options' => [
                        '' => 'All',
                        'unsettled' => 'Unsettled',
                        'settled' => 'Settled',
                    ]
                ],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Orders', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Orders with a product cost owed to central.', 'dot' => 'dot-cyan'],
                ['label' => 'Unsettled', 'value' => $stats['unsettled_count'], 'format' => 'number', 'caption' => 'Orders not yet paid to central.', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Amount Owed', 'value' => $stats['total_owed'], 'format' => 'currency', 'caption' => 'Total product + shipping cost still outstanding.', 'dot' => 'dot-red'],
                ['label' => 'Total Settled', 'value' => $stats['total_settled'], 'format' => 'currency', 'caption' => 'Total paid to central including gateway fees.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'rows' => $rows,
            'tableDescription' => $records->total() . ' purchase records matched the current filters.',
        ]);
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSettledFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'settledFilter']);
        $this->resetPage();
    }

    protected function exportFileName(): string
    {
        return 'vendor-purchases-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        return ['Order UUID', 'Customer', 'Customer Email', 'Items', 'Payment Method', 'Product Cost', 'Shipping', 'Total Due', 'Settled', 'Settled At', 'Settlement Ref', 'Placed At'];
    }

    protected function exportRows(): array
    {
        $repository = app(TenantPanelRepository::class);
        return $repository->exportVendorPurchases(['search' => $this->search, 'settled' => $this->settledFilter])
            ->map(fn(Order $order) => [
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
            ])->all();
    }
}
