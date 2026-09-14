<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Store\SaveColorsRequest;
use App\Http\Requests\Tenant\Panel\Store\SaveFooterRequest;
use App\Http\Requests\Tenant\Panel\Store\SaveGeneralAppearanceRequest;
use App\Http\Requests\Tenant\Panel\Store\SavePromoBannerRequest;
use App\Models\Tenant\Setting;
use App\Repositories\Tenant\StorefrontRepository;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AppearanceController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $languages = $this->repo->activeLanguages();
        $settings = $this->repo->appearanceSettings();
        $storefrontRepo = app(StorefrontRepository::class);
        $activeTheme = $storefrontRepo->currentTheme();
        $bannerDimensions = config('image_dimensions.themes.' . ($activeTheme->slug ?? ''));

        return view('tenant.pages.store.appearance.index', [
            'title' => 'Appearance',
            'badge' => 'Storefront',
            'description' => 'Manage your storefront logo, banners, social links, and footer.',
            'previewUrl' => $this->repo->appearancePreviewUrl(),
            'activeTab' => $request->query('tab', 'general'),
            'logoFonts' => StorefrontRepository::LOGO_FONTS,
            'general' => [
                'logo_mode' => ($settings['logo_mode'] ?? '') === 'text' ? 'text' : 'image',
                'logo_text_ar' => (string) ($settings['logo_text_ar'] ?? ''),
                'logo_text_en' => (string) ($settings['logo_text_en'] ?? ''),
                'logo_color' => ($settings['logo_color'] ?? '') ?: '#111827',
                'logo_bg_color' => ($settings['logo_bg_color'] ?? '') ?: '#ffffff',
                'logo_shape' => ($settings['logo_shape'] ?? '') === 'rounded' ? 'rounded' : 'rectangle',
                'logo_font_ar' => ($settings['logo_font_ar'] ?? '') ?: 'cairo',
                'logo_font_en' => ($settings['logo_font_en'] ?? '') ?: 'poppins',
                'logo_path_ar' => ($settings['logo_path_ar'] ?? '') ?: null,
                'logo_path_en' => ($settings['logo_path_en'] ?? '') ?: null,
            ],
            'colorThemes' => $this->repo->appearanceColorThemes(),
            'socialLinks' => $this->repo->socialLinks(),
            'promoBanner' => [
                'promo_banner_title' => $settings['promo_banner_title'] ?? '',
                'promo_banner_subtitle' => $settings['promo_banner_subtitle'] ?? '',
                'promo_banner_link' => $settings['promo_banner_link'] ?? '',
                'promo_banner_cta_text' => $settings['promo_banner_cta_text'] ?? '',
                'promo_banner_image_url' => $settings['promo_banner_image_url'] ?? '',
            ],
            'languages' => $languages,
            'footerTranslations' => $this->footerTranslations($languages),
            'activeLocale' => $languages->firstWhere('is_default', true)?->code ?? $languages->first()?->code ?? config('app.fallback_locale', 'en'),
        ]);
    }

    /** Per-locale footer copy: ['en' => ['footer_text' => '…', 'footer_copyright' => '…'], …]. */
    private function footerTranslations($languages): array
    {
        $translations = $languages->mapWithKeys(fn ($l) => [
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
                if (!isset($translations[$locale])) {
                    continue;
                }
                $translations[$locale][$name] = (string) ($payload['value'] ?? '');
            }
        }

        // Seed the default-locale tab from any legacy scalar value so admins
        // who saved footer copy before translations existed don't lose it.
        $defaultLocale = $languages->firstWhere('is_default', true)?->code
            ?? $languages->first()?->code
            ?? config('app.fallback_locale', 'en');

        if (isset($translations[$defaultLocale])) {
            $legacyText = (string) (Setting::query()->where('name', 'footer_text')->value('value') ?? '');
            $legacyCopyright = (string) (Setting::query()->where('name', 'footer_copyright')->value('value') ?? '');

            if ($translations[$defaultLocale]['footer_text'] === '' && $legacyText !== '') {
                $translations[$defaultLocale]['footer_text'] = $legacyText;
            }
            if ($translations[$defaultLocale]['footer_copyright'] === '' && $legacyCopyright !== '') {
                $translations[$defaultLocale]['footer_copyright'] = $legacyCopyright;
            }
        }

        return $translations;
    }

    // ── General ───────────────────────────────────────────────────────────

    public function saveGeneral(SaveGeneralAppearanceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $logoPathAr = null;
        $logoPathEn = null;

        if ($request->hasFile('logo_upload_ar')) {
            $logoPathAr = tenant_asset($request->file('logo_upload_ar')->store('appearances/logos', 'public'));
        }
        if ($request->hasFile('logo_upload_en')) {
            $logoPathEn = tenant_asset($request->file('logo_upload_en')->store('appearances/logos', 'public'));
        }

        $current = $this->repo->appearanceSettings();

        $this->service->saveAppearanceSettings([
            'logo_mode' => $validated['logo_mode'],
            'logo_text_ar' => $validated['logo_text_ar'] ?? '',
            'logo_text_en' => $validated['logo_text_en'] ?? '',
            'logo_color' => $validated['logo_color'],
            'logo_bg_color' => $validated['logo_bg_color'],
            'logo_shape' => $validated['logo_shape'],
            'logo_font_ar' => $validated['logo_font_ar'],
            'logo_font_en' => $validated['logo_font_en'],
            'logo_path_ar' => $logoPathAr ?? ($current['logo_path_ar'] ?? ''),
            'logo_path_en' => $logoPathEn ?? ($current['logo_path_en'] ?? ''),
        ]);

        return $this->success('General settings saved successfully.');
    }

    public function validateGeneral(SaveGeneralAppearanceRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    // ── Colors ────────────────────────────────────────────────────────────

    public function saveColors(SaveColorsRequest $request, int $theme, int $variant): JsonResponse
    {
        $defaults = $this->repo->themeVariantColorDefaults($theme, $variant);
        if (empty($defaults)) {
            return $this->failure('That theme variant has no customizable colors.', 404);
        }

        $values = array_merge($defaults, $request->validated('values', []));

        $this->service->saveThemeColors($theme, $variant, null, $values);

        return $this->success('Storefront colors saved successfully.');
    }

    public function validateColors(SaveColorsRequest $request, int $theme, int $variant): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function resetColors(int $theme, int $variant): JsonResponse
    {
        $defaults = $this->repo->themeVariantColorDefaults($theme, $variant);
        if (empty($defaults)) {
            return $this->failure('That theme variant has no customizable colors.', 404);
        }

        $this->service->resetThemeColors($theme, $variant, null);

        return $this->success('Storefront colors reset to the default.', ['defaults' => $defaults]);
    }

    // ── Promo banner ──────────────────────────────────────────────────────

    public function savePromoBanner(SavePromoBannerRequest $request): JsonResponse
    {
        $this->service->savePromoBannerSettings($request->validated());

        return $this->success('Promotional banner updated.');
    }

    public function validatePromoBanner(SavePromoBannerRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    // ── Footer ────────────────────────────────────────────────────────────

    public function saveFooter(SaveFooterRequest $request): JsonResponse
    {
        $this->service->saveFooterTranslations($request->validated('translations', []));

        return $this->success('Footer settings saved successfully.');
    }

    public function validateFooter(SaveFooterRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
