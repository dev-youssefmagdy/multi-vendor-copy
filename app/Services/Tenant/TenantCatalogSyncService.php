<?php

namespace App\Services\Tenant;

use App\Enums\ActivationStatus;
use App\Enums\AppSettingType;
use App\Enums\ContentStatus;
use App\Enums\EmailTemplateType;
use App\Enums\PaymentGatewayType;
use App\Enums\Tenant\SettingType;
use App\Models\AppSetting as CentralAppSetting;
use App\Models\Category as CentralCategory;
use App\Models\Currency as CentralCurrency;
use App\Models\EmailTemplate as CentralEmailTemplate;
use App\Models\FixedShippingCost;
use App\Models\Language as CentralLanguage;
use App\Models\PaymentGateway as CentralPaymentGateway;
use App\Models\Product as CentralProduct;
use App\Models\ProductBadge as CentralProductBadge;
use App\Models\ProductTenantAssignment;
use App\Models\StaticPage as CentralStaticPage;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use App\Models\Tenant\Category;
use App\Models\TenantLanguagePurchase;
use App\Services\TenantNotificationService;
use App\Models\Tenant\Currency;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\EmailTemplateTranslation;
use App\Models\Tenant\Language;
use App\Models\Tenant\Page;
use App\Models\Tenant\PaymentGateway;
use App\Models\Tenant\Product;
use App\Models\CentralCoupon;
use App\Models\CentralFlashSale;
use App\Models\TenantCountry;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\ProductBadge;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Theme;
use App\Models\Tenant\ThemeCountry;
use App\Models\Template as CentralTemplate;
use InvalidArgumentException;

class TenantCatalogSyncService
{
    protected const SECTIONS = [
        'languages',
        'currencies',
        'settings',
        'payment-gateways',
        'themes',
        'email-templates',
        'pages',
        'categories',
        'products',
        'badges',
        'flash-sales',
        'coupons',
    ];

    protected float $profitPercentage = 0;

    public function syncForTenant(Tenant $tenant, ?array $sections = null): array
    {
        $sections = $this->normalizeSections($sections);
        $summary = array_fill_keys($sections, 0);

        set_time_limit(0);

        $this->profitPercentage = max(0, (float) ($tenant->profit_percentage ?? 0));

        tenancy()->initialize($tenant);
        // try {
        foreach ($sections as $section) {
            $summary[$section] = match ($section) {
                'languages' => $this->syncLanguages(),
                'currencies' => $this->syncCurrencies(),
                'settings' => $this->syncSettings(),
                'payment-gateways' => $this->syncPaymentGateways(),
                'themes' => $this->syncThemes(),
                'email-templates' => $this->syncEmailTemplates(),
                'pages' => $this->syncPages(),
                'categories' => $this->syncCategories($tenant->category_ids ?? []),
                'products' => $this->syncProducts($tenant->category_ids ?? []),
                'badges' => $this->syncBadges(),
                'flash-sales' => $this->syncFlashSales(),
                'coupons' => $this->syncCoupons(),
            };
        }
        // } finally {
        //     tenancy()->end();
        // }

        return $summary;
    }

    public function sections(): array
    {
        return self::SECTIONS;
    }

    public function syncSection(Tenant $tenant, string $section): int
    {
        set_time_limit(0);
        $this->profitPercentage = max(0, (float) ($tenant->profit_percentage ?? 0));
        tenancy()->initialize($tenant);

        return match ($section) {
            'languages' => $this->syncLanguages(),
            'currencies' => $this->syncCurrencies(),
            'settings' => $this->syncSettings(),
            'payment-gateways' => $this->syncPaymentGateways(),
            'themes' => $this->syncThemes(),
            'email-templates' => $this->syncEmailTemplates(),
            'pages' => $this->syncPages(),
            'categories' => $this->syncCategories($this->normalizeCategoryIds($tenant->category_ids)),
            'products' => $this->syncProducts($this->normalizeCategoryIds($tenant->category_ids)),
            'badges' => $this->syncBadges(),
            'flash-sales' => $this->syncFlashSales(),
            'coupons' => $this->syncCoupons(),
            default => throw new InvalidArgumentException("Unsupported sync section: {$section}"),
        };
    }

    /**
     * Legacy data may hold category_ids as a (possibly multiply) JSON-encoded
     * string instead of an array; unwrap it defensively before use.
     *
     * @return int[]
     */
    protected function normalizeCategoryIds(mixed $value): array
    {
        while (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            $value = $decoded;
        }

        return is_array($value) ? array_values(array_map('intval', $value)) : [];
    }

    public function normalizeSections(?array $sections): array
    {
        if (blank($sections)) {
            return self::SECTIONS;
        }

        $normalized = collect($sections)
            ->filter(fn($section) => filled($section))
            ->map(fn($section) => strtolower(trim((string) $section)))
            ->values()
            ->all();

        $invalid = array_values(array_diff($normalized, self::SECTIONS));

        if ($invalid !== []) {
            throw new InvalidArgumentException('Unsupported sync sections: ' . implode(', ', $invalid));
        }

        return array_values(array_unique($normalized));
    }

