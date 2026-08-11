<?php

namespace App\Livewire\Admin\Wallet;

use App\Enums\WalletTransactionDirection;
use App\Livewire\Admin\Base\ListPage;
use App\Models\TenantPayout;
use App\Models\VendorSettlement;
use App\Repositories\WalletTransactionRepository;
use Livewire\WithPagination;

class TransactionsList extends ListPage
{
    use WithPagination;

    public string $search = '';
    public string $directionFilter = '';
    public string $sourceFilter = '';   // '': wallet transactions | 'settlements' | 'payouts'
    public bool $showTransactionModal = false;
    public array $selectedTransaction = [];

    protected bool $exportable = true;

    protected function sourceOptions(): array
    {
        return [
            '' => 'Wallet Transactions',
            'settlements' => 'Settlements Received (Vendor → Central)',
            'payouts' => 'Payouts Sent (Central → Vendor)',
        ];
    }

    protected function pageMeta(): array
    {
        return match ($this->sourceFilter) {
            'settlements' => [
                'title' => 'Transactions',
                'badge' => 'Settlements',
                'description' => 'Payments received from vendors (Vendor → Central). Each row represents a vendor settling their vendor_cost with the platform.',
                'actionLabel' => '',
                'tableTitle' => 'Settlements Received',
                'headers' => ['Invoice', 'Tenant', 'Amount', 'Gateway', 'Settled At'],
            ],
            'payouts' => [
                'title' => 'Transactions',
                'badge' => 'Payouts',
                'description' => 'Payments sent to vendors (Central → Vendor). Each row represents the platform paying out a vendor’s profit share.',
                'actionLabel' => '',
                'tableTitle' => 'Payouts Sent',
                'headers' => ['Invoice', 'Tenant', 'Amount', 'Method', 'Reference', 'Paid At'],
            ],
            default => [
                'title' => 'Transactions',
                'badge' => 'Wallet Ledger',
                'description' => 'Inspect credit and debit movements for owner and tenant wallet accounting.',
                'actionLabel' => 'Export Ledger',
                'tableTitle' => 'Wallet Transactions',
                'headers' => ['Reference', 'Store', 'Type', 'Direction', 'Amount', 'Actions'],
            ],
        };
    }

    protected function pageData(): array
    {
        if ($this->sourceFilter === 'settlements') {
            return $this->settlementsPageData();
        }
        if ($this->sourceFilter === 'payouts') {
            return $this->payoutsPageData();
        }

        return $this->walletTransactionsPageData();
    }

    protected function walletTransactionsPageData(): array
    {
        $repository = app(WalletTransactionRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'direction' => $this->directionFilter,
        ]);
        $stats = $repository->stats();

