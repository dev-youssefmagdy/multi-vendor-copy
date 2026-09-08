<?php

namespace App\Livewire\Admin\Affiliate;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Affiliate;
use App\Services\AffiliateService;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class AffiliatesListPage extends ListPage
{
    use InteractsWithAdminUi, WithPagination, WithFileUploads;

    public bool $showFormModal   = false;
    public bool $showPayoutModal = false;
    public ?int $affiliateId     = null;

    // Edit fields
    public string $name            = '';
    public string $email           = '';
    public string $commissionType  = 'percentage';
    public string $commissionValue = '10.00';
    public string $status          = 'pending';

    // Payout fields
    public ?int   $payoutAffiliateId = null;
    public string $payoutAffiliateName = '';
    public string $payoutAffiliateBalance = '0.00';
    public string $payoutAmount    = '';
    public string $payoutReference = '';
    public string $payoutNotes     = '';
    public $payoutAttachment       = null;

    protected function pageView(): string
    {
        return 'livewire.admin.affiliate.affiliates-list-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title'       => 'Affiliate Accounts',
            'badge'       => 'Affiliates',
            'description' => 'Manage affiliate partners, commission rates, balances, and payouts.',
            'actionLabel' => null,
            'tableTitle'  => 'Affiliates',
            'headers'     => ['Affiliate', 'Commission', 'Balance', 'Earned', 'Referrals', 'Conversions', 'Status', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $this->authorizePermission('affiliates.manage');

        $records = Affiliate::query()
            ->withCount(['referrals', 'conversions'])
            ->orderByDesc('id')
            ->paginate(20);

        $stats = [
            'total'   => Affiliate::query()->count(),
            'active'  => Affiliate::query()->where('status', 'active')->count(),
            'pending' => Affiliate::query()->where('status', 'pending')->count(),
        ];

        $rows = collect($records->items())->map(fn (Affiliate $a) => [
            '<div class="entity-title">' . e($a->name) . '</div><div class="entity-subtitle">' . e($a->email) . '</div>',
            '<span class="badge badge-cyan">' . e($a->commission_type === 'percentage' ? $a->commission_value . '%' : '$' . number_format((float) $a->commission_value, 2)) . '</span>',
            '<strong>$' . number_format((float) $a->balance, 2) . '</strong>',
            '$' . number_format((float) $a->total_earned, 2),
            number_format($a->referrals_count),
            number_format($a->conversions_count),
            '<span class="badge ' . match ($a->status) { 'active' => 'badge-green', 'pending' => 'badge-amber', default => 'badge-secondary' } . '">' . ucfirst($a->status) . '</span>',
            '<div class="flex gap-2">
                <button type="button" class="btn btn-secondary btn-sm" wire:click="editAffiliate(' . $a->id . ')">Edit</button>
                ' . ($a->balance > 0 ? '<button type="button" class="btn btn-primary btn-sm" wire:click="openPayoutModal(' . $a->id . ')">Pay Out</button>' : '') . '
            </div>',
        ])->all();

        return array_merge(parent::pageData(), [
            'records'    => $records,
            'rows'       => $rows,
            'statistics' => [
                ['label' => 'Total Affiliates', 'value' => number_format($stats['total']), 'dot' => 'dot-cyan', 'caption' => 'Registered affiliates'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'dot' => 'dot-green', 'caption' => 'Approved and active'],
                ['label' => 'Pending Approval', 'value' => number_format($stats['pending']), 'dot' => 'dot-amber', 'caption' => 'Awaiting review'],
            ],
            'modalModel'        => 'showFormModal',
            'modalTitle'        => 'Edit Affiliate',
            'modalCloseAction'  => 'closeModal',
            'modalSubmitAction' => 'saveAffiliate',
            'modalSubmitLabel'  => 'Save Changes',
            'modalFieldGroups'  => [
                [
                    'gridClass' => 'form-grid-2',
                    'fields'    => [
                        ['label' => 'Name', 'model' => 'name', 'type' => 'text'],
                        ['label' => 'Email', 'model' => 'email', 'type' => 'email'],
                        ['label' => 'Commission Type', 'model' => 'commissionType', 'type' => 'select', 'options' => ['percentage' => 'Percentage (%)', 'fixed' => 'Fixed Amount ($)']],
                        ['label' => 'Commission Value', 'model' => 'commissionValue', 'type' => 'number'],
                        ['label' => 'Status', 'model' => 'status', 'type' => 'select', 'options' => ['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended']],
                    ],
                ],
            ],
        ]);
    }

    public function editAffiliate(int $id): void
    {
        $a = Affiliate::query()->findOrFail($id);
        $this->affiliateId     = $a->id;
        $this->name            = $a->name;
        $this->email           = $a->email;
        $this->commissionType  = $a->commission_type;
        $this->commissionValue = (string) $a->commission_value;
        $this->status          = $a->status;
        $this->showFormModal   = true;
    }

    public function saveAffiliate(): void
    {
        $this->authorizePermission('affiliates.manage');
        $this->validate([
            'name'            => 'required|string|max:100',
            'commissionType'  => 'required|in:percentage,fixed',
            'commissionValue' => 'required|numeric|min:0',
            'status'          => 'required|in:pending,active,suspended',
        ]);

        $a         = Affiliate::query()->findOrFail($this->affiliateId);
        $wasActive = $a->status === 'active';

        $a->update([
            'name'             => $this->name,
            'commission_type'  => $this->commissionType,
            'commission_value' => $this->commissionValue,
            'status'           => $this->status,
            'approved_at'      => ($this->status === 'active' && ! $wasActive) ? now() : $a->approved_at,
        ]);

        $this->closeModal();
        $this->toast('Affiliate updated.');
    }

    public function openPayoutModal(int $id): void
    {
        $a = Affiliate::query()->findOrFail($id);

        $this->payoutAffiliateId      = $a->id;
        $this->payoutAffiliateName    = $a->name;
        $this->payoutAffiliateBalance = (string) $a->balance;
        $this->payoutAmount           = '';
        $this->payoutReference        = '';
        $this->payoutNotes            = '';
        $this->payoutAttachment       = null;
        $this->showPayoutModal        = true;
    }

    public function issuePayout(AffiliateService $service): void
    {
        $this->authorizePermission('affiliates.manage');
        $this->validate([
            'payoutAmount'     => 'required|numeric|min:0.01',
            'payoutReference'  => 'nullable|string|max:255',
            'payoutNotes'      => 'nullable|string|max:2000',
            'payoutAttachment' => 'nullable|file|max:10240',
        ]);

        $affiliate = Affiliate::query()->findOrFail($this->payoutAffiliateId);

        if ((float) $this->payoutAmount > (float) $affiliate->balance) {
            $this->addError('payoutAmount', 'Amount exceeds available balance ($' . number_format((float) $affiliate->balance, 2) . ').');
            return;
        }

        $attachmentPath = $this->payoutAttachment
            ? Storage::disk('public')->url($this->payoutAttachment->store('affiliate-payouts', 'public'))
            : null;

        $service->issuePayout($affiliate, [
            'amount'          => $this->payoutAmount,
            'reference'       => $this->payoutReference,
            'notes'           => $this->payoutNotes,
            'attachment_path' => $attachmentPath,
        ]);

        $this->showPayoutModal = false;
        $this->toast('Payout of $' . number_format((float) $this->payoutAmount, 2) . ' recorded.');
    }

    public function closeModal(): void
    {
        $this->showFormModal   = false;
        $this->showPayoutModal = false;
        $this->affiliateId     = null;
        $this->resetErrorBag();
    }

    public function clearFilters(): void {}
}