    protected function syncLanguages(): int
    {
        $currentTenant = tenant();

        // Fetch free languages from central
        $freeLanguages = tenancy()->central(
            fn() => CentralLanguage::query()->where('is_free', true)->get()
        );

        // Fetch IDs of languages this tenant has purchased
        $purchasedIds = $currentTenant
            ? tenancy()->central(fn() => TenantLanguagePurchase::query()
                ->where('tenant_id', $currentTenant->id)
                ->pluck('central_language_id')
                ->all())
            : [];

        // Fetch paid-but-purchased languages
        $purchasedLanguages = count($purchasedIds) > 0
            ? tenancy()->central(fn() => CentralLanguage::query()->whereIn('id', $purchasedIds)->get())
            : collect();

        $eligibleLanguages = $freeLanguages->concat($purchasedLanguages)->unique('id');

        foreach ($eligibleLanguages as $language) {
            \DB::transaction(function () use ($language) {
                // Claim any existing row with this code so updateOrCreate finds it by
                // central_language_id and does UPDATE rather than INSERT.
                Language::query()
                    ->where('code', $language->code)
                    ->where(fn($q) => $q->whereNull('central_language_id')->orWhere('central_language_id', '!=', $language->id))
                    ->lockForUpdate()
                    ->update(['central_language_id' => $language->id]);

                Language::query()->updateOrCreate(
                    ['central_language_id' => $language->id],
                    [
                        'code' => $language->code,
                        'name' => $language->name,
                        'native_name' => $language->native_name,
                        'direction' => $language->direction->value,
                        'is_active' => $language->is_active,
                        'is_default' => $language->is_default,
                        'sort_order' => $language->sort_order,
                    ]
                );
            });
        }

        // Delete languages that are no longer free AND not purchased by this tenant
        Language::query()
            ->whereNotNull('central_language_id')
            ->whereNotIn('central_language_id', $eligibleLanguages->pluck('id'))
            ->delete();

        return $eligibleLanguages->count();
    }

    protected function syncCurrencies(): int
    {
        $currencies = tenancy()->central(fn() => CentralCurrency::query()->orderBy('name')->get());
        $defaultCode = strtoupper((string) (tenancy()->central(fn() => CentralAppSetting::query()->where('key', 'default_currency')->value('value')) ?? 'USD'));

        foreach ($currencies as $currency) {
            Currency::query()->updateOrCreate(
                ['central_currency_id' => $currency->id],
                [
                    'code' => strtoupper((string) ($currency->code ?: $this->inferCurrencyCode($currency))),
                    'name' => $currency->name,
                    'symbol' => $currency->sign,
                    'conversion_rate' => $currency->conversion_rate,
                    'is_active' => true,
                    'is_default' => strtoupper((string) ($currency->code ?: $this->inferCurrencyCode($currency))) === $defaultCode,
                ]
            );
        }

        Currency::query()
            ->whereNotNull('central_currency_id')
            ->whereNotIn('central_currency_id', $currencies->pluck('id'))
            ->delete();

        return $currencies->count();
    }

