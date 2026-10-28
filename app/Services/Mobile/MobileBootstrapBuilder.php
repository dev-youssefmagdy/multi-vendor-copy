<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use App\Models\HomeVariant;
use App\Models\Tenant\MobileAppSetting;
use App\Models\Tenant\MobileAppTheme;
use App\Models\Tenant\Page;
use App\Models\Tenant\TenantPageSection;
use App\Models\Tenant\TenantThemeColor;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\PageBuilder\SectionRegistry;

/** Builds the `GET /bootstrap` payload. Runs inside tenancy. */
class MobileBootstrapBuilder
{
    public function __construct(private readonly StorefrontRepository $repo)
    {
    }

    /** @return array{theme_slug:string,variant:?HomeVariant} */
    public function resolveTheme(): array
    {
        $choice = MobileAppTheme::query()->latest('id')->first();
        $slug = $choice?->theme_slug;

        if (!$slug || !in_array($slug, MobileSectionCatalog::THEMES, true)) {
            // Mobile theme unset: follow the web theme if it is a built-in one,
            // otherwise (custom/uploaded themes are excluded) use ecommet.
            $webSlug = $this->repo->currentTheme()?->slug;
            $slug = in_array($webSlug, MobileSectionCatalog::THEMES, true) ? $webSlug : 'ecommet';
            $choice = null;
        }

        $variant = null;
        if ($choice) {
            $variant = HomeVariant::query()->forTheme($slug)->where('key', $choice->variant_key)->where('is_active', true)->first();
        } elseif ($slug === $this->repo->currentTheme()?->slug) {
            $variant = $this->repo->currentHomeVariant();
        }

        $variant ??= HomeVariant::query()->forTheme($slug)->where('is_default', true)->first()
            ?? HomeVariant::query()->forTheme($slug)->orderBy('id')->first();

        return ['theme_slug' => $slug, 'variant' => $variant];
    }

    /** @return string[] ordered visible section keys, filtered by the variant's allowed keys */
    public function sectionKeys(string $themeSlug, ?HomeVariant $variant): array
    {
        $variantKey = $variant?->key ?? 'v1';
        $allowed = MobileSectionCatalog::allowedKeys($themeSlug, $variantKey, $variant?->view);
        $theme = $this->repo->themeBySlug($themeSlug);

        $rows = $theme
            ? TenantPageSection::query()
                ->where('theme_id', $theme->id)
                ->when($variant, fn ($q) => $q->where('home_variant_id', $variant->id), fn ($q) => $q->whereNull('home_variant_id'))
                ->where('page', 'home')->orderBy('sort_order')->get()->keyBy('section_key')
            : collect();

        if ($rows->isNotEmpty()) {
            $keys = collect($rows->keys())
                ->merge(array_diff(SectionRegistry::defaultsFor($themeSlug, 'home'), $rows->keys()->all()))
                ->filter(fn (string $k) => !$rows->has($k) || $rows->get($k)->is_visible)
                ->values()->all();
        } elseif ($variant && $variant->sections) {
            $keys = $variant->sections;
        } else {
            $keys = SectionRegistry::defaultsFor($themeSlug, 'home');
        }

        return array_values(array_intersect($keys, $allowed));
    }

