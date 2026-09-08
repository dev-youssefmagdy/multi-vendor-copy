<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\ListPage;
use App\Repositories\LanguagePurchaseRepository;
use App\Models\TenantLanguagePurchase;
use Livewire\WithPagination;

class LanguagePurchasesPage extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $gatewayFilter = '';
    public bool $showPurchaseModal = false;
    public array $selectedPurchase = [];

    protected function pageMeta(): array
    {
        return [
            'title' => 'Language Purchases',
            'badge' => 'Localization',
            'description' => 'List tenant purchases of premium language add-ons.',
            'tableTitle' => 'Language Purchases',
            'headers' => ['Tenant', 'Language', 'Amount', 'Gateway', 'Transaction', 'Purchased At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(LanguagePurchaseRepository::class);
        $records = $repository->paginate(['search' => $this->search, 'gateway' => $this->gatewayFilter]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Tenant, language, transaction, gateway'],
                ['label' => 'Gateway', 'model' => 'gatewayFilter', 'type' => 'select', 'options' => ['' => 'All'] + TenantLanguagePurchase::query()->distinct()->pluck('gateway_code', 'gateway_code')->toArray() ?? []],
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Purchases', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Total language purchases', 'dot' => 'dot-cyan'],
                ['label' => 'Revenue', 'value' => $stats['revenue'], 'format' => 'currency', 'caption' => 'Total revenue from language purchases', 'dot' => 'dot-green'],
            ]),
            'rows' => collect($records->items())->map(function ($purchase) {
                $tenantName = $purchase->tenant?->name ?? $purchase->tenant_id;
                $tenantEmail = $purchase->tenant?->email ?? '-';
                $language = $purchase->language?->name ?? 'Unknown';

                return [
                    '<div class="entity-title">' . e($tenantName) . '</div><div class="entity-subtitle">' . e($tenantEmail) . '</div>',
                    '<div class="entity-title">' . e($language) . '</div><div class="entity-subtitle">' . e(strtoupper($purchase->language?->code ?? '-')) . '</div>',
                    '<div class="entity-title">$' . number_format((float) $purchase->amount, 2) . '</div>',
                    e(strtoupper((string) $purchase->gateway_code ?: '-')),
                    e($purchase->transaction_uuid ?: '-'),
                    e(optional($purchase->created_at)->format('M d, Y H:i') ?: '-'),
                    '<button type="button" class="link-btn" wire:click="viewPurchase(' . $purchase->id . ')">View</button>',
                ];
            })->all(),
            'tableDescription' => $records->total() . ' language purchases matched the current filters.',
            'modalModel' => 'showPurchaseModal',
            'modalTitle' => $this->selectedPurchase['transaction_uuid'] ?? 'Purchase Details',
            'modalContentView' => 'livewire.admin.setting.partials.language-purchase-details',
            'modalContentData' => ['purchase' => $this->selectedPurchase],
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGatewayFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'gatewayFilter']);
        $this->resetPage();
    }

    public function viewPurchase(int $id): void
    {
        $purchase = app(LanguagePurchaseRepository::class)->find($id);

        if (!$purchase) {
            return;
        }

        $this->selectedPurchase = [
            'id' => $purchase->id,
            'tenant_id' => $purchase->tenant_id,
            'tenant_name' => $purchase->tenant?->name,
            'tenant_email' => $purchase->tenant?->email,
            'language' => $purchase->language?->name,
            'language_code' => $purchase->language?->code,
            'amount' => (float) $purchase->amount,
            'gateway' => $purchase->gateway_code,
            'transaction_uuid' => $purchase->transaction_uuid,
            'created_at' => $purchase->created_at,
        ];

        $this->showPurchaseModal = true;
    }

    public function closePurchaseModal(): void
    {
        $this->showPurchaseModal = false;
        $this->selectedPurchase = [];
    }
}