    protected function syncSettings(): int
    {
        $settings = tenancy()->central(fn() => CentralAppSetting::query()
            ->where('key', 'not like', 'mail\_%')
            ->orderBy('key')
            ->get());
        $centralMailSettings = tenancy()->central(fn() => CentralAppSetting::query()
            ->where('key', 'like', 'mail\_%')
            ->pluck('value', 'key')
            ->all());
        $defaultLocale = $this->defaultLocale();

        foreach ($settings as $setting) {
            $tenantSetting = Setting::query()->updateOrCreate(
                ['name' => $setting->key],
                [
                    'value' => is_scalar($setting->value) || $setting->value === null
                        ? (string) $setting->value
                        : json_encode($setting->value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'type' => $this->mapSettingType($setting->type),
                    'group' => str_starts_with($setting->key, 'mail_') ? 'mail' : 'general',
                    'options' => null,
                ]
            );

            $tenantSetting->syncTranslations([
                $defaultLocale => ['title' => str($setting->key)->replace('_', ' ')->headline()->toString()],
            ]);
        }

        Setting::query()
            ->where('group', 'mail')
            ->get()
            ->each(function (Setting $setting) use ($centralMailSettings): void {
                if (!array_key_exists($setting->name, $centralMailSettings)) {
                    return;
                }

                if ((string) $setting->value === (string) $centralMailSettings[$setting->name]) {
                    $setting->delete();
                }
            });

        return $settings->count();
    }

    protected function syncPaymentGateways(): int
    {
        $gateways = tenancy()->central(fn() => CentralPaymentGateway::query()
            ->where('type', PaymentGatewayType::Orders->value)
            ->orderBy('name')->get());

        foreach ($gateways as $gateway) {
            $requiredKeys = array_values(array_unique(array_keys($gateway->credentials ?? [])));
            $isActive = $gateway->status === \App\Enums\ActivationStatus::Active;

            $tenantGateway = PaymentGateway::query()->firstOrNew([
                'central_payment_gateway_id' => $gateway->id,
            ]);

            $tenantGateway->fill([
                'name' => $gateway->name,
                'code' => $gateway->code,
                'is_active' => $isActive,
                'required_keys' => $requiredKeys,
            ]);

            if (!$tenantGateway->exists) {
                $tenantGateway->mode = $gateway->mode->value;
                $tenantGateway->use_own = false;
                $tenantGateway->hide = !$isActive;
            } else {
                $tenantGateway->hide = !$isActive;
            }

            $tenantGateway->required_values = $this->syncGatewayValues(
                $tenantGateway->required_values,
                $requiredKeys,
            );

            $tenantGateway->save();
        }

        PaymentGateway::query()
            ->whereNotNull('central_payment_gateway_id')
            ->whereNotIn('central_payment_gateway_id', $gateways->pluck('id'))
            ->delete();

        return $gateways->count();
    }

    protected function syncThemes(): int
    {
        $themes = tenancy()->central(fn() => CentralTemplate::query()
            ->with(['previewFile', 'countries', 'parts'])
            ->orderBy('name')
            ->get());

        foreach ($themes as $theme) {
            // A template with no country assignments means "all countries" ⇒ universal.
            $isUniversal = $theme->countries->isEmpty();

            $tenantTheme = Theme::query()->updateOrCreate(
                ['central_theme_id' => $theme->id],
                [
                    'name' => $theme->name,
                    'slug' => $theme->slug,
                    'is_active' => $theme->is_default,
                    'is_universal' => $isUniversal,
                    'preview_path' => $theme->previewFile?->full_path,
                ]
            );

            $this->syncThemeCountries($tenantTheme, $theme);
        }

        Theme::query()
            ->whereNotNull('central_theme_id')
            ->whereNotIn('central_theme_id', $themes->pluck('id'))
            ->delete();

        return $themes->count();
    }

    /**
     * Mirror the central template's allowed-country set onto the tenant theme.
     *
     * Central semantics:
     *   - Empty `template_country` pivot for a template means "all countries allowed".
     *   - Otherwise only the listed countries are allowed.
     *
     * Tenant pivot (`theme_country`) holds one row PER allowed country with an
     * `is_enabled` flag the tenant can toggle:
     *   - Newly allowed country    → insert row is_enabled=true
     *   - Country no longer allowed → delete row (tenant cannot enable it anymore)
     *   - Existing row             → keep `is_enabled` untouched, refresh snapshot fields
     */
    protected function syncThemeCountries(Theme $tenantTheme, CentralTemplate $centralTemplate): void
    {
        $assigned = $centralTemplate->countries; // eager-loaded

        // Empty assignment means "all countries" — resolve by fetching every central country.
        if ($assigned->isEmpty()) {
            $assigned = tenancy()->central(fn() => \App\Models\Country::query()
                ->orderBy('name')
                ->get(['id', 'iso2', 'name', 'flag_emoji']));
        }

        $allowedIds = $assigned->pluck('id')->map(fn($v) => (int) $v)->all();

        // Remove rows for countries that are no longer allowed.
        ThemeCountry::query()
            ->where('theme_id', $tenantTheme->id)
            ->whereNotIn('country_id', $allowedIds ?: [0])
            ->delete();

        $existingIds = ThemeCountry::query()
            ->where('theme_id', $tenantTheme->id)
            ->pluck('country_id')
            ->map(fn($v) => (int) $v)
            ->all();

        $now = now();
        $inserts = [];
        foreach ($assigned as $country) {
            $cid = (int) $country->id;
            if (in_array($cid, $existingIds, true)) {
                // Refresh denormalised snapshot fields without touching is_enabled.
                ThemeCountry::query()
                    ->where('theme_id', $tenantTheme->id)
                    ->where('country_id', $cid)
                    ->update([
                        'iso2' => $country->iso2,
                        'name' => $country->name,
                        'flag_emoji' => $country->flag_emoji,
                        'updated_at' => $now,
                    ]);
                continue;
            }

            $inserts[] = [
                'theme_id' => $tenantTheme->id,
                'country_id' => $cid,
                'iso2' => $country->iso2,
                'name' => $country->name,
                'flag_emoji' => $country->flag_emoji,
                'is_enabled' => true, // default: tenant has every allowed country enabled
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($inserts)) {
            ThemeCountry::query()->insert($inserts);
        }
    }

    protected function syncEmailTemplates(): int
    {
        $templates = tenancy()->central(fn() => CentralEmailTemplate::query()
            ->where('type', EmailTemplateType::Tenant->value)
            ->with('translations')
            ->orderBy('name')
            ->get());

        foreach ($templates as $template) {
            $tenantTemplate = EmailTemplate::query()
                ->where('central_email_template_id', $template->id)
                ->orWhere(function ($query) use ($template) {
                    $query->whereNull('central_email_template_id')
                        ->where('name', $template->name);
                })
                ->first() ?? new EmailTemplate();

            $tenantTemplate->fill([
                'central_email_template_id' => $template->id,
                'name' => $template->name,
                'action' => $template->action,
                'subject' => $template->subject,
                'body' => $template->body,
                'is_active' => $template->status === ActivationStatus::Active,
            ]);

            $tenantTemplate->save();

            // Sync per-language translations from central to tenant
            foreach ($template->translations as $centralTranslation) {
                EmailTemplateTranslation::query()->updateOrCreate(
                    ['email_template_id' => $tenantTemplate->id, 'locale' => $centralTranslation->locale],
                    ['subject' => $centralTranslation->subject, 'body' => $centralTranslation->body],
                );
            }

            // Remove tenant translations that no longer exist centrally
            $centralLocales = $template->translations->pluck('locale');
            if ($centralLocales->isNotEmpty()) {
                EmailTemplateTranslation::query()
                    ->where('email_template_id', $tenantTemplate->id)
                    ->whereNotIn('locale', $centralLocales)
                    ->delete();
            }
        }

        EmailTemplate::query()
            ->whereNotNull('central_email_template_id')
            ->whereNotIn('central_email_template_id', $templates->pluck('id'))
            ->delete();

        return $templates->count();
    }

    protected function syncPages(): int
    {
        $pages = tenancy()->central(fn() => CentralStaticPage::query()
            ->with('translations.language')
            ->orderBy('slug')
            ->get());

        foreach ($pages as $page) {
            $tenantPage = Page::query()->updateOrCreate(
                ['slug' => $page->slug],
                ['active' => in_array($page->status, [ContentStatus::Active, ContentStatus::Published], true)]
            );

            $locales = $page->translationsByLocale(['title', 'content']);
            $tenantTranslations = collect($locales)->mapWithKeys(fn($fields, $locale) => [
                $locale => [
                    'title' => $fields['title'] ?? '',
                    'body' => $fields['content'] ?? '',
                ],
            ])->all();

            $tenantPage->syncTranslations($tenantTranslations);
        }

        Page::query()
            ->whereNotIn('slug', $pages->pluck('slug'))
            ->delete();

        return $pages->count();
    }

    /**
     * Expand a list of root category IDs into all descendant IDs (including roots).
     * Must be called within a central DB context (tenancy()->central()).
     *
     * @param  int[]  $rootIds
     * @return int[]
     */
    protected function expandCategoryIds(array $rootIds): array
    {
        $allIds = $rootIds;
        $check = $rootIds;

        while (!empty($check)) {
            $children = CentralCategory::whereIn('parent_id', $check)
                ->pluck('id')
                ->toArray();

            $new = array_diff($children, $allIds);
            $allIds = array_merge($allIds, $new);
            $check = $new;
        }

        return $allIds;
    }

    /**
     * @param  int[]  $rootCategoryIds  Empty = sync all; non-empty = sync only
     *                                   these roots and their descendants.
     */
    protected function syncCategories(array $rootCategoryIds): int
    {
        $categories = tenancy()->central(function () use ($rootCategoryIds) {
            $categoryIds = !empty($rootCategoryIds)
                ? $this->expandCategoryIds($rootCategoryIds)
                : [];

            return CentralCategory::query()
                ->with(['translations.language', 'children.translations.language'])
                ->when(!empty($categoryIds), fn($q) => $q->whereIn('id', $categoryIds))
                ->orderBy('parent_id')
                ->get();
        });

        $map = [];

        foreach ($categories as $category) {
            /** @var CentralCategory $category */
            $tenantCategory = Category::query()->updateOrCreate(
                ['central_category_id' => $category->id],
                [
                    'parent_id' => $category->parent_id ? ($map[$category->parent_id] ?? null) : null,
                    'active' => $category->status->value === 'published',
                    'featured' => $category->is_featured,
                ]
            );

            if ($tenantCategory->wasRecentlyCreated) {
                $tenantCategory->update(['order_number' => $category->order_number]);
            }

            $tenantCategory->syncTranslations($this->mergeTranslatedFields(
                $tenantCategory,
                $category->translationsByLocale(['name', 'slug', 'description']),
                ['name', 'slug', 'description'],
                ['name', 'slug', 'description', 'meta_keywords', 'meta_description']
            ));
            $map[$category->id] = $tenantCategory->id;
        }

        Category::query()
            ->whereNotNull('central_category_id')
            ->whereNotIn('central_category_id', $categories->pluck('id'))
            ->delete();

        return $categories->count();
    }

    /**
     * @param  int[]  $rootCategoryIds  Empty = sync all products; non-empty = sync only
     *                                   products belonging to these categories or their descendants.
     */
    protected function syncProducts(array $rootCategoryIds): int
    {
        $currentTenantId = tenant()?->id;

        // Load active shipping costs from central once for the whole sync run.
        /** @var Collection<int, FixedShippingCost> $shippingCosts */
        $shippingCosts = tenancy()->central(
            fn() => FixedShippingCost::query()->where('is_active', true)->get()->keyBy('country_id')
        );

        // Products from selected categories (or all products when no restriction)
        $catalogProducts = tenancy()->central(function () use ($rootCategoryIds) {
            $categoryIds = !empty($rootCategoryIds)
                ? $this->expandCategoryIds($rootCategoryIds)
                : [];

            // withTrashed(): a soft-deleted central product must still flow through
            // this sync so its tenant row gets central_visible=false instead of
            // being purged by the cleanup step below, keeping the delete revertible.
            return CentralProduct::withTrashed()
                ->with(['translations.language', 'categories', 'variants.options.translations.language', 'variants.files', 'files'])
                ->when(!empty($categoryIds), fn($q) => $q->whereHas('categories', fn($query) =>
                    $query->whereIn('id', $categoryIds)))
                ->get();
        });

        // Products specifically assigned to this tenant
        $assignedProducts = $currentTenantId
            ? tenancy()->central(fn() => CentralProduct::withTrashed()
                ->with(['translations.language', 'categories', 'variants.options.translations.language', 'variants.files', 'files'])
                ->whereHas('tenantAssignments', fn($q) => $q->where('tenant_id', $currentTenantId))
                ->whereNotIn('id', $catalogProducts->pluck('id'))
                ->get())
            : collect();

        $products = $catalogProducts->merge($assignedProducts);
        $assignedProductIds = $assignedProducts->pluck('id')->flip();

        $variantIds = [];
        $newlyFlaggedProductIds = [];

        // Neozena covers AI translation for new products only when the tenant
        // has at least one active language it has already paid to AI-translate.
        $hasPaidTranslation = Language::query()
            ->where('is_active', true)
            ->where('translation_status', 'completed')
            ->whereNotNull('central_language_id')
            ->exists();

        // Resolved once for the whole run instead of per-product inside the loop below.
        $categoryIdMap = Category::query()
            ->whereNotNull('central_category_id')
            ->pluck('id', 'central_category_id');

        foreach ($products as $product) {
            /** @var CentralProduct $product */
            $tenantProduct = Product::withoutGlobalScope('centralVisible')->firstOrNew(['central_product_id' => $product->id]);
            $isNewProduct = !$tenantProduct->exists;
            $isTenantOwned = isset($assignedProductIds[$product->id]);

            $centralProductPrice = $product->sale_price ?: $product->base_price;
            $basePrice = $this->applyProfit($centralProductPrice);
            $countryPrices = $this->buildCountryPrices($basePrice, $product, $shippingCosts);

            $tenantProduct->fill([
                'slug' => $product->slug,
                'price' => $isNewProduct ? $countryPrices : $tenantProduct->price,
                'default_price' => $basePrice,
                'weight_grams' => $tenantProduct->weight_grams ?? $product->weight_grams,
                'active' => $isNewProduct ? ($product->status->value === 'published') : $tenantProduct->active,
                'central_visible' => $product->isVisibleToTenants(),
                'featured' => $isNewProduct ? false : $tenantProduct->featured,
                'is_tenant_owned' => $isTenantOwned,
                'order_number' => $isNewProduct ? $product->order_number : $tenantProduct->order_number,
            ]);
            $tenantProduct->save();

            // A tenant's admin-approved name/description must survive future syncs;
            // only meta_keywords (never approval-gated) and new locales still sync in.
            $tenantProduct->syncTranslations($this->mergeTranslatedFields(
                $tenantProduct,
                $tenantProduct->has_custom_translations ? [] : $product->translationsByLocale(['name', 'description']),
                $tenantProduct->has_custom_translations ? [] : ['name', 'description'],
                ['name', 'description', 'meta_keywords']
            ));
            $tenantProduct->categories()->sync(
                $product->categories->pluck('id')->map(fn($id) => $categoryIdMap[$id] ?? null)->filter()->values()->all()
            );

            if ($isNewProduct && $hasPaidTranslation) {
                $tenantProduct->update(['needs_ai_translation' => true]);
                $newlyFlaggedProductIds[] = $tenantProduct->id;
            }

            foreach ($product->variants as $variant) {
                $variantIds[] = $variant->id;

                $tenantVariant = ProductVariant::query()->firstOrNew([
                    'central_product_variant_id' => $variant->id,
                    'product_id' => $tenantProduct->id,
                ]);
                $isNewVariant = !$tenantVariant->exists;
                $centralVariantPrice = $variant->price ?? $product->sale_price ?? $product->base_price;
                $baseVariantPrice = $this->applyProfit($centralVariantPrice);
                $variantWeight = $variant->weight_grams !== null ? (int) $variant->weight_grams : (int) ($product->weight_grams ?? 0);
                $variantCountryPrices = $this->buildCountryPrices($baseVariantPrice, $product, $shippingCosts, $variantWeight);
                $centralThumb = $variant->relationLoaded('files')
                    ? $variant->files->firstWhere('key', 'variant_thumb')
                    : $variant->files()->where('key', 'variant_thumb')->first();

                $tenantVariant->fill([
                    'option_ids' => $variant->options->pluck('id')->map(fn($id) => (int) $id)->all(),
                    'real_price' => $centralVariantPrice,
                    'weight_grams' => $tenantVariant->weight_grams ?? $variant->weight_grams,
                    'sell_price' => $isNewVariant ? $variantCountryPrices : $tenantVariant->sell_price,
                    'default_sell_price' => $baseVariantPrice,
                    'stock' => /*$isNewVariant ? */ ($variant->stock ?? 0) ?? $tenantVariant->stock,
                    'margin_percentage' => $isNewVariant ? $this->profitPercentage : $tenantVariant->margin_percentage,
                    'thumbnail_path' => $tenantVariant->thumbnail_path
                        ?: ($centralThumb?->full_path ?? $product->primary_image_url),
                    'active' => $isNewVariant ? ($variant->status->value === 'active') : $tenantVariant->active,
                ]);
                $tenantVariant->save();
            }

            // Mark assignment as synced
            if ($currentTenantId) {
                tenancy()->central(fn() => ProductTenantAssignment::query()
                    ->where('product_id', $product->id)
                    ->where('tenant_id', $currentTenantId)
                    ->update(['synced_at' => now()]));
            }
        }

        ProductVariant::query()
            ->whereNotNull('central_product_variant_id')
            ->when($variantIds !== [], fn($query) => $query->whereNotIn('central_product_variant_id', $variantIds), fn($query) => $query)
            ->delete();

        // Only delete catalog products that are no longer in either the catalog or the tenant assignments
        $allProductIds = $products->pluck('id')->all();
        Product::withoutGlobalScope('centralVisible')
            ->whereNotNull('central_product_id')
            ->where('is_own_product', false)
            ->whereNotIn('central_product_id', $allProductIds)
            ->delete();

        if ($newlyFlaggedProductIds !== [] && $currentTenant = tenant()) {
            $this->notifyNewCatalogProducts($currentTenant, $newlyFlaggedProductIds);
        }

        return $products->count();
    }

    protected function notifyNewCatalogProducts(Tenant $tenant, array $productIds): void
    {
        $count = count($productIds);

        app(TenantNotificationService::class)->notify(
            $tenant,
            'new_catalog_products',
            __('New Products Available'),
            $count === 1
                ? __(':count new product has been added to your catalog — ready in your translated languages.', ['count' => $count])
                : __(':count new products have been added to your catalog — ready in your translated languages.', ['count' => $count]),
            ['product_ids' => $productIds],
        );
    }

    public function syncProductToTenant(CentralProduct $centralProduct, Tenant $tenant): void
    {
        $this->profitPercentage = max(0, (float) ($tenant->profit_percentage ?? 0));

        // Load active shipping costs from central.
        /** @var Collection<int, FixedShippingCost> $shippingCosts */
        $shippingCosts = tenancy()->central(
            fn() => FixedShippingCost::query()->where('is_active', true)->get()->keyBy('country_id')
        );

        tenancy()->initialize($tenant);

        $centralProduct->load(['translations.language', 'categories', 'variants.options.translations.language', 'variants.files', 'files', 'countries']);

        $tenantProduct = Product::withoutGlobalScope('centralVisible')->firstOrNew(['central_product_id' => $centralProduct->id]);
        $isNewProduct = !$tenantProduct->exists;

        $centralProductPrice = $centralProduct->sale_price ?: $centralProduct->base_price;
        $basePrice = $this->applyProfit($centralProductPrice);
        $countryPrices = $this->buildCountryPrices($basePrice, $centralProduct, $shippingCosts);

        $tenantProduct->fill([
            'slug' => $centralProduct->slug,
            'price' => $isNewProduct ? $countryPrices : $tenantProduct->price,
            'default_price' => $basePrice,
            'weight_grams' => $tenantProduct->weight_grams ?? $centralProduct->weight_grams,
            'active' => $isNewProduct ? ($centralProduct->status->value === 'published') : $tenantProduct->active,
            'central_visible' => $centralProduct->isVisibleToTenants(),
            // Denormalized from the central product_country pivot so the storefront
            // can rank by country without a cross-database join. Empty = no preference.
            'allowed_country_ids' => $centralProduct->countries->isEmpty()
                ? null
                : $centralProduct->countries->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'featured' => $isNewProduct ? false : $tenantProduct->featured,
            'is_tenant_owned' => true,
        ]);
        $tenantProduct->save();

        $tenantProduct->syncTranslations($this->mergeTranslatedFields(
            $tenantProduct,
            $tenantProduct->has_custom_translations ? [] : $centralProduct->translationsByLocale(['name', 'description']),
            $tenantProduct->has_custom_translations ? [] : ['name', 'description'],
            ['name', 'description', 'meta_keywords']
        ));
        $tenantProduct->categories()->sync(
            Category::query()->whereIn('central_category_id', $centralProduct->categories->pluck('id'))->pluck('id')->all()
        );

        if ($isNewProduct) {
            $hasPaidTranslation = Language::query()
                ->where('is_active', true)
                ->where('translation_status', 'completed')
                ->whereNotNull('central_language_id')
                ->exists();

            if ($hasPaidTranslation) {
                $tenantProduct->update(['needs_ai_translation' => true]);
                $this->notifyNewCatalogProducts($tenant, [$tenantProduct->id]);
            }
        }

        $existingVariants = ProductVariant::query()
            ->where('product_id', $tenantProduct->id)
            ->whereIn('central_product_variant_id', $centralProduct->variants->pluck('id'))
            ->get()
            ->keyBy('central_product_variant_id');

        foreach ($centralProduct->variants as $variant) {
            $tenantVariant = $existingVariants->get($variant->id) ?? new ProductVariant([
                'central_product_variant_id' => $variant->id,
                'product_id' => $tenantProduct->id,
            ]);
            $isNewVariant = !$tenantVariant->exists;
            $centralVariantPrice = $variant->price ?? $centralProduct->sale_price ?? $centralProduct->base_price;
            $baseVariantPrice = $this->applyProfit($centralVariantPrice);
            $variantWeight = $variant->weight_grams !== null ? (int) $variant->weight_grams : (int) ($centralProduct->weight_grams ?? 0);
            $variantCountryPrices = $this->buildCountryPrices($baseVariantPrice, $centralProduct, $shippingCosts, $variantWeight);
            $centralThumb = $variant->relationLoaded('files')
                ? $variant->files->firstWhere('key', 'variant_thumb')
                : $variant->files()->where('key', 'variant_thumb')->first();

            $tenantVariant->fill([
                'option_ids' => $variant->options->pluck('id')->map(fn($id) => (int) $id)->all(),
                'real_price' => $centralVariantPrice,
                'weight_grams' => $tenantVariant->weight_grams ?? $variant->weight_grams,
                'sell_price' => $isNewVariant ? $variantCountryPrices : $tenantVariant->sell_price,
                'default_sell_price' => $baseVariantPrice,
                'stock' => $variant->stock ?? 0,
                'margin_percentage' => $isNewVariant ? $this->profitPercentage : $tenantVariant->margin_percentage,
                'thumbnail_path' => $tenantVariant->thumbnail_path
                    ?: ($centralThumb?->full_path ?? $centralProduct->primary_image_url),
                'active' => $isNewVariant ? ($variant->status->value === 'active') : $tenantVariant->active,
            ]);
            $tenantVariant->save();
        }

        tenancy()->central(fn() => ProductTenantAssignment::query()
            ->where('product_id', $centralProduct->id)
            ->where('tenant_id', $tenant->id)
            ->update(['synced_at' => now()]));
    }

    /**
     * Build the per-country price JSON array for a product.
     *
     * Keys: "default" (base price without shipping), and one key per active country.
     * Value: base price + flat shipping cost for that country.
     *
     * Shipping resolution order:
     *   1. Product-level flat override (central product.fixed_shipping_costs[country_id])
     *   2. Weight-based cost (FixedShippingCost.price_per_gram × weight_grams)
     *   3. Zero (no shipping configured for this country)
     *
     * For variants, pass the variant's own weight_grams via $weightOverride so the
     * cost is computed from the variant's weight instead of the parent product's.
     */
    protected function buildCountryPrices(float $basePrice, CentralProduct $product, Collection $shippingCosts, ?int $weightOverride = null): array
    {
        $prices = ['default' => $basePrice];

        $fixedJson = is_array($product->fixed_shipping_costs) ? $product->fixed_shipping_costs : [];
        $weight = $weightOverride ?? (int) ($product->weight_grams ?? 0);

        foreach ($shippingCosts as $countryId => $record) {
            $key = (string) $countryId;
            $shipping = array_key_exists($key, $fixedJson)
                ? (float) $fixedJson[$key]
                : ($weight > 0 ? round((float) $record->price_per_gram * $weight, 2) : 0.0);
            $prices[$key] = round($basePrice + $shipping, 2);
        }

        return $prices;
    }

    protected function applyProfit(float|int|string|null $centralPrice): float
    {
        $price = max(0, (float) $centralPrice);

        if ($this->profitPercentage <= 0) {
            return $price;
        }

        return round($price * (1 + $this->profitPercentage / 100), 2);
    }

    protected function inferCurrencyCode($currency): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z]/', '', $currency->name) ?: 'CUR');

        return substr($normalized, 0, 6);
    }

