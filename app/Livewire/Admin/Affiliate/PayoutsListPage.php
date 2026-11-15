<?php

namespace App\Livewire\Admin\Affiliate;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Livewire\Admin\Concerns\ManagesAffiliateSettings;
use App\Models\AffiliatePayout;
use App\Models\AffiliatePayoutRequest;
use App\Services\AffiliateService;
use App\Support\ImageOptimizer;
use App\Support\VendorProfile;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Central admin: affiliate withdrawal requests (submitted from the affiliate portal or the vendor
 * panel's Partner Program tab, stored in central `affiliate_payout_requests`) plus issued payouts.
 * Admin pays a request (records an AffiliatePayout, receipt optional) or rejects it.
 * Vendor identity and bank details come from the vendor's Compliance Center via VendorProfile.
 */
class PayoutsListPage extends ListPage
{
    use InteractsWithAdminUi, ManagesAffiliateSettings, WithFileUploads, WithPagination;

    /** pending | fulfilled | rejected | all | payouts (issued payout history) */
    public string $statusFilter = 'pending';

    public bool $showPayModal    = false;
    public bool $showRejectModal = false;
    public ?int $requestId       = null;

    public string $requestAffiliateName = '';
    public string $requestBalance       = '0.00';
    public string $requestNote          = '';
    public string $payAmount            = '';
    public string $payReference         = '';
    public string $payNotes             = '';
    public $payReceipt                  = null;
    public string $rejectNote           = '';

    /** Payee profile shown in the pay + details modals (see VendorProfile). */
    public array $payeeProfile          = [];
    public bool $showDetailsModal       = false;
    public array $detailsStats          = [];

    protected bool $exportable = true;

    protected function pageView(): string
    {
        return 'livewire.admin.affiliate.payouts-list-page';
    }

    private function showingPayouts(): bool
    {
        return $this->statusFilter === 'payouts';
    }

    protected function pageMeta(): array
    {
        return [
            'title'              => 'Affiliate Payouts',
            'badge'              => 'Affiliates',
            'description'        => 'Withdrawal requests from affiliates (including vendors in the partner program) and payouts issued.',
            'actionLabel'        => null,
            'tableTitle'         => $this->showingPayouts() ? 'Issued payouts' : 'Withdrawal requests',
            'tableDescription'   => $this->showingPayouts()
                ? 'Payments recorded for affiliates, with receipts.'
                : 'Pay a request (upload the transfer receipt) or reject it.',
            'filtersDescription' => 'Filter withdrawal requests by status, or switch to the issued payouts history.',
            'headers'            => $this->showingPayouts()
                ? ['Affiliate', 'Amount', 'Method', 'Reference', 'Receipt', 'Date']
                : ['Affiliate', 'Amount', 'Balance', 'Note', 'Status', 'Requested', 'Payout', 'Actions'],
            'emptyTitle'         => $this->showingPayouts() ? 'No payouts yet' : 'No withdrawal requests',
            'emptyCopy'          => $this->showingPayouts() ? 'Issued payouts will appear here.' : 'No withdrawal requests match this status.',
        ];
    }

    protected function pageData(): array
    {
        $this->authorizePermission('affiliates.manage');

        [$records, $rows] = $this->showingPayouts() ? $this->payoutRows() : $this->requestRows();

        $pending = AffiliatePayoutRequest::query()->where('status', 'pending');

        return array_merge(parent::pageData(), [
            'records'      => $records,
            'rows'         => $rows,
            'statistics'   => [
                ['label' => 'Pending Requests', 'value' => number_format((clone $pending)->count()), 'dot' => 'dot-amber', 'caption' => '$' . number_format((float) (clone $pending)->sum('amount'), 2) . ' awaiting payment'],
                ['label' => 'Total Paid Out', 'value' => '$' . number_format((float) AffiliatePayout::query()->sum('amount'), 2), 'dot' => 'dot-cyan', 'caption' => 'All-time payouts issued'],
                ['label' => 'Rejected Requests', 'value' => number_format(AffiliatePayoutRequest::query()->where('status', 'rejected')->count()), 'dot' => 'dot-red', 'caption' => 'Declined by admin'],
            ],
            'filterFields' => [
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => [
                    'pending'   => 'Pending requests',
                    'fulfilled' => 'Paid requests',
                    'rejected'  => 'Rejected requests',
                    'all'       => 'All requests',
                    'payouts'   => 'Issued payouts (history)',
                ]],
            ],
        ]);
    }

    private function requestRows(): array
    {
        $records = AffiliatePayoutRequest::query()
            ->with(['affiliate', 'payout'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(25);

        $rows = collect($records->items())->map(function (AffiliatePayoutRequest $r) {
            $a       = $r->affiliate;
            $payout  = $r->payout;
            $status  = match ($r->status) {
                'pending'   => '<span class="badge badge-amber">Pending</span>',
                'fulfilled' => '<span class="badge badge-green">Paid</span>',
                'rejected'  => '<span class="badge badge-red">Rejected</span>',
                default     => '<span class="badge badge-secondary">' . e(ucfirst($r->status)) . '</span>',
            };

            return [
                $this->affiliateCell($a, $r->id),
                '<strong>$' . number_format((float) $r->amount, 2) . '</strong>',
                '$' . number_format((float) ($a?->balance ?? 0), 2),
                e($r->note ?: '—') . ($r->admin_note ? '<div class="entity-subtitle">Admin: ' . e($r->admin_note) . '</div>' : ''),
                $status,
                e($r->created_at?->format('M d, Y H:i') ?? '—'),
                $payout
                    ? e($payout->reference ?: '—') . ($payout->attachment_path ? ' · <a href="' . e($payout->attachment_path) . '" target="_blank" rel="noopener">Receipt</a>' : '')
                    : ($r->processed_at ? e($r->processed_at->format('M d, Y')) : '—'),
                $r->status === 'pending'
                    ? '<div class="flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" wire:click="openPayModal(' . $r->id . ')">Mark Paid</button>
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="openRejectModal(' . $r->id . ')">Reject</button>
                    </div>'
                    : '—',
            ];
        })->all();

        return [$records, $rows];
    }

    private function payoutRows(): array
    {
        $records = AffiliatePayout::query()->with('affiliate')->latest('paid_at')->latest('id')->paginate(25);

        $rows = collect($records->items())->map(fn (AffiliatePayout $p) => [
            $this->affiliateCell($p->affiliate, null),
            '<strong>$' . number_format((float) $p->amount, 2) . '</strong>',
            e(ucfirst(str_replace('_', ' ', (string) $p->method))),
            e($p->reference ?? '—'),
            $p->attachment_path ? '<a href="' . e($p->attachment_path) . '" target="_blank" rel="noopener">View</a>' : '—',
            e($p->paid_at?->format('M d, Y') ?? '—'),
        ])->all();

        return [$records, $rows];
    }

    /**
     * Readable affiliate cell: person + store name (vendors), contact email, bank-details state,
     * and a Details button. Vendor affiliates carry a generated alias email (owner+tenant-id@…),
     * so the store owner's real email is shown instead.
     */
    private function affiliateCell(?\App\Models\Affiliate $a, ?int $requestId): string
    {
        if (!$a) {
            return '—';
        }

        $p = VendorProfile::forAffiliate($a);
        $name = VendorProfile::displayName($p, (string) $a->name);
        $sub = $p['is_vendor']
            ? __('Store') . ': ' . ($p['store_name'] ?: '—') . ($p['email'] ? ' · ' . $p['email'] : '')
            : (string) $a->email;
        $bank = VendorProfile::hasBankDetails($p)
            ? '<span class="badge badge-green">' . e(__('Bank details')) . '</span>'
            : '<span class="badge badge-red">' . e(__('No bank details')) . '</span>';
        $type = '<span class="badge ' . ($p['is_vendor'] ? 'badge-cyan' : 'badge-secondary') . '">' . e($p['is_vendor'] ? __('Vendor') : __('External')) . '</span>';

        return '<div class="entity-title">' . e($name) . '</div>'
            . '<div class="entity-subtitle">' . e($sub) . '</div>'
            . '<div class="flex gap-2" style="margin-top:6px;flex-wrap:wrap;align-items:center">' . $type . $bank
            . '<button type="button" class="btn btn-secondary btn-sm" wire:click="openDetailsModal(' . (int) $a->id . ')">' . e(__('Details')) . '</button></div>';
    }

    public function openDetailsModal(int $affiliateId): void
    {
        $this->authorizePermission('affiliates.manage');
        $a = \App\Models\Affiliate::query()->findOrFail($affiliateId);

        $this->payeeProfile = VendorProfile::forAffiliate($a);
        $this->requestAffiliateName = VendorProfile::displayName($this->payeeProfile, (string) $a->name);
        $this->detailsStats = [
            'code'          => (string) $a->code,
            'status'        => ucfirst((string) $a->status),
            'balance'       => (float) $a->balance,
            'total_earned'  => (float) $a->total_earned,
            'total_paid'    => (float) $a->total_paid,
            'referrals'     => $a->referrals()->count(),
            'conversions'   => $a->conversions()->count(),
            'pending'       => (float) $a->payoutRequests()->where('status', 'pending')->sum('amount'),
            'joined'        => $a->created_at?->format('M d, Y') ?? '—',
        ];
        $this->showDetailsModal = true;
    }

    // ── CSV export (current filter, all pages) ──────────────────────────
    protected function exportFileName(): string
    {
        return 'affiliate-' . ($this->showingPayouts() ? 'payouts' : 'withdrawal-requests-' . $this->statusFilter) . '-' . now()->format('Y-m-d') . '.csv';
    }

    protected function exportHeaders(): array
    {
        $person = ['Full name', 'Type', 'Store name', 'Store URL', 'Email', 'Phone', 'Country', 'City',
            'Bank name', 'Account holder', 'Account number', 'IBAN', 'Bank currency', 'PayPal'];

        return $this->showingPayouts()
            ? array_merge(['Payout ID'], $person, ['Amount (USD)', 'Method', 'Reference', 'Notes', 'Receipt', 'Paid at'])
            : array_merge(['Request ID'], $person, ['Amount (USD)', 'Current balance (USD)', 'Affiliate note', 'Status', 'Requested at', 'Payout reference', 'Receipt', 'Admin note']);
    }

    protected function exportRows(): array
    {
        $this->authorizePermission('affiliates.manage');

        $person = function (?\App\Models\Affiliate $a): array {
            $p = VendorProfile::forAffiliate($a);

            return [
                VendorProfile::displayName($p, (string) ($a?->name ?? '')),
                $p['is_vendor'] ? 'Vendor' : 'External',
                $p['store_name'], $p['store_url'], $p['email'] ?: (string) ($a?->email ?? ''), $p['phone'], $p['country'], $p['city'],
                $p['bank_name'], $p['bank_holder_name'], $p['bank_account_number'], $p['bank_iban'], $p['bank_currency'], $p['paypal_email'],
            ];
        };

        if ($this->showingPayouts()) {
            return AffiliatePayout::query()->with('affiliate')->latest('paid_at')->latest('id')->get()
                ->map(fn (AffiliatePayout $p) => array_merge([$p->id], $person($p->affiliate), [
                    number_format((float) $p->amount, 2, '.', ''), (string) $p->method, (string) $p->reference, (string) $p->notes,
                    (string) $p->attachment_path, $p->paid_at?->format('Y-m-d H:i') ?? '',
                ]))->all();
        }

        return AffiliatePayoutRequest::query()->with(['affiliate', 'payout'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('id')->get()
            ->map(fn (AffiliatePayoutRequest $r) => array_merge([$r->id], $person($r->affiliate), [
                number_format((float) $r->amount, 2, '.', ''),
                number_format((float) ($r->affiliate?->balance ?? 0), 2, '.', ''),
                (string) $r->note, ucfirst((string) $r->status), $r->created_at?->format('Y-m-d H:i') ?? '',
                (string) ($r->payout?->reference ?? ''), (string) ($r->payout?->attachment_path ?? ''), (string) $r->admin_note,
            ]))->all();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    private function pendingRequest(int $id): AffiliatePayoutRequest
    {
        return AffiliatePayoutRequest::query()->with('affiliate')->where('status', 'pending')->findOrFail($id);
    }

    public function openPayModal(int $id): void
    {
        $this->authorizePermission('affiliates.manage');
        $r = $this->pendingRequest($id);

        $this->resetErrorBag();
        $this->requestId            = $r->id;
        $this->payeeProfile         = VendorProfile::forAffiliate($r->affiliate);
        $this->requestAffiliateName = VendorProfile::displayName($this->payeeProfile, (string) ($r->affiliate?->name ?? '—'));
        $this->requestBalance       = (string) ($r->affiliate?->balance ?? '0');
        $this->requestNote          = (string) ($r->note ?? '');
        $this->payAmount            = (string) $r->amount;
        $this->payReference         = '';
        $this->payNotes             = '';
        $this->payReceipt           = null;
        $this->showPayModal         = true;
    }

    public function markPaid(AffiliateService $service): void
    {
        $this->authorizePermission('affiliates.manage');
        $this->validate([
            'payAmount'    => 'required|numeric|min:0.01',
            'payReference' => 'nullable|string|max:255',
            'payNotes'     => 'nullable|string|max:2000',
            'payReceipt'   => 'nullable|file|mimes:jpg,jpeg,png,webp,avif,gif,pdf|max:10240',
        ], [], ['payReceipt' => 'receipt', 'payAmount' => 'amount']);

        $request = $this->pendingRequest((int) $this->requestId);

        if ((float) $this->payAmount > (float) $request->amount) {
            $this->addError('payAmount', 'Amount cannot exceed the requested $' . number_format((float) $request->amount, 2) . '.');
            return;
        }

        // Receipt is optional: admins may pay by cash / methods without a document.
        $path = $this->payReceipt ? ImageOptimizer::store($this->payReceipt, 'affiliate-payouts') : null;

        try {
            $service->issuePayout($request->affiliate, [
                'request_id'      => $request->id,
                'amount'          => $this->payAmount,
                'method'          => 'manual',
                'reference'       => $this->payReference ?: null,
                'notes'           => $this->payNotes ?: null,
                'attachment_path' => $path ? Storage::disk('public')->url($path) : null,
            ]);
        } catch (\InvalidArgumentException $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $this->addError('payAmount', $e->getMessage());
            return;
        }

        $amount = (float) $this->payAmount;
        $this->closeModal();
        $this->toast('Payout of $' . number_format($amount, 2) . ' recorded and request marked paid.');
    }

    public function openRejectModal(int $id): void
    {
        $this->authorizePermission('affiliates.manage');
        $r = $this->pendingRequest($id);

        $this->resetErrorBag();
        $this->requestId            = $r->id;
        $this->requestAffiliateName = (string) ($r->affiliate?->name ?? '—');
        $this->payAmount            = (string) $r->amount;
        $this->rejectNote           = '';
        $this->showRejectModal      = true;
    }

    public function rejectRequest(AffiliateService $service): void
    {
        $this->authorizePermission('affiliates.manage');
        $this->validate(['rejectNote' => 'nullable|string|max:1000']);

        $service->rejectPayoutRequest($this->pendingRequest((int) $this->requestId), $this->rejectNote ?: null);

        $this->closeModal();
        $this->toast('Withdrawal request rejected.');
    }

    public function closeModal(): void
    {
        $this->showSettingsModal = false;
        $this->showPayModal      = false;
        $this->showRejectModal   = false;
        $this->showDetailsModal  = false;
        $this->requestId         = null;
        $this->payeeProfile      = [];
        $this->payReceipt        = null;
        $this->resetErrorBag();
    }

    public function clearFilters(): void
    {
        $this->statusFilter = 'pending';
        $this->resetPage();
    }
}