    /** @return array<string,string> normalised tokens (primary, secondary, bg_main, ...) */
    public function colors(string $themeSlug, ?HomeVariant $variant): array
    {
        $raw = (array) ($variant?->colors ?? []);
        $theme = $this->repo->themeBySlug($themeSlug);

        if ($theme && $variant) {
            $override = TenantThemeColor::query()->where('theme_id', $theme->id)
                ->where('home_variant_id', $variant->id)->whereNull('country_id')->value('colors');
            $override = is_string($override) ? json_decode($override, true) : $override;
            $raw = array_merge($raw, (array) $override);
        }

        $tokens = [];
        foreach ($raw as $name => $hex) {
            $token = str_replace('-', '_', preg_replace('/^--(color-)?/', '', (string) $name));
            $tokens[match ($token) {
                'subtitle' => 'text_subtitle',
                'black' => 'text_primary',
                default => $token,
            }] = $hex;
        }

        // Panel "Mobile App" overrides win over variant defaults and tenant_theme_colors.
        $settings = MobileAppSetting::allKeyed();
        foreach ((array) ($settings['color_overrides'] ?? []) as $token => $hex) {
            if (is_string($hex) && $hex !== '') {
                $tokens[str_replace('-', '_', preg_replace('/^--(color-)?/', '', (string) $token))] = $hex;
            }
        }
        foreach (['primary_color' => 'primary', 'secondary_color' => 'secondary'] as $key => $token) {
            if (!empty($settings[$key]) && is_string($settings[$key])) {
                $tokens[$token] = $settings[$key];
            }
        }

        return $tokens;
    }

    public function build(?string $appVersion = null): array
    {
        ['theme_slug' => $slug, 'variant' => $variant] = $this->resolveTheme();
        $settings = MobileAppSetting::allKeyed();
        $logo = $this->repo->resolvedLogo();
        $variantKey = $variant?->key ?? 'v1';

        $sections = collect($this->sectionKeys($slug, $variant))->map(fn (string $key) => [
            'key' => $key,
            'widget' => MobileSectionCatalog::widgetFor($key),
            'source' => MobileSectionCatalog::sourceFor($key),
            'data_endpoint' => '/api/mobile/v1/home/sections/'.$key,
        ])->all();

        $payload = [
            'tenant' => [
                'id' => (string) tenant('id'),
                'name' => $this->repo->storeName(),
            ],
            'settings' => $settings,
            'features' => array_merge([
                'favorites' => true, 'returns' => true, 'offers' => true, 'guest_cart' => false,
            ], (array) ($settings['features'] ?? [])),
            'theme' => [
                'slug' => $slug,
                'variant_key' => $variantKey,
                'variant_name' => $variant?->name,
                'colors' => $this->colors($slug, $variant),
            ],
            'sections' => $sections,
            'allowed_section_keys' => MobileSectionCatalog::allowedMap(),
            'branding' => [
                'logo' => $logo,
                'splash_image_url' => ($settings['splash'] ?? null) ?: null,
                'app_name' => ($settings['app_name'] ?? null) ?: $this->repo->storeName(),
            ],
            'social_links' => $this->repo->socialLinks()->map(fn ($l) => [
                'icon' => $l->icon instanceof \BackedEnum ? $l->icon->value : $l->icon,
                'url' => $l->url,
            ])->values()->all(),
            'footer' => ['text' => $this->repo->footerText(), 'copyright' => $this->repo->footerCopyright()],
            'locales' => $this->repo->activeLanguages()->map(fn ($l) => [
                'code' => $l->code, 'name' => $l->native_name ?: $l->name,
                'rtl' => $l->direction === 'rtl', 'default' => (bool) $l->is_default,
            ])->values()->all(),
            'currencies' => $this->repo->activeCurrencies()->map(fn ($c) => [
                'code' => $c->code, 'symbol' => $c->symbol,
                'rate' => (float) $c->conversion_rate, 'default' => (bool) $c->is_default,
            ])->values()->all(),
            'pages' => Page::query()->where('active', true)->get()->map(fn ($p) => [
                'slug' => $p->slug, 'title' => $p->translationValue('title') ?? $p->slug,
            ])->values()->all(),
        ];

        // Hash only config (not per-request status) so the ETag is stable.
        $version = substr(sha1(json_encode($payload)), 0, 16);

        $minVersion = $settings['min_app_version'] ?? null;
        $payload['status'] = [
            'maintenance' => (bool) ($settings['maintenance'] ?? false),
            'update_required' => $minVersion && $appVersion && version_compare($appVersion, (string) $minVersion, '<'),
            'min_app_version' => $minVersion,
        ];
        $payload['config_version'] = $version;

        return $payload;
    }
}
