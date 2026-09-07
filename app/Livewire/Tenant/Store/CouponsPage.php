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

    public bool $showFormModal = false;
    public ?int $couponId = null;
    public string $code = '';
    public string $type = 'fixed';
    public string $value = '0.00';
    public string $minimumSpend = '0.00';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $nameText = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Coupons',
            'badge' => 'Storefront',
            'description' => 'Create and manage discount codes for the current tenant storefront.',
            'actionLabel' => 'Add Coupon',
            'tableTitle' => 'Discount Codes',
            'headers' => ['Coupon', 'Type', 'Value', 'Window', 'Countries', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateCoupons();
        $stats = $repository->couponStats();

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'records' => $records,
            'statistics' => [
                ['label' => 'Coupons', 'value' => number_format($stats['total']), 'caption' => 'Coupons stored for this tenant', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'caption' => 'Coupons currently usable', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Scheduled', 'value' => number_format($stats['scheduled']), 'caption' => 'Coupons with future start dates', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn(Coupon $coupon) => [
                e($coupon->name ?? $coupon->code),
                e($coupon->type->label()),
                $coupon->type === CouponType::Percentage ? e(number_format((float) $coupon->value, 2)) . '%' : '$' . e(number_format((float) $coupon->value, 2)),
                e(optional($coupon->start_date)->format('M d, Y') ?: '-') . ' - ' . e(optional($coupon->end_date)->format('M d, Y') ?: '-'),
                !empty($coupon->allowed_country_ids)
                    ? '<span class="badge badge-secondary" title="' . e(implode(', ', array_slice((array) $coupon->allowed_country_ids, 0, 5))) . '">' .
                        count((array) $coupon->allowed_country_ids) . ' ' . (count((array) $coupon->allowed_country_ids) === 1 ? 'country' : 'countries') . '</span>'
                    : '<span class="badge badge-cyan">🌐 All</span>',
                '<div class="flex gap-2"><button type="button" class="btn btn-secondary btn-sm" wire:click="editCoupon(' . $coupon->id . ')">Edit</button><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $coupon->id . ')">Delete</button></div>',
            ])->all(),
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->couponId ? 'Edit Coupon' : 'Add Coupon',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->couponId ? 'Update Coupon' : 'Create Coupon',
            'modalFieldGroups' => [
                [
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Code', 'model' => 'code'],
                        ['label' => 'Name', 'model' => 'nameText'],
                        ['label' => 'Type', 'model' => 'type', 'type' => 'select', 'options' => collect(CouponType::cases())->mapWithKeys(fn($type) => [$type->value => $type->label()])->all()],
                        ['label' => 'Value', 'model' => 'value', 'type' => 'number'],
                        ['label' => 'Minimum Spend', 'model' => 'minimumSpend', 'type' => 'number'],
                        ['label' => 'Start Date', 'model' => 'startDate', 'type' => 'datetime-local'],
                        ['label' => 'End Date', 'model' => 'endDate', 'type' => 'datetime-local'],
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
                $this->addError('value', "Discount percentage must be less than the store's profit percentage ({$maxPercentage}%).");
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
        ], $couponId ? Coupon::query()->findOrFail($couponId) : null);

        $this->closeModal();
        $this->resetForm();
        $this->toast($couponId ? 'Coupon updated successfully.' : 'Coupon created successfully.');
    }

    public function confirmDelete(int $couponId): void
    {
        $this->confirmAction('deleteCoupon', [$couponId], ['title' => 'Delete coupon?', 'confirmButtonText' => 'Delete coupon']);
    }

    public function deleteCoupon(int $couponId, TenantPanelService $service): void
    {
        $service->deleteModel(Coupon::query()->findOrFail($couponId));
        $this->toast('Coupon deleted successfully.');
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
