<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\HomeVariant;
use App\Models\Tenant\Setting;
use App\Models\Tenant\SocialLink;
use App\Models\Tenant\TenantHomeVariant;
use App\Repositories\Tenant\StorefrontRepository;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

class AppearancePage extends TenantPage
{
    use InteractsWithTenantUi;
    use WithFileUploads;

    // ── Tab ───────────────────────────────────────────────────────────────────
    #[Url]
    public string $activeTab = 'general';

    // ── General ───────────────────────────────────────────────────────────────
    public string $logoMode = 'image';
    public string $logoTextAr = '';
    public string $logoTextEn = '';
    public string $logoColor = '#111827';
    public string $logoBgColor = '#ffffff';
    public string $logoBgColorHex = '#ffffff';
    public string $logoShape = 'rectangle';
    public string $logoFontAr = 'cairo';
    public string $logoFontEn = 'poppins';
    public ?string $logoPathAr = null;
    public ?string $logoPathEn = null;
    public $logoUploadAr = null;
    public $logoUploadEn = null;

    public function toggleLogoBgTransparent(): void
    {
        if ($this->logoBgColor === 'transparent') {
            $this->logoBgColor = $this->logoBgColorHex;
        } else {
            $this->logoBgColor = 'transparent';
        }
    }

    public function updatedLogoBgColorHex(string $value): void
    {
        $this->logoBgColor = $value;
    }

    // ── Social Links ──────────────────────────────────────────────────────────
    public bool $socialModalOpen = false;
    public ?int $socialId = null;
    public string $socialIcon = 'facebook';
    public string $socialUrl = '';
    public int $socialSerial = 0;

    // ── Promo Banner ──────────────────────────────────────────────────────────
    public string $promoBannerTitle = '';
    public string $promoBannerSubtitle = '';
    public string $promoBannerLink = '';
    public string $promoBannerCtaText = '';
    public string $promoBannerImageUrl = '';

    // ── Footer ────────────────────────────────────────────────────────────────
    public string $footerText = '';
    public string $footerCopyright = '';

    /**
     * Per-locale footer copy.
     * Shape: ['en' => ['footer_text' => '…', 'footer_copyright' => '…'], …]
     */
    public array $footerTranslations = [];