    protected function syncGatewayValues(mixed $existingValues, array $requiredKeys): array
    {
        $existingValues = is_array($existingValues) ? $existingValues : [];

        $normalized = [];

        foreach ($requiredKeys as $key) {
            $normalized[$key] = (string) ($existingValues[$key] ?? '');
        }

        return $normalized;
    }

    protected function mergeTranslatedFields(object $tenantModel, array $incomingTranslations, array $managedFields, array $allTenantFields): array
    {
        $tenantModel->loadMissing('translations.language');

        $existingTranslations = method_exists($tenantModel, 'translationsByLocale')
            ? $tenantModel->translationsByLocale($allTenantFields)
            : [];

        foreach ($incomingTranslations as $locale => $fields) {
            foreach ($managedFields as $field) {
                $existingTranslations[$locale][$field] = $fields[$field] ?? '';
            }
        }

        return $existingTranslations;
    }

    protected function mapSettingType(AppSettingType $type): string
    {
        return match ($type) {
            AppSettingType::Boolean => SettingType::Boolean->value,
            AppSettingType::Integer => SettingType::Number->value,
            AppSettingType::Json => SettingType::Json->value,
            AppSettingType::Secret => SettingType::Password->value,
            AppSettingType::String => SettingType::String->value,
        };
    }