        $sourceFilter = ['label' => 'Source', 'model' => 'sourceFilter', 'type' => 'select', 'options' => $this->sourceOptions()];

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Reference'],
                ['label' => 'Direction', 'model' => 'directionFilter', 'type' => 'select', 'options' => ['' => 'All directions', 'credit' => 'Credit', 'debit' => 'Debit']],
                $sourceFilter,
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Transactions', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Wallet ledger entries', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Credits', 'value' => $stats['credits'], 'format' => 'number', 'caption' => 'Incoming entries', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Debits', 'value' => $stats['debits'], 'format' => 'number', 'caption' => 'Outgoing entries', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Admin Profit', 'value' => $stats['admin_profit'], 'format' => 'currency', 'caption' => 'Profit attached to wallet transactions', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => collect($records->items())->map(fn($transaction) => [
                '<div class="entity-title">' . e($transaction->reference ?? 'No reference') . '</div><div class="entity-subtitle">' . e($transaction->created_at?->format('M d, Y H:i') ?? '-') . '</div>',
                '<div class="entity-title">' . e($transaction->store_name ?? 'Unknown store') . '</div><div class="entity-subtitle">' . e($transaction->wallet?->tenant?->email ?? 'No email') . '</div>',
                '<div class="entity-title">' . e($transaction->model_type) . '</div><div class="entity-subtitle">' . e($transaction->model_label) . '</div>',
                '<span class="badge ' . ($transaction->direction === WalletTransactionDirection::Credit ? 'badge-green' : 'badge-amber') . '">' . e($transaction->direction->label()) . '</span>',
                '<div class="entity-title">$' . number_format((float) $transaction->amount, 2) . '</div><div class="entity-subtitle">$' . number_format((float) $transaction->admin_profit, 2) . ' admin profit</div>',
                '<button type="button" class="link-btn" wire:click=\'viewTransaction(' . json_encode((string) $transaction->tenant_id) . ', ' . json_encode((string) $transaction->reference) . ')\'>View</button>',
            ])->all(),
            'tableDescription' => $records->total() . ' wallet transactions matched the current filters.',
            'modalModel' => 'showTransactionModal',
            'modalTitle' => $this->selectedTransaction['reference'] ?? 'Transaction Details',
            'modalMaxWidth' => '4xl',
            'modalCloseAction' => 'closeTransactionModal',
            'modalContentView' => 'livewire.admin.wallet.partials.transaction-details',
            'modalContentData' => ['transaction' => $this->selectedTransaction],
        ]);
    }

    protected function settlementsPageData(): array
    {
        $query = VendorSettlement::query()
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                    ->orWhere('tenant_name', 'like', '%' . $this->search . '%')
                    ->orWhere('tenant_email', 'like', '%' . $this->search . '%');
            }))
            ->latest('settled_at');

        $records = $query->paginate(10);
        $statsQ = VendorSettlement::query();

        $sourceFilter = ['label' => 'Source', 'model' => 'sourceFilter', 'type' => 'select', 'options' => $this->sourceOptions()];

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Invoice or tenant'],
                $sourceFilter,
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Settlements', 'value' => $statsQ->count(), 'format' => 'number', 'caption' => 'Vendor → Central payments received', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Total Received', 'value' => (float) $statsQ->sum('total'), 'format' => 'currency', 'caption' => 'Sum of all settlement totals', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Product Cost', 'value' => (float) $statsQ->sum('product_cost'), 'format' => 'currency', 'caption' => 'Sum of product_cost across settlements', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Gateway Fees', 'value' => (float) $statsQ->sum('gateway_fee'), 'format' => 'currency', 'caption' => 'Sum of gateway processing fees paid', 'dot' => 'dot-cyan'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => collect($records->items())->map(fn(VendorSettlement $s) => [
                '<div class="entity-title">' . e($s->invoice_number) . '</div><div class="entity-subtitle">' . e($s->settled_at?->format('M d, Y H:i') ?? '-') . '</div>',
                '<div class="entity-title">' . e($s->tenant_name ?? '') . '</div><div class="entity-subtitle">' . e($s->tenant_email ?? '') . '</div>',
                '<strong>$' . number_format((float) $s->total, 2) . '</strong>',
                e(ucwords(str_replace('_', ' ', (string) $s->gateway_code))),
                $s->settled_at?->format('M d, Y H:i') ?? '—',
            ])->all(),
            'tableDescription' => $records->total() . ' settlements matched.',
        ]);
    }

    protected function payoutsPageData(): array
    {
        $query = TenantPayout::query()
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                    ->orWhere('tenant_name', 'like', '%' . $this->search . '%')
                    ->orWhere('tenant_email', 'like', '%' . $this->search . '%');
            }))
            ->latest('paid_at');

        $records = $query->paginate(10);
        $statsQ = TenantPayout::query();

        $sourceFilter = ['label' => 'Source', 'model' => 'sourceFilter', 'type' => 'select', 'options' => $this->sourceOptions()];

        return array_merge(parent::pageData(), [
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Invoice or tenant'],
                $sourceFilter,
            ],
            'statistics' => $this->presentMetricCards([
                ['label' => 'Total Payouts', 'value' => $statsQ->count(), 'format' => 'number', 'caption' => 'Central → Vendor payments sent', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Total Paid Out', 'value' => (float) $statsQ->sum('amount'), 'format' => 'currency', 'caption' => 'Sum of all payout amounts', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Bank Payouts', 'value' => $statsQ->where('method', 'bank')->count(), 'format' => 'number', 'caption' => 'Payouts via bank transfer', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Gateway Payouts', 'value' => $statsQ->where('method', '!=', 'bank')->count(), 'format' => 'number', 'caption' => 'Payouts via payment gateway', 'dot' => 'dot-cyan'],
            ]),
            'statisticsGridClass' => 'g-stats4',
            'rows' => collect($records->items())->map(fn(TenantPayout $p) => [
                '<div class="entity-title">' . e($p->invoice_number) . '</div><div class="entity-subtitle">' . e($p->paid_at?->format('M d, Y H:i') ?? '-') . '</div>',
                '<div class="entity-title">' . e($p->tenant_name ?? '') . '</div><div class="entity-subtitle">' . e($p->tenant_email ?? '') . '</div>',
                '<strong>$' . number_format((float) $p->amount, 2) . '</strong>',
                e($p->method === 'bank' ? ('Bank · ' . ($p->bank_name ?? '—')) : ('Gateway · ' . ($p->gateway_name ?? '—'))),
                '<span style="font-family:monospace;font-size:11px;">' . e($p->transaction_reference ?? '—') . '</span>',
                $p->paid_at?->format('M d, Y H:i') ?? '—',
            ])->all(),
            'tableDescription' => $records->total() . ' payouts matched.',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDirectionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSourceFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'directionFilter', 'sourceFilter']);
        $this->resetPage();
    }

    protected function exportFileName(): string
    {
        return match ($this->sourceFilter) {
            'settlements' => 'settlements-' . now()->format('Y-m-d') . '.csv',
            'payouts' => 'payouts-' . now()->format('Y-m-d') . '.csv',
            default => 'wallet-transactions-' . now()->format('Y-m-d') . '.csv',
        };
    }

    protected function exportHeaders(): array
    {
        return match ($this->sourceFilter) {
            'settlements' => ['Invoice #', 'Tenant', 'Tenant Email', 'Total', 'Product Cost', 'Gateway Fee', 'Gateway', 'Settled At'],
            'payouts' => ['Invoice #', 'Tenant', 'Tenant Email', 'Amount', 'Method', 'Reference', 'Paid At'],
            default => ['Reference', 'Store', 'Store Email', 'Type', 'Description', 'Direction', 'Amount', 'Admin Profit', 'Date'],
        };
    }

    protected function exportRows(): array
    {
        if ($this->sourceFilter === 'settlements') {
            return \App\Models\VendorSettlement::query()
                ->when($this->search, fn($q) => $q->where(function ($q) {
                    $q->where('invoice_number', 'like', '%' . $this->search . '%')
                        ->orWhere('tenant_name', 'like', '%' . $this->search . '%')
                        ->orWhere('tenant_email', 'like', '%' . $this->search . '%');
                }))
                ->latest('settled_at')
                ->get()
                ->map(fn($s) => [
                    $s->invoice_number,
                    $s->tenant_name ?? '',
                    $s->tenant_email ?? '',
                    number_format((float) $s->total, 2),
                    number_format((float) $s->product_cost, 2),
                    number_format((float) $s->gateway_fee, 2),
                    $s->gateway_code ?? '',
                    $s->settled_at?->format('Y-m-d H:i') ?? '',
                ])->all();
        }

        if ($this->sourceFilter === 'payouts') {
            return \App\Models\TenantPayout::query()
                ->when($this->search, fn($q) => $q->where(function ($q) {
                    $q->where('invoice_number', 'like', '%' . $this->search . '%')
                        ->orWhere('tenant_name', 'like', '%' . $this->search . '%')
                        ->orWhere('tenant_email', 'like', '%' . $this->search . '%');
                }))
                ->latest('paid_at')
                ->get()
                ->map(fn($p) => [
                    $p->invoice_number,
                    $p->tenant_name ?? '',
                    $p->tenant_email ?? '',
                    number_format((float) $p->amount, 2),
                    $p->method === 'bank' ? 'Bank (' . ($p->bank_name ?? '-') . ')' : 'Gateway (' . ($p->gateway_name ?? '-') . ')',
                    $p->transaction_reference ?? '',
                    $p->paid_at?->format('Y-m-d H:i') ?? '',
                ])->all();
        }

        // Default: wallet transactions
        $repository = app(WalletTransactionRepository::class);
        return $repository->export([
            'search' => $this->search,
            'direction' => $this->directionFilter,
        ])->map(fn($t) => [
                $t->reference ?? '',
                $t->store_name ?? '',
                $t->wallet?->tenant?->email ?? '',
                $t->model_type ?? '',
                $t->description ?? $t->model_label ?? '',
                $t->direction->label(),
                number_format((float) $t->amount, 2),
                number_format((float) $t->admin_profit, 2),
                $t->created_at?->format('Y-m-d H:i') ?? '',
            ])->all();
    }

    public function viewTransaction(string $tenantId, string $reference): void
    {
        $transaction = app(WalletTransactionRepository::class)->find($tenantId, $reference);

        if (!$transaction) {
            return;
        }

        $this->selectedTransaction = [
            'reference' => $transaction->reference,
            'store_name' => $transaction->store_name,
            'tenant_email' => $transaction->tenant?->email,
            'direction' => $transaction->direction->label(),
            'amount' => (float) $transaction->amount,
            'admin_profit' => (float) $transaction->admin_profit,
            'description' => $transaction->description,
            'model_type' => $transaction->model_type,
            'model_label' => $transaction->model_label,
            'subscription' => $transaction->subscription,
            'created_at' => $transaction->created_at,
        ];
        $this->showTransactionModal = true;
    }

    public function closeTransactionModal(): void
    {
        $this->showTransactionModal = false;
        $this->selectedTransaction = [];
    }
}
