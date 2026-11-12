<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Currency;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;

class CurrenciesPage extends ListPage
{
    use InteractsWithTenantUi;

    public string $search = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => __('Currencies'),
            'badge' => __('Settings'),
            'description' => __('Control active currencies and the tenant storefront default currency.'),
            'tableTitle' => __('Tenant Currencies'),
            'headers' => [__('Currency'), __('Rate'), __('Status'), __('Actions')],
        ];
    }

    protected function pageData(): array
    {
        $currencies = app(TenantPanelRepository::class)->currencies();

        if ($this->search !== '') {
            $search = mb_strtolower($this->search);
            $currencies = $currencies->filter(fn(Currency $c) =>
                str_contains(mb_strtolower($c->code), $search) ||
                str_contains(mb_strtolower($c->name), $search)
            );
        }

        if ($this->statusFilter !== '') {
            $currencies = match ($this->statusFilter) {
                'default'  => $currencies->where('is_default', true),
                'active'   => $currencies->where('is_active', true)->where('is_default', false),
                'inactive' => $currencies->where('is_active', false),
                default    => $currencies,
            };
        }

        return array_merge(parent::pageData(), [
            'actionLabel' => null,
            'filterFields' => [
                ['label' => __('Search'), 'model' => 'search', 'placeholder' => __('Code or name…')],
                ['label' => __('Status'), 'model' => 'statusFilter', 'type' => 'select', 'options' => [
                    ''         => __('All'),
                    'default'  => __('Default'),
                    'active'   => __('Active'),
                    'inactive' => __('Inactive'),
                ]],
            ],
            'rows' => $currencies->map(fn(Currency $currency) => [
                e($currency->code . ' - ' . $currency->name),
                e($currency->symbol ?: '$') . ' ' . e(number_format((float) $currency->conversion_rate, 6)),
                '<span class="badge ' . ($currency->is_default ? 'badge-green' : ($currency->is_active ? 'badge-cyan' : 'badge-amber')) . '">' . e($currency->is_default ? __('Default') : ($currency->is_active ? __('Active') : __('Inactive'))) . '</span>',
                '<div class="flex gap-2"><button type="button" class="btn btn-secondary btn-sm" wire:click="toggleActive(' . $currency->id . ')">' . ($currency->is_active ? __('Disable') : __('Enable')) . '</button></div>',
            ])->all(),
            'statistics' => [
                ['label' => __('Currencies'), 'value' => number_format($currencies->count()), 'caption' => __('Currencies synced into this tenant'), 'dot' => 'dot-cyan'],
                ['label' => __('Active'), 'value' => number_format($currencies->where('is_active', true)->count()), 'caption' => __('Currencies available on storefront'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Default'), 'value' => e(optional($currencies->firstWhere('is_default', true))->code ?? '-'), 'caption' => __('Current storefront settlement currency'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
        ]);
    }

    public function toggleActive(int $currencyId): void
    {
        $currency = Currency::query()->findOrFail($currencyId);
        $currency->update(['is_active' => !$currency->is_active]);
        $this->toast(__('Currency state updated successfully.'));
    }

    public function makeDefault(int $currencyId, TenantPanelService $service): void
    {
        $service->markDefaultCurrency(Currency::query()->findOrFail($currencyId));
        $this->toast(__('Default currency updated successfully.'));
    }

    public function updatedSearch(): void {}

    public function updatedStatusFilter(): void {}

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
    }
}