    public function mount(): void
    {
        $repo = app(TenantPanelRepository::class);
        $languages = $repo->activeLanguages();

        $settings = $repo->appearanceSettings();
        $this->logoMode = ($settings['logo_mode'] ?? '') === 'text' ? 'text' : 'image';
        $this->logoTextAr = (string) ($settings['logo_text_ar'] ?? '');
        $this->logoTextEn = (string) ($settings['logo_text_en'] ?? '');
        $this->logoColor = ($settings['logo_color'] ?? '') ?: '#111827';
        $this->logoBgColor = ($settings['logo_bg_color'] ?? '') ?: '#ffffff';
        $this->logoBgColorHex = $this->logoBgColor !== 'transparent' ? $this->logoBgColor : '#ffffff';
        $this->logoShape = ($settings['logo_shape'] ?? '') === 'rounded' ? 'rounded' : 'rectangle';
        $this->logoFontAr = ($settings['logo_font_ar'] ?? '') ?: 'cairo';
        $this->logoFontEn = ($settings['logo_font_en'] ?? '') ?: 'poppins';
        $this->logoPathAr = ($settings['logo_path_ar'] ?? '') ?: null;
        $this->logoPathEn = ($settings['logo_path_en'] ?? '') ?: null;
        $this->footerText = $settings['footer_text'] ?? '';
        $this->footerCopyright = $settings['footer_copyright'] ?? '';

        $this->promoBannerTitle = $settings['promo_banner_title'] ?? '';
        $this->promoBannerSubtitle = $settings['promo_banner_subtitle'] ?? '';
        $this->promoBannerLink = $settings['promo_banner_link'] ?? '';
        $this->promoBannerCtaText = $settings['promo_banner_cta_text'] ?? '';
        $this->promoBannerImageUrl = $settings['promo_banner_image_url'] ?? '';

        // Build the per-locale matrix then overlay any saved translations.
        $this->footerTranslations = $languages->mapWithKeys(fn($l) => [
            $l->code => ['footer_text' => '', 'footer_copyright' => ''],
        ])->all();

        $footerSettings = Setting::query()
            ->whereIn('name', ['footer_text', 'footer_copyright'])
            ->with('translations.language')
            ->get()
            ->keyBy('name');

        foreach (['footer_text', 'footer_copyright'] as $name) {
            $setting = $footerSettings->get($name);
            if (!$setting) {
                continue;
            }
            foreach ($setting->translationsByLocale(['value']) as $locale => $payload) {
                if (!isset($this->footerTranslations[$locale])) {
                    continue;
                }
                $this->footerTranslations[$locale][$name] = (string) ($payload['value'] ?? '');
            }
        }

        // Seed the default-locale tab from any legacy scalar value so admins
        // who saved footer copy before translations existed don't lose it.
        $defaultLocale = $languages->firstWhere('is_default', true)?->code
            ?? $languages->first()?->code
            ?? config('app.fallback_locale', 'en');

        if (isset($this->footerTranslations[$defaultLocale])) {
            if ($this->footerTranslations[$defaultLocale]['footer_text'] === '' && $this->footerText !== '') {
                $this->footerTranslations[$defaultLocale]['footer_text'] = $this->footerText;
            }
            if ($this->footerTranslations[$defaultLocale]['footer_copyright'] === '' && $this->footerCopyright !== '') {
                $this->footerTranslations[$defaultLocale]['footer_copyright'] = $this->footerCopyright;
            }
        }
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.appearance-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Appearance',
            'badge' => 'Storefront',
            'description' => 'Manage your storefront logo, banners, social links, and footer.',
        ];
    }

    protected function pageData(): array
    {
        $repo = app(TenantPanelRepository::class);
        $storefrontRepo = app(StorefrontRepository::class);
        $activeTheme = $storefrontRepo->currentTheme();
        $bannerDimensions = config('image_dimensions.themes.' . ($activeTheme->slug ?? ''));

        return array_merge(parent::pageData(), [
            'languages' => $repo->activeLanguages(),
            'socialLinks' => $repo->socialLinks(),
            'colorThemes' => $repo->themes(),
            'previewUrl' => $this->previewUrl($storefrontRepo),
            'activeThemeLabel' => $bannerDimensions['label'] ?? ($activeTheme->name ?? 'your theme'),
            'bannerWidth' => $bannerDimensions['width'] ?? null,
            'bannerHeight' => $bannerDimensions['height'] ?? null,
        ]);
    }

    /** Build the `/preview` URL that mirrors this tenant's current theme, colors, and homepage variant. */
    protected function previewUrl(StorefrontRepository $repo): ?string
    {
        $theme = $repo->currentTheme();
        if (!$theme) {
            return null;
        }

        $query = ['theme' => $theme->slug];

        $variantId = TenantHomeVariant::query()
            ->where('theme_id', $theme->id)
            ->whereNull('country_id')
            ->value('home_variant_id');

        if ($variantId) {
            $variantKey = tenancy()->central(fn() => HomeVariant::query()->find($variantId)?->key);
            if ($variantKey) {
                $query['homepage_variant'] = $variantKey;
            }
        }

        $centralDomain = config('tenancy.central_domains.0')
            ?: (parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');
        $scheme = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_SCHEME) ?: 'http';

        return $scheme . '://' . $centralDomain . '/preview?' . http_build_query($query);
    }

    // ── Tab ───────────────────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetErrorBag();
    }

    // ── General ───────────────────────────────────────────────────────────────

    public function saveGeneral(TenantPanelService $service): void
    {
        $this->validate([
            'logoMode' => ['required', 'in:text,image'],
            'logoTextAr' => ['nullable', 'string', 'max:60'],
            'logoTextEn' => ['nullable', 'string', 'max:60'],
            'logoColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logoBgColor' => ['required', 'string', 'regex:/^(#[0-9a-fA-F]{6}|transparent)$/'],
            'logoShape' => ['required', 'in:rectangle,rounded'],
            'logoFontAr' => ['required', 'in:' . implode(',', array_keys(StorefrontRepository::LOGO_FONTS['ar']))],
            'logoFontEn' => ['required', 'in:' . implode(',', array_keys(StorefrontRepository::LOGO_FONTS['en']))],
            'logoUploadAr' => ['nullable', 'image', 'max:2048'],
            'logoUploadEn' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->logoUploadAr) {
            $this->logoPathAr = tenant_asset($this->logoUploadAr->store('appearances/logos', 'public'));
        }
        if ($this->logoUploadEn) {
            $this->logoPathEn = tenant_asset($this->logoUploadEn->store('appearances/logos', 'public'));
        }

        $service->saveAppearanceSettings([
            'logo_mode' => $this->logoMode,
            'logo_text_ar' => $this->logoTextAr,
            'logo_text_en' => $this->logoTextEn,
            'logo_color' => $this->logoColor,
            'logo_bg_color' => $this->logoBgColor,
            'logo_shape' => $this->logoShape,
            'logo_font_ar' => $this->logoFontAr,
            'logo_font_en' => $this->logoFontEn,
            'logo_path_ar' => $this->logoPathAr ?? '',
            'logo_path_en' => $this->logoPathEn ?? '',
        ]);

        $this->logoUploadAr = null;
        $this->logoUploadEn = null;
        $this->toast('General settings saved successfully.');
    }

    // ── Social Links ──────────────────────────────────────────────────────────

    public function openSocialModal(?int $id = null): void
    {
        $this->resetSocialForm();

        if ($id) {
            $link = SocialLink::query()->findOrFail($id);
            $this->socialId = $link->id;
            $this->socialIcon = $link->icon;
            $this->socialUrl = $link->url;
            $this->socialSerial = (int) $link->serial_number;
        }

        $this->socialModalOpen = true;
    }

    public function saveSocialLink(TenantPanelService $service): void
    {
        $this->validate([
            'socialIcon' => ['required', 'string', 'max:50'],
            'socialUrl' => ['required', 'url', 'max:500'],
            'socialSerial' => ['required', 'integer', 'min:0'],
        ]);

        $service->saveSocialLink([
            'icon' => $this->socialIcon,
            'url' => $this->socialUrl,
            'serial_number' => $this->socialSerial,
        ], $this->socialId ? SocialLink::query()->findOrFail($this->socialId) : null);

        $this->socialModalOpen = false;
        $this->resetSocialForm();
        $this->toast('Social link saved successfully.');
    }

    public function confirmDeleteSocial(int $id): void
    {
        $this->confirmAction('deleteSocialLink', [$id], [
            'title' => 'Delete social link?',
            'confirmButtonText' => 'Delete link',
        ]);
    }

    public function deleteSocialLink(int $id): void
    {
        SocialLink::query()->findOrFail($id)->delete();
        $this->toast('Social link deleted.');
    }

    public function closeSocialModal(): void
    {
        $this->socialModalOpen = false;
        $this->resetSocialForm();
    }

    protected function resetSocialForm(): void
    {
        $this->socialId = null;
        $this->socialIcon = 'facebook';
        $this->socialUrl = '';
        $this->socialSerial = 0;
        $this->resetErrorBag();
    }

    // ── Promo Banner ──────────────────────────────────────────────────────────

    public function savePromoBanner(TenantPanelService $service): void
    {
        $this->validate([
            'promoBannerTitle' => ['nullable', 'string', 'max:120'],
            'promoBannerSubtitle' => ['nullable', 'string', 'max:255'],
            'promoBannerLink' => ['nullable', 'url', 'max:500'],
            'promoBannerCtaText' => ['nullable', 'string', 'max:40'],
            'promoBannerImageUrl' => ['nullable', 'url', 'max:1000'],
        ]);

        $service->savePromoBannerSettings([
            'promo_banner_title' => $this->promoBannerTitle,
            'promo_banner_subtitle' => $this->promoBannerSubtitle,
            'promo_banner_link' => $this->promoBannerLink,
            'promo_banner_cta_text' => $this->promoBannerCtaText,
            'promo_banner_image_url' => $this->promoBannerImageUrl,
        ]);

        $this->toast('Promotional banner updated.');
    }

    // ── Footer ────────────────────────────────────────────────────────────────

    public function saveFooter(TenantPanelService $service): void
    {
        $rules = [];
        foreach (array_keys($this->footerTranslations) as $locale) {
            $rules["footerTranslations.{$locale}.footer_text"] = ['nullable', 'string', 'max:1000'];
            $rules["footerTranslations.{$locale}.footer_copyright"] = ['nullable', 'string', 'max:255'];
        }
        $this->validate($rules);

        $service->saveFooterTranslations($this->footerTranslations);

        // Keep the legacy scalar props aligned with the default-locale copy so
        // any consumer still reading them gets the latest value.
        $defaultLocale = app(TenantPanelRepository::class)
            ->activeLanguages()
            ->firstWhere('is_default', true)?->code
            ?? config('app.fallback_locale', 'en');
        $this->footerText = $this->footerTranslations[$defaultLocale]['footer_text'] ?? '';
        $this->footerCopyright = $this->footerTranslations[$defaultLocale]['footer_copyright'] ?? '';

        $this->toast('Footer settings saved successfully.');
    }
}
