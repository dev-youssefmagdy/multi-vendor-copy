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
            'title' => 'Languages',
            'badge' => 'Settings',
            'description' => 'Control tenant storefront languages, activation, and the default locale.',
            'tableTitle' => 'Tenant Languages',
            'headers' => ['Language', 'Direction', 'Type', 'Status', 'Actions'],
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
                    ? '<span class="badge badge-green">Free</span>'
                    : '<span class="badge badge-violet">Purchased</span>';

                return [
                    ($language->imageFile ? '<img src="' . e($language->imageFile->full_path) . '" alt="' . e($language->name) . '" style="width:36px;height:24px;object-fit:cover;border-radius:4px;margin-right:8px;vertical-align:middle" />' : '') . e($language->code . ' - ' . ($language->native_name ?: $language->name)),
                    e(strtoupper($language->direction->value)),
                    $typeLabel,
                    '<span class="badge ' . ($language->is_default ? 'badge-green' : ($language->is_active ? 'badge-cyan' : 'badge-amber')) . '">' . e($language->is_default ? 'Default' : ($language->is_active ? 'Active' : 'Inactive')) . '</span>',
                    '<div class="flex gap-2"><button type="button" class="btn btn-secondary btn-sm" wire:click="toggleActive(' . $language->id . ')">' . ($language->is_active ? 'Disable' : 'Enable') . '</button><button type="button" class="btn btn-secondary btn-sm" wire:click="makeDefault(' . $language->id . ')">Make Default</button></div>',
                ];
            })->all(),
            'statistics' => [
                ['label' => 'Languages', 'value' => number_format($languages->count()), 'caption' => 'Languages available in tenant DB', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => number_format($languages->where('is_active', true)->count()), 'caption' => 'Locales visible on storefront', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Default', 'value' => e(optional($languages->firstWhere('is_default', true))->code ?? '-'), 'caption' => 'Current default locale', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Purchased', 'value' => number_format(count($purchasedIds)), 'caption' => 'Paid language add-ons purchased', 'dot' => 'dot-violet'],
            ],
        ]);
    }

    public function toggleActive(int $languageId): void
    {
        $language = Language::query()->findOrFail($languageId);
        $language->update(['is_active' => !$language->is_active]);

        $this->dispatch('setup-step-completed');
        $this->toast('Language state updated successfully.');
    }

    public function makeDefault(int $languageId, TenantPanelService $service): void
    {
        $service->markDefaultLanguage(Language::query()->findOrFail($languageId));

        $this->dispatch('setup-step-completed');
        $this->toast('Default language updated successfully.');
    }

    public function clearFilters(): void
    {
    }
}
