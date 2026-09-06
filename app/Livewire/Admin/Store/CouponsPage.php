<?php

namespace App\Livewire\Admin\Store;

use App\Enums\Tenant\CouponType;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\CentralCoupon;
use App\Services\Admin\CentralCouponService;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Carbon\Carbon;
use Livewire\WithPagination;

class CouponsPage extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public bool $showFormModal = false;
    public ?int $couponId = null;
    public string $code = '';
    public string $name = '';
    public string $type = 'fixed';
    public string $value = '0.00';
    public string $minimumSpend = '0.00';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public bool $active = true;
    public array $assignedCountryIds = [];
    public bool $allCountries = true;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Central Coupons',
            'badge' => 'Store',
            'description' => 'Create platform-wide discount codes that are automatically synced to all tenant storefronts.',
            'actionLabel' => 'Add Coupon',
            'tableTitle' => 'Discount Codes',
            'headers' => ['Code', 'Type', 'Value', 'Min. Spend', 'Window', 'Countries', 'Status', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $this->authorizePermission('store.coupons.manage');

        $records = CentralCoupon::query()
            ->with(['countries'])
            ->orderByDesc('id')
            ->paginate(15);

        $total = CentralCoupon::query()->count();
        $now = Carbon::now();
        $active = CentralCoupon::query()->where('active', true)
            ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $now))
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $now))
            ->count();
        $scheduled = CentralCoupon::query()->where('active', true)->where('start_date', '>', $now)->count();

        $rows = collect($records->items())->map(fn(CentralCoupon $coupon) => [
            '<div class="entity-title">' . e($coupon->code) . '</div>' . ($coupon->name ? '<div class="entity-subtitle">' . e($coupon->name) . '</div>' : ''),
            '<span class="badge badge-cyan">' . e($coupon->type->label()) . '</span>',
            $coupon->type === CouponType::Percentage
            ? e(number_format((float) $coupon->value, 2)) . '%'
            : '$' . e(number_format((float) $coupon->value, 2)),
            '$' . e(number_format((float) $coupon->minimum_spend, 2)),
            e(optional($coupon->start_date)->format('M d, Y') ?: '∞') . ' – ' . e(optional($coupon->end_date)->format('M d, Y') ?: '∞'),
            $coupon->countries->isEmpty()
                ? '<span class="badge badge-cyan">🌐 All</span>'
                : '<span class="badge badge-secondary" title="' . e($coupon->countries->pluck('name')->join(', ')) . '">🏳 ' . $coupon->countries->count() . '</span>',
            '<span class="badge ' . ($coupon->active ? 'badge-green' : 'badge-amber') . '">' . e($coupon->active ? 'Active' : 'Inactive') . '</span>',
            '<div class="flex gap-2">
                <button type="button" class="btn btn-secondary btn-sm" wire:click="editCoupon(' . $coupon->id . ')">Edit</button>
                <button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $coupon->id . ')">Delete</button>
            </div>',
        ])->all();

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'records' => $records,
            'rows' => $rows,
            'statistics' => [
                ['label' => 'Coupons', 'value' => number_format($total), 'caption' => 'Platform-wide discount codes', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => number_format($active), 'caption' => 'Currently usable by customers', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Scheduled', 'value' => number_format($scheduled), 'caption' => 'Coupons with a future start date', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->couponId ? 'Edit Coupon' : 'Add Coupon',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->couponId ? 'Update Coupon' : 'Create Coupon',
            'modalCountryPicker' => true,
            'modalCountryPickerHint' => 'Leave set to All Countries to make this coupon usable everywhere.',
            'countries' => \App\Models\Country::query()
                ->where('is_active_for_tenants', true)
                ->orderBy('name')
                ->get(['id', 'name', 'iso2', 'flag_emoji']),
            'modalFieldGroups' => [
                [
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Code', 'model' => 'code', 'type' => 'text'],
                        ['label' => 'Display Name', 'model' => 'name', 'type' => 'text'],
                        ['label' => 'Type', 'model' => 'type', 'type' => 'select', 'options' => collect(CouponType::cases())->mapWithKeys(fn($t) => [$t->value => $t->label()])->all()],
                        ['label' => 'Value', 'model' => 'value', 'type' => 'number'],
                        ['label' => 'Minimum Spend', 'model' => 'minimumSpend', 'type' => 'number'],
                        ['label' => 'Start Date', 'model' => 'startDate', 'type' => 'datetime-local'],
                        ['label' => 'End Date', 'model' => 'endDate', 'type' => 'datetime-local'],
                        ['label' => 'Status', 'model' => 'active', 'type' => 'checkbox', 'toggleLabel' => 'Coupon is active', 'live' => true],
                    ],
                ],
            ],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->couponId = null;
        $this->code = '';
        $this->name = '';
        $this->type = CouponType::Fixed->value;
        $this->value = '0.00';
        $this->minimumSpend = '0.00';
        $this->startDate = null;
        $this->endDate = null;
        $this->active = true;
        $this->assignedCountryIds = [];
        $this->allCountries = true;
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function editCoupon(int $couponId): void
    {
        $coupon = CentralCoupon::query()->findOrFail($couponId);

        $this->couponId = $coupon->id;
        $this->code = $coupon->code;
        $this->name = $coupon->name ?? '';
        $this->type = $coupon->type->value;
        $this->value = number_format((float) $coupon->value, 2, '.', '');
        $this->minimumSpend = number_format((float) $coupon->minimum_spend, 2, '.', '');
        $this->startDate = optional($coupon->start_date)->format('Y-m-d\TH:i');
        $this->endDate = optional($coupon->end_date)->format('Y-m-d\TH:i');
        $this->active = $coupon->active;

        $countryIds = $coupon->countries()->pluck('countries.id')->map(fn($id) => (string) $id)->all();
        $this->assignedCountryIds = $countryIds;
        $this->allCountries = empty($countryIds);

        $this->showFormModal = true;
    }

    public function save(CentralCouponService $service, CentralCatalogTenantSyncService $syncService): void
    {
        $this->authorizePermission('store.coupons.manage');

        $this->validate([
            'code' => 'required|string|max:60',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'minimumSpend' => 'nullable|numeric|min:0',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'assignedCountryIds' => 'array',
            'assignedCountryIds.*' => 'integer|exists:countries,id',
        ]);

        $existing = CentralCoupon::query()
            ->where('code', strtoupper(trim($this->code)))
            ->when($this->couponId, fn($q) => $q->where('id', '!=', $this->couponId))
            ->exists();

        if ($existing) {
            $this->addError('code', 'This coupon code is already in use.');
            return;
        }

        $coupon = $this->couponId ? CentralCoupon::query()->findOrFail($this->couponId) : null;

        $service->save([
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'minimum_spend' => filled($this->minimumSpend) ? $this->minimumSpend : 0,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'active' => $this->active,
            'country_ids' => $this->allCountries ? [] : array_map('intval', $this->assignedCountryIds),
        ], $coupon);

        // Observer triggers sync automatically; explicit call is a safety net.
        $syncService->syncCoupons();

        $this->closeModal();
        $this->toast($this->couponId ? 'Coupon updated and synced to tenants.' : 'Coupon created and synced to tenants.');
    }

    public function confirmDelete(int $couponId): void
    {
        $this->confirmAction('deleteCoupon', [$couponId], [
            'title' => 'Delete Coupon?',
            'text' => 'This will also remove it from all tenant storefronts.',
        ]);
    }

    public function deleteCoupon(int $couponId, CentralCouponService $service, CentralCatalogTenantSyncService $syncService): void
    {
        $this->authorizePermission('store.coupons.manage');

        $service->delete(CentralCoupon::query()->findOrFail($couponId));
        $syncService->syncCoupons();

        $this->toast('Coupon deleted and removed from all tenant storefronts.');
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->couponId = null;
        $this->code = '';
        $this->name = '';
        $this->type = CouponType::Fixed->value;
        $this->value = '0.00';
        $this->minimumSpend = '0.00';
        $this->startDate = null;
        $this->endDate = null;
        $this->active = true;
        $this->assignedCountryIds = [];
        $this->allCountries = true;
        $this->resetErrorBag();
    }

    public function clearFilters(): void
    {
    }
}
