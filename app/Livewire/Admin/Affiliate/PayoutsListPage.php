<?php

namespace App\Livewire\Admin\Affiliate;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Livewire\Admin\Concerns\ManagesAffiliateSettings;
use App\Models\AffiliatePayout;
use App\Models\AffiliatePayoutRequest;
use App\Services\AffiliateService;
use App\Support\ImageOptimizer;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Central admin: affiliate withdrawal requests (submitted from the affiliate portal or the vendor
 * panel's Partner Program tab, stored in central `affiliate_payout_requests`) plus issued payouts.
 * Admin pays a request (records an AffiliatePayout with a receipt) or rejects it.
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
                '<div class="entity-title">' . e($a?->name ?? '—') . '</div><div class="entity-subtitle">' . e(($a?->email ?? '') . ($a?->isTenant() ? ' · store: ' . $a->tenant_id : '')) . '</div>'
                    . ($a ? ' <span class="badge ' . ($a->isTenant() ? 'badge-cyan' : 'badge-secondary') . '">' . ($a->isTenant() ? 'Vendor' : 'External') . '</span>' : ''),
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
            e($p->affiliate?->name ?? '—'),
            '<strong>$' . number_format((float) $p->amount, 2) . '</strong>',
            e(ucfirst(str_replace('_', ' ', (string) $p->method))),
            e($p->reference ?? '—'),
            $p->attachment_path ? '<a href="' . e($p->attachment_path) . '" target="_blank" rel="noopener">View</a>' : '—',
            e($p->paid_at?->format('M d, Y') ?? '—'),
        ])->all();

        return [$records, $rows];
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
        $this->requestAffiliateName = (string) ($r->affiliate?->name ?? '—');
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
            'payReceipt'   => 'required|file|mimes:jpg,jpeg,png,webp,avif,gif,pdf|max:10240',
        ], [], ['payReceipt' => 'receipt', 'payAmount' => 'amount']);

        $request = $this->pendingRequest((int) $this->requestId);

        if ((float) $this->payAmount > (float) $request->amount) {
            $this->addError('payAmount', 'Amount cannot exceed the requested $' . number_format((float) $request->amount, 2) . '.');
            return;
        }

        $path = ImageOptimizer::store($this->payReceipt, 'affiliate-payouts');

        try {
            $service->issuePayout($request->affiliate, [
                'request_id'      => $request->id,
                'amount'          => $this->payAmount,
                'method'          => 'manual',
                'reference'       => $this->payReference ?: null,
                'notes'           => $this->payNotes ?: null,
                'attachment_path' => Storage::disk('public')->url($path),
            ]);
        } catch (\InvalidArgumentException $e) {
            Storage::disk('public')->delete($path);
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
        $this->requestId         = null;
        $this->payReceipt        = null;
        $this->resetErrorBag();
    }

    public function clearFilters(): void
    {
        $this->statusFilter = 'pending';
        $this->resetPage();
    }
}
