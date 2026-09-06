<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Country;
use App\Models\Tenant\Banner;
use App\Repositories\Tenant\StorefrontRepository;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

class BannersPage extends TenantPage
{
    use InteractsWithTenantUi;
    use WithFileUploads;

    public ?int $countryId = null;

    // ── Modal state ───────────────────────────────────────────────────────────
    public bool $bannerModalOpen = false;
    public ?int $bannerId = null;
    public string $bannerUrl = '';
    public int $bannerSerial = 0;
    public $bannerImage = null;
    public array $bannerTranslations = [];

    public function mount(?int $countryId = null): void
    {
        $this->countryId = $countryId;
        $this->resetBannerForm();
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Banners',
            'badge' => 'Storefront',
            'description' => 'Homepage and promotional banners with multi-language content.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.banners-page';
    }

    protected function pageData(): array
    {
        $repo = app(TenantPanelRepository::class);
        $storefrontRepo = app(StorefrontRepository::class);
        $activeTheme = $storefrontRepo->currentTheme();
        $bannerDimensions = config('image_dimensions.themes.' . ($activeTheme->slug ?? ''));

        $country = $this->countryId ? Country::query()->find($this->countryId) : null;

        return array_merge(parent::pageData(), [
            'languages' => $repo->activeLanguages(),
            'banners' => Banner::query()
                ->with('translations.language')
                ->where('country_id', $this->countryId)
                ->orderBy('serial_number')
                ->get(),
            'country' => $country,
            'activeThemeLabel' => $bannerDimensions['label'] ?? ($activeTheme->name ?? 'your theme'),
            'bannerWidth' => $bannerDimensions['width'] ?? null,
            'bannerHeight' => $bannerDimensions['height'] ?? null,
        ]);
    }

    public function openBannerModal(?int $id = null): void
    {
        $this->resetBannerForm();

        if ($id) {
            $banner = Banner::query()->with('translations.language')->findOrFail($id);
            $this->bannerId = $banner->id;
            $this->bannerUrl = (string) ($banner->url ?? '');
            $this->bannerSerial = (int) $banner->serial_number;
            $this->bannerTranslations = array_replace_recursive(
                $this->bannerTranslations,
                $banner->translationsByLocale(['title', 'subtitle', 'button_text'])
            );
        }

        $this->bannerModalOpen = true;
    }

    public function saveBanner(TenantPanelService $service): void
    {
        if (!$this->bannerId) {
            $limitService = app(PlanLimitService::class);
            if (!$limitService->canPerform(tenant(), PlanLimitService::FEATURE_BANNERS)) {
                $message = $limitService->errorMessage(PlanLimitService::FEATURE_BANNERS);
                $this->dispatch('admin-toast', message: $message, type: 'error');
                $this->addError('bannerUrl', $message);

                return;
            }
        }

        $rules = [
            'bannerUrl' => ['nullable', 'url', 'max:500'],
            'bannerSerial' => ['required', 'integer', 'min:0'],
            'bannerImage' => ['nullable', 'image', 'max:2048'],
        ];
        foreach (array_keys($this->bannerTranslations) as $locale) {
            $rules["bannerTranslations.{$locale}.title"] = ['nullable', 'string', 'max:255'];
            $rules["bannerTranslations.{$locale}.subtitle"] = ['nullable', 'string', 'max:500'];
            $rules["bannerTranslations.{$locale}.button_text"] = ['nullable', 'string', 'max:100'];
        }
        $this->validate($rules);

        $imagePath = $this->bannerId ? Banner::query()->find($this->bannerId)?->image_path : null;
        if ($this->bannerImage) {
            $imagePath = $this->bannerImage->store('appearances/banners', 'public');
            $imagePath = tenant_asset($imagePath);
        }

        $service->saveBanner([
            'url' => $this->bannerUrl ?: null,
            'image_path' => $imagePath,
            'serial_number' => $this->bannerSerial,
            'country_id' => $this->countryId,
            'translations' => $this->bannerTranslations,
        ], $this->bannerId ? Banner::query()->findOrFail($this->bannerId) : null);

        $this->bannerModalOpen = false;
        $this->resetBannerForm();
        $this->toast('Banner saved successfully.');
    }

    public function confirmDeleteBanner(int $id): void
    {
        $this->confirmAction('deleteBanner', [$id], [
            'title' => 'Delete banner?',
            'confirmButtonText' => 'Delete banner',
        ]);
    }

    public function deleteBanner(int $id): void
    {
        Banner::query()->findOrFail($id)->delete();
        $this->toast('Banner deleted.');
    }

    public function updateBannerOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Banner::query()->where('id', (int) $id)->update(['serial_number' => $index]);
        }

        $this->toast('Banner order saved.');
    }

    public function closeBannerModal(): void
    {
        $this->bannerModalOpen = false;
        $this->resetBannerForm();
    }

    protected function resetBannerForm(): void
    {
        $this->bannerId = null;
        $this->bannerUrl = '';
        $this->bannerSerial = 0;
        $this->bannerImage = null;

        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $this->bannerTranslations = $languages->mapWithKeys(fn($l) => [
            $l->code => ['title' => '', 'subtitle' => '', 'button_text' => ''],
        ])->all();

        $this->resetErrorBag();
    }
}
