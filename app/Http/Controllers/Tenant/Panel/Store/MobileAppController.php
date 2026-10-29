<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\HomeVariant;
use App\Models\Tenant\MobileAppSetting;
use App\Models\Tenant\MobileAppTheme;
use App\Services\Mobile\MobileSectionCatalog;
use App\Support\CacheVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Tenant panel: "Mobile App" settings + "App Themes" (independent of the web theme). */
final class MobileAppController extends PanelController
{
    public const FEATURES = ['favorites', 'returns', 'offers', 'guest_cart'];

    public const NAV_ITEMS = ['home', 'categories', 'search', 'cart', 'favorites', 'orders', 'account'];

    public const COLOR_TOKENS = ['primary', 'secondary', 'bg_main', 'text_primary', 'text_subtitle'];

    public function index(): View
    {
        $s = MobileAppSetting::allKeyed();
        $domain = tenant()?->domains()->first()?->domain;

        return view('tenant.pages.store.mobile.index', [
            's' => $s,
            'features' => array_merge(['favorites' => true, 'returns' => true, 'offers' => true, 'guest_cart' => false], (array) ($s['features'] ?? [])),
            'navSelected' => (array) ($s['nav_items'] ?? ['home', 'categories', 'cart', 'account']),
            'featureKeys' => self::FEATURES,
            'navItems' => self::NAV_ITEMS,
            'tenantCode' => (string) tenant('id'),
            'domain' => $domain,
            'landingUrl' => $domain ? 'https://'.$domain.'/app' : null,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $values = [
            'app_name' => $data['app_name'] ?? null,
            'primary_color' => $data['primary_color'] ?? null,
            'secondary_color' => $data['secondary_color'] ?? null,
            'features' => collect(self::FEATURES)->mapWithKeys(fn ($f) => [$f => (bool) ($data['features'][$f] ?? false)])->all(),
            'nav_items' => array_values($data['nav_items'] ?? []),
            'default_locale' => $data['default_locale'] ?? null,
            'default_currency' => $data['default_currency'] ?? null,
            'support_email' => $data['support_email'] ?? null,
            'support_phone' => $data['support_phone'] ?? null,
            'support_whatsapp' => $data['support_whatsapp'] ?? null,
            'play_store_url' => $data['play_store_url'] ?? null,
            'app_store_url' => $data['app_store_url'] ?? null,
            'min_app_version' => $data['min_app_version'] ?? null,
            'maintenance' => (bool) ($data['maintenance'] ?? false),
            'maintenance_message' => $data['maintenance_message'] ?? null,
        ];

        foreach (['logo', 'splash'] as $file) {
            if ($request->hasFile($file)) {
                $values[$file] = tenant_asset($request->file($file)->store('mobile', 'public'));
            }
        }

        foreach ($values as $key => $value) {
            MobileAppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        CacheVersion::bump('MobileApp');

        return $this->success('Mobile app settings saved.');
    }

    public function validateSave(Request $request): JsonResponse
    {
        $request->validate($this->rules());

        return $this->validFormResponse();
    }

    public function themes(): View
    {
        $active = MobileAppTheme::query()->latest('id')->first();
        $variants = tenancy()->central(fn () => HomeVariant::query()
            ->whereIn('theme_slug', MobileSectionCatalog::THEMES)
            ->where('is_active', true)
            ->orderBy('theme_slug')->orderBy('key')->get());

        $cards = $variants->map(fn (HomeVariant $v) => [
            'id' => $v->id,
            'theme_slug' => $v->theme_slug,
            'key' => $v->key,
            'name' => $v->name ?: strtoupper($v->theme_slug.' '.$v->key),
            'description' => $v->description,
            'preview' => $v->preview_image,
            'colors' => $this->normaliseColors((array) $v->colors),
            'initials' => strtoupper(substr((string) ($v->name ?: $v->theme_slug), 0, 2)),
            'is_active' => $active && $active->theme_slug === $v->theme_slug && $active->variant_key === $v->key,
        ])->values()->all();

        $overrides = (array) (MobileAppSetting::query()->where('key', 'color_overrides')->value('value') ?? []);

        return view('tenant.pages.store.mobile.themes', [
            'cards' => $cards,
            'activeLabel' => $active ? $active->theme_slug.' / '.$active->variant_key : null,
            'overrides' => $overrides,
            'colorTokens' => self::COLOR_TOKENS,
        ]);
    }

    public function activate(string $themeSlug, string $variantKey): JsonResponse
    {
        $data = ['theme_slug' => $themeSlug, 'variant_key' => $variantKey];
        if (! in_array($themeSlug, MobileSectionCatalog::THEMES, true)) {
            return $this->failure('Unknown theme variant.', 422);
        }

        $exists = tenancy()->central(fn () => HomeVariant::query()
            ->forTheme($data['theme_slug'])->where('key', $data['variant_key'])->where('is_active', true)->exists());

        if (! $exists) {
            return $this->failure('Unknown theme variant.', 422);
        }

        MobileAppTheme::query()->delete();
        MobileAppTheme::query()->create($data);
        CacheVersion::bump('MobileApp');

        return $this->success('App theme activated.');
    }

    public function saveColors(Request $request): JsonResponse
    {
        $rules = ['colors' => ['nullable', 'array']];
        foreach (self::COLOR_TOKENS as $t) {
            $rules['colors.'.$t] = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }
        $data = $request->validate($rules);

        $colors = collect($data['colors'] ?? [])->filter()->only(self::COLOR_TOKENS)->all();
        MobileAppSetting::query()->updateOrCreate(['key' => 'color_overrides'], ['value' => $colors]);
        CacheVersion::bump('MobileApp');

        return $this->success('Color overrides saved.');
    }

    /** @return array<string,string> tokens (primary, secondary, ...) from raw "--color-x" variant colours */
    private function normaliseColors(array $raw): array
    {
        $out = [];
        foreach ($raw as $name => $hex) {
            if (! is_string($hex) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
                continue;
            }
            $token = str_replace('-', '_', (string) preg_replace('/^--(color-)?/', '', (string) $name));
            $out[match ($token) {
                'subtitle' => 'text_subtitle',
                'black' => 'text_primary',
                default => $token,
            }] = $hex;
        }

        return $out;
    }

    private function rules(): array
    {
        $hex = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];

        return [
            'app_name' => ['nullable', 'string', 'max:60'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'splash' => ['nullable', 'image', 'max:4096'],
            'primary_color' => $hex,
            'secondary_color' => $hex,
            'features' => ['nullable', 'array'],
            'features.*' => ['boolean'],
            'nav_items' => ['nullable', 'array', 'max:5'],
            'nav_items.*' => ['string', Rule::in(self::NAV_ITEMS)],
            'default_locale' => ['nullable', 'string', 'max:10'],
            'default_currency' => ['nullable', 'string', 'max:10'],
            'support_email' => ['nullable', 'email', 'max:190'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'support_whatsapp' => ['nullable', 'string', 'max:30'],
            'play_store_url' => ['nullable', 'url', 'max:255'],
            'app_store_url' => ['nullable', 'url', 'max:255'],
            'min_app_version' => ['nullable', 'regex:/^\d+(\.\d+){0,2}$/'],
            'maintenance' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
