<?php

namespace App\Livewire\Admin\Affiliate;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Affiliate;
use App\Models\AffiliateCoupon;
use Livewire\WithPagination;

class AffiliateCouponsPage extends ListPage
{
    use InteractsWithAdminUi, WithPagination;

    public bool    $showFormModal      = false;
    public ?int    $couponId           = null;
    public ?int    $affiliateId        = null;
    public string  $code               = '';
    public string  $discountType       = 'percentage';
    public string  $discountValue      = '0';
    public string  $commissionValue    = '';
    public ?string $startDate          = null;
    public ?string $endDate            = null;
    public string  $minimumSpend       = '0';
    public bool    $active             = true;

    protected function pageMeta(): array
    {
        return [
            'title'       => 'Affiliate Promo Codes',
            'badge'       => 'Affiliates',
            'description' => 'Manage promo codes assigned to affiliates. These are separate from storefront and registration coupons.',
            'actionLabel' => 'Add Promo Code',
            'tableTitle'  => 'Promo Codes',
            'headers'     => ['Code', 'Affiliate', 'Discount', 'Commission', 'Valid Until', 'Status', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $this->authorizePermission('affiliates.manage');

        $records = AffiliateCoupon::query()
            ->with('affiliate')
            ->orderByDesc('id')
            ->paginate(20);

        $rows = collect($records->items())->map(fn (AffiliateCoupon $c) => [
            '<strong style="letter-spacing:1px;">' . e($c->code) . '</strong>',
            e($c->affiliate?->name ?? '—'),
            e($c->discountLabel()),
            $c->commission_value !== null
                ? '<span class="badge badge-violet">' . e($c->commission_value) . '% override</span>'
                : '<span class="badge badge-secondary">Default rate</span>',
            e($c->end_date ? $c->end_date->format('M d, Y') : 'No expiry'),
            '<span class="badge ' . ($c->active ? 'badge-green' : 'badge-secondary') . '">' . ($c->active ? 'Active' : 'Inactive') . '</span>',
            '<div class="flex gap-2">
                <button type="button" class="btn btn-secondary btn-sm" wire:click="editCoupon(' . $c->id . ')">Edit</button>
                <button type="button" class="btn btn-secondary btn-sm" wire:click="deleteCoupon(' . $c->id . ')" wire:confirm="Delete this promo code?">Delete</button>
            </div>',
        ])->all();

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'records'      => $records,
            'rows'         => $rows,
            'modalModel'      => 'showFormModal',
            'modalTitle'      => $this->couponId ? 'Edit Promo Code' : 'New Promo Code',
            'modalCloseAction'=> 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel'  => $this->couponId ? 'Save Changes' : 'Create Code',
            'modalFieldGroups'  => [
                [
                    'gridClass' => 'form-grid-2',
                    'fields'    => [
                        ['label' => 'Code',           'model' => 'code',          'type' => 'text', 'placeholder' => 'e.g. JOHN20'],
                        ['label' => 'Affiliate',       'model' => 'affiliateId',   'type' => 'select', 'options' => ['' => 'Select an affiliate...'] + Affiliate::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id')->all()],
                        ['label' => 'Discount Type',   'model' => 'discountType',  'type' => 'select', 'options' => ['percentage' => 'Percentage (%)', 'fixed' => 'Fixed ($)']],
                        ['label' => 'Discount Value',  'model' => 'discountValue', 'type' => 'number'],
                        ['label' => 'Commission Override (%)', 'model' => 'commissionValue', 'type' => 'number', 'hint' => 'Leave blank to use the affiliate\'s default commission rate.'],
                        ['label' => 'Minimum Spend',   'model' => 'minimumSpend',  'type' => 'number'],
                        ['label' => 'Start Date',      'model' => 'startDate',     'type' => 'datetime-local'],
                        ['label' => 'End Date',        'model' => 'endDate',       'type' => 'datetime-local'],
                        ['label' => 'Status',          'model' => 'active',        'type' => 'checkbox', 'toggleLabel' => 'Promo code is active', 'live' => true],
                    ],
                ],
            ],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->reset(['couponId', 'code', 'discountType', 'discountValue', 'commissionValue', 'startDate', 'endDate', 'minimumSpend', 'active', 'affiliateId']);
        $this->active       = true;
        $this->discountType = 'percentage';
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function editCoupon(int $id): void
    {
        $c = AffiliateCoupon::query()->findOrFail($id);
        $this->couponId        = $c->id;
        $this->affiliateId     = $c->affiliate_id;
        $this->code            = $c->code;
        $this->discountType    = $c->discount_type;
        $this->discountValue   = (string) $c->discount_value;
        $this->commissionValue = $c->commission_value !== null ? (string) $c->commission_value : '';
        $this->minimumSpend    = (string) $c->minimum_spend;
        $this->startDate       = $c->start_date?->format('Y-m-d\TH:i');
        $this->endDate         = $c->end_date?->format('Y-m-d\TH:i');
        $this->active          = $c->active;
        $this->showFormModal   = true;
    }

    public function save(): void
    {
        $this->authorizePermission('affiliates.manage');
        $this->validate([
            'affiliateId'     => 'required|integer|exists:affiliates,id',
            'code'            => 'required|string|max:64|alpha_dash|' . ($this->couponId ? 'unique:affiliate_coupons,code,' . $this->couponId : 'unique:affiliate_coupons,code'),
            'discountType'    => 'required|in:percentage,fixed',
            'discountValue'   => 'required|numeric|min:0',
            'commissionValue' => 'nullable|numeric|min:0|max:100',
            'minimumSpend'    => 'nullable|numeric|min:0',
            'startDate'       => 'nullable|date',
            'endDate'         => 'nullable|date|after_or_equal:startDate',
        ]);

        $data = [
            'affiliate_id'     => $this->affiliateId,
            'code'             => strtoupper(trim($this->code)),
            'discount_type'    => $this->discountType,
            'discount_value'   => (float) $this->discountValue,
            'commission_value' => filled($this->commissionValue) ? (float) $this->commissionValue : null,
            'minimum_spend'    => (float) ($this->minimumSpend ?: 0),
            'start_date'       => filled($this->startDate) ? $this->startDate : null,
            'end_date'         => filled($this->endDate) ? $this->endDate : null,
            'active'           => $this->active,
        ];

        if ($this->couponId) {
            AffiliateCoupon::query()->findOrFail($this->couponId)->update($data);
            $this->toast('Promo code updated.');
        } else {
            AffiliateCoupon::query()->create($data);
            $this->toast('Promo code created.');
        }

        $this->closeModal();
    }

    public function deleteCoupon(int $id): void
    {
        $this->authorizePermission('affiliates.manage');
        AffiliateCoupon::query()->findOrFail($id)->delete();
        $this->toast('Promo code deleted.');
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->couponId      = null;
        $this->resetErrorBag();
    }

    public function clearFilters(): void {}
}