    protected function defaultLocale(): string
    {
        return tenancy()->central(fn() => CentralAppSetting::query()->where('key', 'default_language')->value('value')
            ?? CentralLanguage::query()->where('is_default', true)->value('code')
            ?? 'en');
    }

    protected function syncFlashSales(): int
    {
        $centralSales = tenancy()->central(
            fn() => CentralFlashSale::query()->with('products:id')->get()
        );

        // Map central_product_id => tenant product id
        $tenantProductIds = Product::withoutGlobalScope("centralVisible")
            ->whereNotNull('central_product_id')
            ->pluck('id', 'central_product_id');

        foreach ($centralSales as $centralSale) {
            /** @var CentralFlashSale $centralSale */
            $tenantProductIdList = collect($centralSale->products)
                ->pluck('id')
                ->map(fn($centralProductId) => $tenantProductIds[$centralProductId] ?? null)
                ->filter()
                ->values()
                ->all();

            if (empty($tenantProductIdList)) {
                continue;
            }

            $tenantSale = FlashSale::query()->updateOrCreate(
                ['central_flash_sale_id' => $centralSale->id],
                [
                    'product_id' => $tenantProductIdList[0],
                    'discount_percentage' => $centralSale->discount_percentage,
                    'start_date' => $centralSale->start_date,
                    'end_date' => $centralSale->end_date,
                    'active' => $centralSale->active,
                    'banner_image' => $centralSale->getBannerUrlAttribute(),
                    'country_id' => $centralSale->country_id,
                ]
            );

            $tenantSale->products()->sync($tenantProductIdList);
        }

        // Remove tenant flash sales whose central counterpart was deleted
        FlashSale::query()
            ->whereNotNull('central_flash_sale_id')
            ->whereNotIn('central_flash_sale_id', $centralSales->pluck('id'))
            ->delete();

        return $centralSales->count();
    }

