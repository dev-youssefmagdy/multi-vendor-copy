<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant\Language;
use App\Models\Tenant\LanguagePurchase;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;

class LanguagesPage extends ListPage
{
    use InteractsWithTenantUi;

    protected function pageMeta(): array
    {
        return [
            'title' => __('Languages'),
            'badge' => __('Settings'),
            'description' => __('Control tenant storefront languages, activation, and the default locale.'),
            'tableTitle' => __('Tenant Languages'),
            'headers' => [__('Language'), __('Direction'), __('Type'), __('Status'), __('Actions')],
        ];
    }

    protected function pageData(): array
    {
        $languages = app(TenantPanelRepository::class)->languages();

        // Get IDs of centrally paid languages this tenant has purchased
        $purchasedIds = LanguagePurchase::query()->pluck('central_language_id')->all();

        return array_merge(parent::pageData(), [
            'actionLabel' => null,
            'rows' => $languages->map(function (Language $language) use ($purchasedIds) {
                $isPurchased = $language->central_language_id && in_array($language->central_language_id, $purchasedIds, true);
                $isFree = !$language->central_language_id || !$isPurchased
                    ? (CentralLanguage::query()->where('id', $language->central_language_id)->value('is_free') ?? true)
                    : false;
                $typeLabel = $isFree
                    ? '<span class="badge badge-green">' . e(__('Free')) . '</span>'
                    : '<span class="badge badge-violet">' . e(__('Purchased')) . '</span>';

                return [
                    ($language->imageFile ? '<img src="' . e($language->imageFile->full_path) . '" alt="' . e($language->name) . '" style="width:36px;height:24px;object-fit:cover;border-radius:4px;margin-right:8px;vertical-align:middle" />' : '') . e($language->code . ' - ' . ($language->native_name ?: $language->name)),
                    e(strtoupper($language->direction->value)),
                    $typeLabel,
                    '<span class="badge ' . ($language->is_default ? 'badge-green' : ($language->is_active ? 'badge-cyan' : 'badge-amber')) . '">' . e($language->is_default ? __('Default') : ($language->is_active ? __('Active') : __('Inactive'))) . '</span>',
                    '<div class="flex gap-2"><button type="button" class="btn btn-secondary btn-sm" wire:click="toggleActive(' . $language->id . ')">' . ($language->is_active ? __('Disable') : __('Enable')) . '</button><button type="button" class="btn btn-secondary btn-sm" wire:click="makeDefault(' . $language->id . ')">' . e(__('Make Default')) . '</button></div>',
                ];
            })->all(),
            'statistics' => [
                ['label' => __('Languages'), 'value' => number_format($languages->count()), 'caption' => __('Languages available in tenant DB'), 'dot' => 'dot-cyan'],
                ['label' => __('Active'), 'value' => number_format($languages->where('is_active', true)->count()), 'caption' => __('Locales visible on storefront'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Default'), 'value' => e(optional($languages->firstWhere('is_default', true))->code ?? '-'), 'caption' => __('Current default locale'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => __('Purchased'), 'value' => number_format(count($purchasedIds)), 'caption' => __('Paid language add-ons purchased'), 'dot' => 'dot-violet'],
            ],
        ]);
    }

    public function toggleActive(int $languageId): void
    {
        $language = Language::query()->findOrFail($languageId);
        $language->update(['is_active' => !$language->is_active]);

        $this->dispatch('setup-step-completed');
        $this->toast(__('Language state updated successfully.'));
    }

    public function makeDefault(int $languageId, TenantPanelService $service): void
    {
        $service->markDefaultLanguage(Language::query()->findOrFail($languageId));

        $this->dispatch('setup-step-completed');
        $this->toast(__('Default language updated successfully.'));
    }

    public function clearFilters(): void
    {
    }
}
