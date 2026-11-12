<?php

namespace App\Livewire\Tenant\Store;

use App\Enums\Tenant\CouponType;
use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Coupon;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Livewire\WithPagination;

class CouponsPage extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public ?int $countryId = null;

    public bool $showFormModal = false;
    public ?int $couponId = null;
    public string $code = '';
    public string $type = 'fixed';
    public string $value = '0.00';
    public string $minimumSpend = '0.00';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $nameText = '';

    public function mount(?int $countryId = null): void
    {
        $this->countryId = $countryId;
    }

    protected function pageMeta(): array
    {
        $country = $this->countryId ? \App\Models\Country::query()->find($this->countryId) : null;

        return [
            'title' => $country ? __('Coupons — :flag :name', ['flag' => $country->flag_emoji, 'name' => $country->name]) : __('Coupons — Default'),
            'badge' => __('Storefront'),
            'description' => $country
                ? __('Discount codes for visitors from :name.', ['name' => $country->name])
                : __('Default coupons shown when no country-specific coupons exist.'),
            'actionLabel' => __('Add Coupon'),
            'tableTitle' => __('Discount Codes'),
            'headers' => [__('Coupon'), __('Type'), __('Value'), __('Window'), __('Actions')],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateCoupons($this->countryId);
        $stats = $repository->couponStats($this->countryId);

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'secondaryActionLabel' => __('← All Countries'),
            'secondaryActionUrl' => route('tenant.store.coupons.index'),
            'records' => $records,
            'statistics' => [
                ['label' => __('Coupons'), 'value' => number_format($stats['total']), 'caption' => __('Coupons stored for this tenant'), 'dot' => 'dot-cyan'],
                ['label' => __('Active'), 'value' => number_format($stats['active']), 'caption' => __('Coupons currently usable'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Scheduled'), 'value' => number_format($stats['scheduled']), 'caption' => __('Coupons with future start dates'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn(Coupon $coupon) => [
                e($coupon->name ?? $coupon->code),
                e(__($coupon->type->label())),
                $coupon->type === CouponType::Percentage ? e(number_format((float) $coupon->value, 2)) . '%' : '$' . e(number_format((float) $coupon->value, 2)),
                e(optional($coupon->start_date)->format('M d, Y') ?: '-') . ' - ' . e(optional($coupon->end_date)->format('M d, Y') ?: '-'),
                '<div class="flex gap-2"><button type="button" class="btn btn-secondary btn-sm" wire:click="editCoupon(' . $coupon->id . ')">' . e(__('Edit')) . '</button><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $coupon->id . ')">' . e(__('Delete')) . '</button></div>',
            ])->all(),
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->couponId ? __('Edit Coupon') : __('Add Coupon'),
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->couponId ? __('Update Coupon') : __('Create Coupon'),
            'modalFieldGroups' => [
                [
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => __('Code'), 'model' => 'code'],
                        ['label' => __('Name'), 'model' => 'nameText'],
                        ['label' => __('Type'), 'model' => 'type', 'type' => 'select', 'options' => collect(CouponType::cases())->mapWithKeys(fn($type) => [$type->value => __($type->label())])->all()],
                        ['label' => __('Value'), 'model' => 'value', 'type' => 'number'],
                        ['label' => __('Minimum Spend'), 'model' => 'minimumSpend', 'type' => 'number'],
                        ['label' => __('Start Date'), 'model' => 'startDate', 'type' => 'datetime-local'],
                        ['label' => __('End Date'), 'model' => 'endDate', 'type' => 'datetime-local'],
                    ],
                ]
            ],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editCoupon(int $couponId): void
    {
        $coupon = Coupon::query()->with('translations.language')->findOrFail($couponId);
        $defaultLocale = app(TenantPanelRepository::class)->activeLanguages()->firstWhere('is_default', true)?->code ?? 'en';
        $translations = $coupon->translationsByLocale(['name']);
        $this->couponId = $coupon->id;
        $this->code = $coupon->code;
        $this->type = $coupon->type->value;
        $this->value = number_format((float) $coupon->value, 2, '.', '');
        $this->minimumSpend = number_format((float) $coupon->minimum_spend, 2, '.', '');
        $this->startDate = optional($coupon->start_date)->format('Y-m-d\TH:i');
        $this->endDate = optional($coupon->end_date)->format('Y-m-d\TH:i');
        $this->nameText = data_get($translations, $defaultLocale . '.name', '');
        $this->showFormModal = true;
    }

    public function save(TenantPanelService $service): void
    {
        $defaultLocale = app(TenantPanelRepository::class)->activeLanguages()->firstWhere('is_default', true)?->code ?? 'en';
        $couponId = $this->couponId;
        $validated = $this->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code,' . ($couponId ?? 'null')],
            'nameText' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:fixed,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'minimumSpend' => ['nullable', 'numeric', 'min:0'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
        ]);

        if ($validated['type'] === CouponType::Percentage->value) {
            $maxPercentage = (float) (tenant('profit_percentage') ?? 0);

            if ((float) $validated['value'] >= $maxPercentage) {
                $this->addError('value', __("Discount percentage must be less than the store's profit percentage (:max%).", ['max' => $maxPercentage]));
                return;
            }
        }

        $service->saveCoupon([
            'code' => $validated['code'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'minimum_spend' => filled($validated['minimumSpend'] ?? null) ? $validated['minimumSpend'] : 0,
            'start_date' => $validated['startDate'] ?? null,
            'end_date' => $validated['endDate'] ?? null,
            'translations' => [$defaultLocale => ['name' => $validated['nameText']]],
            'country_id' => $this->countryId,
        ], $couponId ? Coupon::query()->findOrFail($couponId) : null);

        $this->closeModal();
        $this->resetForm();
        $this->toast($couponId ? __('Coupon updated successfully.') : __('Coupon created successfully.'));
    }

    public function confirmDelete(int $couponId): void
    {
        $this->confirmAction('deleteCoupon', [$couponId], ['title' => __('Delete coupon?'), 'confirmButtonText' => __('Delete coupon')]);
    }

    public function deleteCoupon(int $couponId, TenantPanelService $service): void
    {
        $service->deleteModel(Coupon::query()->findOrFail($couponId));
        $this->toast(__('Coupon deleted successfully.'));
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetErrorBag();
    }

    protected function resetForm(): void
    {
        $this->reset(['couponId', 'code', 'nameText', 'startDate', 'endDate']);
        $this->type = CouponType::Fixed->value;
        $this->value = '0.00';
        $this->minimumSpend = '0.00';
        $this->resetErrorBag();
    }

    public function clearFilters(): void
    {
    }
}