    protected function syncBadges(): int
    {
        $centralBadges = tenancy()->central(
            fn() => CentralProductBadge::query()->get()
        );

        $centralPivotRows = tenancy()->central(
            fn() => \DB::table('product_badge_product')
                ->whereIn('product_badge_id', $centralBadges->pluck('id'))
                ->get()
        );

        $tenantProductIds = Product::withoutGlobalScope("centralVisible")
            ->whereNotNull('central_product_id')
            ->pluck('id', 'central_product_id');

        foreach ($centralBadges as $centralBadge) {
            $tenantBadge = ProductBadge::query()->updateOrCreate(
                ['text' => $centralBadge->text],
                ['active' => $centralBadge->active]
            );

            $rowsForBadge = $centralPivotRows->where('product_badge_id', $centralBadge->id);
            $countryIds = $rowsForBadge->pluck('country_id')->unique()->all();

            foreach ($countryIds as $countryId) {
                $rowsForCountry = $rowsForBadge->filter(fn($r) => $r->country_id === $countryId);

                // Preserve the tenant's own manual reordering (set via SortBadgeProducts)
                // across re-syncs — only newly-attached products get a fresh order,
                // appended after whatever the tenant already arranged.
                $existingOrder = \DB::table('product_badge_product')
                    ->where('product_badge_id', $tenantBadge->id)
                    ->when($countryId === null, fn($q) => $q->whereNull('country_id'), fn($q) => $q->where('country_id', $countryId))
                    ->pluck('sort_order', 'product_id');

                $nextOrder = $existingOrder->isEmpty() ? 0 : ($existingOrder->max() + 1);

                \DB::table('product_badge_product')
                    ->where('product_badge_id', $tenantBadge->id)
                    ->when($countryId === null, fn($q) => $q->whereNull('country_id'), fn($q) => $q->where('country_id', $countryId))
                    ->delete();

                $now = now();
                foreach ($rowsForCountry as $centralRow) {
                    $tenantProductId = $tenantProductIds[$centralRow->product_id] ?? null;
                    if (!$tenantProductId) {
                        continue;
                    }

                    \DB::table('product_badge_product')->insert([
                        'product_badge_id' => $tenantBadge->id,
                        'product_id'       => $tenantProductId,
                        'country_id'       => $countryId,
                        'sort_order'       => $existingOrder[$tenantProductId] ?? $nextOrder++,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }
            }
        }

        ProductBadge::query()
            ->whereNotIn('text', $centralBadges->pluck('text'))
            ->delete();

        return $centralBadges->count();
    }

    protected function syncCoupons(): int
    {
        $centralCoupons = tenancy()->central(
            fn() => CentralCoupon::query()->get()
        );

        // Only sync Default coupons (country_id null) plus coupons scoped to a
        // country this tenant actually serves — a tenant should never receive a
        // coupon targeting a country it doesn't sell in.
        $tenantCountryIds = TenantCountry::query()
            ->where('tenant_id', tenant()->id)
            ->where('is_active', true)
            ->pluck('country_id')
            ->all();

        $applicableCoupons = $centralCoupons->filter(
            fn(CentralCoupon $coupon) => $coupon->country_id === null || in_array($coupon->country_id, $tenantCountryIds, true)
        );

        foreach ($applicableCoupons as $centralCoupon) {
            Coupon::query()->updateOrCreate(
                ['central_coupon_id' => $centralCoupon->id],
                [
                    'code' => $centralCoupon->code,
                    'type' => $centralCoupon->type->value,
                    'value' => $centralCoupon->value,
                    'minimum_spend' => $centralCoupon->minimum_spend,
                    'start_date' => $centralCoupon->start_date,
                    'end_date' => $centralCoupon->end_date,
                    'country_id' => $centralCoupon->country_id,
                ]
            );
        }

        // Remove tenant coupons whose central counterpart was deleted or is no
        // longer applicable to this tenant (e.g. tenant stopped serving that country).
        Coupon::query()
            ->whereNotNull('central_coupon_id')
            ->whereNotIn('central_coupon_id', $applicableCoupons->pluck('id'))
            ->delete();

        return $applicableCoupons->count();
    }
}
