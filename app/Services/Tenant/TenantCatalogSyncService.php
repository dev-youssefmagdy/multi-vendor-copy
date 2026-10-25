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
use App\Models\Tenant\Translation;
use App\Models\Template as CentralTemplate;
use App\Support\CacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    protected const PRODUCT_CHUNK = 100;

    protected float $profitPercentage = 0;

    protected ?TenantPricingService $pricing = null;

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

    /**
     * Run a read/write against the central database while tenancy stays initialized.
     *
     * `tenancy()->central()` ends and re-initializes tenancy, which purges the tenant connection: a reconnect and a
     * database-exists check per call, and any open tenant transaction is silently abandoned. Central models resolve
     * the default connection, so swapping it for the duration of the callback is all that is needed.
     */
    protected function central(callable $callback): mixed
    {
        $tenantDefault = DB::getDefaultConnection();
        $central = config('tenancy.database.central_connection');

        if ($tenantDefault === $central) {
            return $callback();
        }

        DB::setDefaultConnection($central);

        try {
            return $callback();
        } finally {
            DB::setDefaultConnection($tenantDefault);
        }
    }

    public function syncSection(Tenant $tenant, string $section): int
    {
        set_time_limit(0);
        $this->profitPercentage = max(0, (float) ($tenant->profit_percentage ?? 0));
        tenancy()->initialize($tenant);

        return $this->runSection($tenant, $section);
    }

    /**
     * Sync several (small) sections inside one tenant transaction: a single commit/fsync instead of one per
     * statement, and a failed run leaves nothing half-written. Not for `products` — that one is chunked on purpose.
     *
     * @param  string[]  $sections
     * @return array<string, int>
     */
    public function syncSectionsAtomically(Tenant $tenant, array $sections): array
    {
        set_time_limit(0);
        $this->profitPercentage = max(0, (float) ($tenant->profit_percentage ?? 0));
        tenancy()->initialize($tenant);

        return DB::connection('tenant')->transaction(function () use ($tenant, $sections) {
            $summary = [];

            foreach ($sections as $section) {
                $summary[$section] = $this->runSection($tenant, $section);
            }

            return $summary;
        });
    }

    protected function runSection(Tenant $tenant, string $section): int
    {
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
        $freeLanguages = $this->central(
            fn() => CentralLanguage::query()->where('is_free', true)->get()
        );

        // Fetch IDs of languages this tenant has purchased
        $purchasedIds = $currentTenant
            ? $this->central(fn() => TenantLanguagePurchase::query()
                ->where('tenant_id', $currentTenant->id)
                ->pluck('central_language_id')
                ->all())
            : [];

        // Fetch paid-but-purchased languages
        $purchasedLanguages = count($purchasedIds) > 0
            ? $this->central(fn() => CentralLanguage::query()->whereIn('id', $purchasedIds)->get())
            : collect();

        $eligibleLanguages = $freeLanguages->concat($purchasedLanguages)->unique('id');

        // One lookup + one upsert. A tenant row is matched by its central id first, else it claims the row
        // already using the same code (so it is UPDATEd rather than INSERTed); `is_default` is insert-only.
        $existing = Language::query()->toBase()->orderBy('id')->get(['id', 'code', 'central_language_id']);
        $byCentralId = $existing->whereNotNull('central_language_id')->pluck('id', 'central_language_id');
        $byCode = $existing->pluck('id', 'code');
        $now = now();

        $rows = $eligibleLanguages->map(fn ($language) => [
            'id' => $byCentralId->get($language->id) ?? $byCode->get($language->code),
            'central_language_id' => $language->id,
            'code' => $language->code,
            'name' => $language->name,
            'native_name' => $language->native_name,
            'direction' => $language->direction->value,
            'is_active' => $language->is_active,
            'is_default' => $language->is_default,
            'sort_order' => $language->sort_order,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            Language::query()->toBase()->upsert(
                $chunk,
                ['id'],
                ['central_language_id', 'code', 'name', 'native_name', 'direction', 'is_active', 'sort_order', 'updated_at']
            );
        }

        // Delete languages that are no longer free AND not purchased by this tenant
        Language::query()
            ->whereNotNull('central_language_id')
            ->whereNotIn('central_language_id', $eligibleLanguages->pluck('id'))
            ->delete();

        // Bulk writes skip model events (Language's hooks keep the default-language cache key); do it once here.
        if ($defaultCode = Language::query()->where('is_default', true)->value('code')) {
            Cache::driver('file')->forever('default_language_code_' . tenant('id'), $defaultCode);
        }
        CacheVersion::bump('Language');

        return $eligibleLanguages->count();
    }

    protected function syncCurrencies(): int
    {
        $currencies = $this->central(fn() => CentralCurrency::query()->orderBy('name')->get());
        $defaultCode = strtoupper((string) ($this->central(fn() => CentralAppSetting::query()->where('key', 'default_currency')->value('value')) ?? 'USD'));

        // Single bulk upsert: existing rows are matched by their tenant id (looked up by central id), new rows get id NULL.
        $existingIds = Currency::query()
            ->whereNotNull('central_currency_id')
            ->pluck('id', 'central_currency_id');
        $now = now();

        $rows = $currencies->map(function ($currency) use ($existingIds, $defaultCode, $now) {
            $code = strtoupper((string) ($currency->code ?: $this->inferCurrencyCode($currency)));

            return [
                'id' => $existingIds->get($currency->id),
                'central_currency_id' => $currency->id,
                'code' => $code,
                'name' => $currency->name,
                'symbol' => $currency->sign,
                'conversion_rate' => $currency->conversion_rate,
                'is_active' => true,
                'is_default' => $code === $defaultCode,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            Currency::query()->upsert(
                $chunk,
                ['id'],
                ['central_currency_id', 'code', 'name', 'symbol', 'conversion_rate', 'is_active', 'is_default', 'updated_at']
            );
        }

        Currency::query()
            ->whereNotNull('central_currency_id')
            ->whereNotIn('central_currency_id', $currencies->pluck('id'))
            ->delete();

        // Bulk writes skip model events; bump the storefront cache version once instead.
        CacheVersion::bump('Currency');

        return $currencies->count();
    }

    protected function syncSettings(): int
    {
        $settings = $this->central(fn() => CentralAppSetting::query()
            ->where('key', 'not like', 'mail\_%')
            ->orderBy('key')
            ->get());
        $centralMailSettings = $this->central(fn() => CentralAppSetting::query()
            ->where('key', 'like', 'mail\_%')
            ->pluck('value', 'key')
            ->all());
        $defaultLocale = $this->defaultLocale();
        $now = now();

        // `value` is only seeded on insert — the upsert never overwrites a tenant-customised value.
        $rows = $settings->map(fn ($setting) => [
            'name' => $setting->key,
            'value' => is_scalar($setting->value) || $setting->value === null
                ? (string) $setting->value
                : json_encode($setting->value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'type' => $this->mapSettingType($setting->type),
            'group' => str_starts_with($setting->key, 'mail_') ? 'mail' : 'general',
            'options' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            Setting::query()->upsert($rows, ['name'], ['type', 'group', 'options', 'updated_at']);

            $settingIds = Setting::query()->whereIn('name', array_column($rows, 'name'))->pluck('id', 'name');

            // Same result as syncTranslations() per setting (its translations replaced by one title row),
            // done as one delete + one insert.
            Translation::query()
                ->where('translatable_type', Setting::class)
                ->whereIn('translatable_id', $settingIds->values())
                ->delete();

            $languageId = Language::query()->where('code', $defaultLocale)->value('id');

            if ($languageId) {
                Translation::query()->insert($settings->map(fn ($setting) => [
                    'language_id' => $languageId,
                    'translatable_type' => Setting::class,
                    'translatable_id' => $settingIds[$setting->key],
                    'field' => 'title',
                    'value' => str($setting->key)->replace('_', ' ')->headline()->toString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }
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

        CacheVersion::bump('Setting');

        return $settings->count();
    }

    protected function syncPaymentGateways(): int
    {
        $gateways = $this->central(fn() => CentralPaymentGateway::query()
            ->where('type', PaymentGatewayType::Orders->value)
            ->orderBy('name')->get());

        $existing = PaymentGateway::query()
            ->whereIn('central_payment_gateway_id', $gateways->pluck('id'))
            ->get()
            ->keyBy('central_payment_gateway_id');
        $now = now();

        // One upsert for every gateway. Rows are serialised through the model so casts (json keys, encrypted
        // values, enums) match a normal save; `mode`/`use_own` only matter on insert — the update list below
        // leaves a tenant's own settings untouched.
        $rows = $gateways->map(function ($gateway) use ($existing, $now) {
            $requiredKeys = array_values(array_unique(array_keys($gateway->credentials ?? [])));
            $isActive = $gateway->status === ActivationStatus::Active;
            $current = $existing->get($gateway->id);

            $row = new PaymentGateway([
                'name' => $gateway->name,
                'code' => $gateway->code,
                'is_active' => $isActive,
                'required_keys' => $requiredKeys,
                'hide' => !$isActive,
                'mode' => $gateway->mode->value,
                'use_own' => false,
            ]);
            $row->required_values = $this->syncGatewayValues($current?->required_values, $requiredKeys);

            return $row->getAttributes() + [
                'id' => $current?->id,
                'central_payment_gateway_id' => $gateway->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            PaymentGateway::query()->toBase()->upsert(
                $chunk,
                ['id'],
                ['central_payment_gateway_id', 'name', 'code', 'is_active', 'hide', 'required_keys', 'required_values', 'updated_at']
            );
        }

        PaymentGateway::query()
            ->whereNotNull('central_payment_gateway_id')
            ->whereNotIn('central_payment_gateway_id', $gateways->pluck('id'))
            ->delete();

        return $gateways->count();
    }

    protected function syncThemes(): int
    {
        $themes = $this->central(fn() => CentralTemplate::query()
            ->with(['previewFile', 'countries.translations.language', 'parts'])
            ->orderBy('name')
            ->get());

        $existingIds = Theme::query()->toBase()
            ->whereIn('central_theme_id', $themes->pluck('id'))
            ->orderBy('id')
            ->pluck('id', 'central_theme_id');

        // "All countries" is the same list for every universal template — fetch it once.
        $allCountries = $themes->contains(fn ($theme) => $theme->countries->isEmpty())
            ? $this->central(fn() => \App\Models\Country::query()
                ->with('translations.language') // `name` is translated — avoid 2 lazy queries per country
                ->orderBy('name')
                ->get(['id', 'iso2', 'name', 'flag_emoji']))
            : collect();
        $now = now();

        // `is_active` is only seeded on insert — the update list never overwrites the tenant's choice.
        // A template with no country assignments means "all countries" ⇒ universal.
        $rows = $themes->map(fn ($theme) => [
            'id' => $existingIds->get($theme->id),
            'central_theme_id' => $theme->id,
            'name' => $theme->name,
            'slug' => $theme->slug,
            'is_universal' => $theme->countries->isEmpty(),
            'is_active' => false,
            'preview_path' => $theme->previewFile?->full_path,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            Theme::query()->toBase()->upsert(
                $chunk,
                ['id'],
                ['central_theme_id', 'name', 'slug', 'is_universal', 'preview_path', 'updated_at']
            );
        }

        $tenantThemeIds = Theme::query()->toBase()
            ->whereIn('central_theme_id', $themes->pluck('id'))
            ->orderBy('id')
            ->pluck('id', 'central_theme_id');

        $this->syncThemeCountries($themes->mapWithKeys(fn ($theme) => [
            (int) $tenantThemeIds[$theme->id] => $theme->countries->isEmpty() ? $allCountries : $theme->countries,
        ]));

        Theme::query()
            ->whereNotNull('central_theme_id')
            ->whereNotIn('central_theme_id', $themes->pluck('id'))
            ->delete();

        // Bulk writes skip model events; bump the storefront cache version once
        // instead — after the stale delete, so it isn't cached under the new version.
        CacheVersion::bump('Theme');

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
    protected function syncThemeCountries(Collection $assignedByTheme): void
    {
        $now = now();
        $rows = [];
        $allowed = [];

        foreach ($assignedByTheme as $themeId => $assigned) {
            foreach ($assigned as $country) {
                $allowed[$themeId][(int) $country->id] = true;
                $rows[] = [
                    'theme_id' => $themeId,
                    'country_id' => (int) $country->id,
                    'iso2' => $country->iso2,
                    'name' => $country->name,
                    'flag_emoji' => $country->flag_emoji,
                    'is_enabled' => true, // default: tenant has every allowed country enabled
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Remove rows for countries that are no longer allowed (one read, one delete for all themes).
        $stale = ThemeCountry::query()->toBase()
            ->whereIn('theme_id', $assignedByTheme->keys())
            ->get(['id', 'theme_id', 'country_id'])
            ->filter(fn ($row) => !isset($allowed[$row->theme_id][(int) $row->country_id]))
            ->pluck('id');

        foreach ($stale->chunk(500) as $ids) {
            ThemeCountry::query()->whereKey($ids->all())->delete();
        }

        // One upsert on the (theme_id, country_id) unique key: new countries are inserted enabled,
        // existing rows only get their snapshot fields refreshed (is_enabled is left untouched).
        foreach (array_chunk($rows, 500) as $chunk) {
            ThemeCountry::query()->upsert($chunk, ['theme_id', 'country_id'], ['iso2', 'name', 'flag_emoji', 'updated_at']);
        }
    }

    protected function syncEmailTemplates(): int
    {
        $templates = $this->central(fn() => CentralEmailTemplate::query()
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
        [$pages, $centralCodes] = $this->central(fn() => [
            CentralStaticPage::query()->with('translations')->orderBy('slug')->get(),
            $this->centralLanguageCodes(),
        ]);

        $now = now();

        Page::query()->toBase()->upsert(
            $pages->map(fn($page) => [
                'slug' => $page->slug,
                'active' => in_array($page->status, [ContentStatus::Active, ContentStatus::Published], true),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['slug'],
            ['active', 'updated_at'],
        );

        $pageIds = Page::query()->toBase()->pluck('id', 'slug')->all();
        [$codeToId] = $this->tenantLanguageMaps();
        $desired = [];

        foreach ($pages as $page) {
            $incoming = [];
            foreach ($this->centralLocaleFields($page, ['title', 'content'], $centralCodes) as $locale => $fields) {
                $incoming[$locale] = ['title' => $fields['title'], 'body' => $fields['content']];
            }

            $desired[$pageIds[$page->slug]] = $this->desiredTranslations([], $incoming, ['title', 'body'], ['title', 'body'], $codeToId, []);
        }

        (new BulkTranslationWriter())->replace((new Page())->getMorphClass(), $desired);

        Page::query()
            ->whereNotIn('slug', $pages->pluck('slug'))
            ->delete();

        return $pages->count();
    }

    /**
     * Expand a list of root category IDs into all descendant IDs (including roots).
     * Must be called within a central DB context ($this->central()).
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
        [$categories, $centralCodes] = $this->central(function () use ($rootCategoryIds) {
            $categoryIds = !empty($rootCategoryIds)
                ? $this->expandCategoryIds($rootCategoryIds)
                : [];

            return [
                CentralCategory::query()
                    ->with('translations')
                    ->when(!empty($categoryIds), fn($q) => $q->whereIn('id', $categoryIds))
                    ->orderBy('id')
                    ->get(),
                $this->centralLanguageCodes(),
            ];
        });

        $now = now();
        $fields = ['name', 'slug', 'description', 'meta_keywords', 'meta_description'];
        $existing = $this->tenantRowsByCentralId(Category::query(), 'central_category_id', ['id', 'central_category_id', 'parent_id', 'active', 'featured']);

        // Pass 1 – create the missing rows so every parent has a tenant id before parent_id is written
        // (the old loop relied on `ORDER BY parent_id`, which breaks for ids that are not top-down).
        $missing = [];
        foreach ($categories as $category) {
            if (! isset($existing[$category->id])) {
                $missing[] = [
                    'central_category_id' => $category->id,
                    'parent_id' => null,
                    'active' => $category->status->value === 'published',
                    'featured' => (bool) $category->is_featured,
                    'order_number' => $category->order_number,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($missing, 500) as $chunk) {
            Category::query()->toBase()->insert($chunk);
        }
        $created = array_column($missing, 'central_category_id', 'central_category_id');

        $allRows = Category::query()->toBase()->whereNotNull('central_category_id')->orderBy('id')->get(['id', 'central_category_id']);
        $tenantIds = [];
        foreach ($allRows as $row) {
            $tenantIds[(int) $row->central_category_id] ??= (int) $row->id;
        }

        // Pass 2 – parent_id / active / featured for rows that are new or changed.
        $changed = [];
        foreach ($categories as $category) {
            $row = $existing[$category->id] ?? null;
            $parentId = $category->parent_id ? ($tenantIds[$category->parent_id] ?? null) : null;
            $active = $category->status->value === 'published';
            $featured = (bool) $category->is_featured;

            if ($row === null || (int) $row->parent_id !== (int) $parentId || (bool) $row->active !== $active || (bool) $row->featured !== $featured) {
                $changed[] = [
                    'id' => $tenantIds[$category->id],
                    'central_category_id' => $category->id,
                    'parent_id' => $parentId,
                    'active' => $active,
                    'featured' => $featured,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($changed, 500) as $chunk) {
            Category::query()->toBase()->upsert($chunk, ['id'], ['parent_id', 'active', 'featured', 'updated_at']);
        }

        [$codeToId, $activeIds] = $this->tenantLanguageMaps();
        $writer = new BulkTranslationWriter();
        $type = (new Category())->getMorphClass();
        $existingTranslations = $writer->existing($type, array_values($tenantIds));
        $desired = [];

        foreach ($categories as $category) {
            $tenantId = $tenantIds[$category->id];
            $desired[$tenantId] = $this->desiredTranslations(
                $existingTranslations[$tenantId] ?? [],
                $this->centralLocaleFields($category, $fields, $centralCodes),
                $fields,
                $fields,
                $codeToId,
                $activeIds,
            );
        }
        $writer->replace($type, $desired, $existingTranslations);

        $keep = array_flip($categories->pluck('id')->all());
        $stale = $allRows->filter(fn($row) => ! isset($keep[(int) $row->central_category_id]))->pluck('id')->all();
        foreach (array_chunk($stale, 1000) as $ids) {
            Category::query()->whereIn('id', $ids)->delete();
        }

        // Bulk writes skip model events; bump the storefront cache version once instead.
        CacheVersion::bump('Category');

        return $categories->count();
    }

    /**
     * Products are processed in small chunks so memory stays flat no matter how large
     * the catalog is, and every chunk is written with a handful of multi-row statements
     * (insert / upsert / delete) instead of several queries per product and variant.
     *
     * @param  int[]  $rootCategoryIds  Empty = sync all products; non-empty = sync only
     *                                   products belonging to these categories or their descendants.
     */
    protected function syncProducts(array $rootCategoryIds): int
    {
        $currentTenantId = tenant()?->id;
        $this->pricing = app(TenantPricingService::class);

        [$catalogIds, $assignedIds, $shippingCosts, $centralCodes] = $this->central(function () use ($rootCategoryIds, $currentTenantId) {
            $categoryIds = !empty($rootCategoryIds)
                ? $this->expandCategoryIds($rootCategoryIds)
                : [];

            // withTrashed(): a soft-deleted central product must still flow through
            // this sync so its tenant row gets central_visible=false instead of
            // being purged by the cleanup step below, keeping the delete revertible.
            $catalogIds = CentralProduct::withTrashed()
                ->when(!empty($categoryIds), fn($q) => $q->whereHas('categories', fn($query) =>
                    $query->whereIn('id', $categoryIds)))
                ->orderBy('id')
                ->pluck('id')
                ->all();

            // Products specifically assigned to this tenant
            $assignedIds = $currentTenantId
                ? CentralProduct::withTrashed()
                    ->whereHas('tenantAssignments', fn($q) => $q->where('tenant_id', $currentTenantId))
                    ->whereNotIn('id', $catalogIds)
                    ->orderBy('id')
                    ->pluck('id')
                    ->all()
                : [];

            return [
                $catalogIds,
                $assignedIds,
                // Active shipping costs from central, loaded once for the whole run.
                FixedShippingCost::query()->where('is_active', true)->get()->keyBy('country_id'),
                $this->centralLanguageCodes(),
            ];
        });

        $assignedFlip = array_flip($assignedIds);
        $allIds = array_merge($catalogIds, $assignedIds);

        $ctx = (object) [
            'shippingCosts' => $shippingCosts,
            'centralCodes' => $centralCodes,
            'assigned' => $assignedFlip,
            'currentTenantId' => $currentTenantId,
            'seenVariants' => [],
            'flagged' => [],
            // Neozena covers AI translation for new products only when the tenant
            // has at least one active language it has already paid to AI-translate.
            'hasPaidTranslation' => Language::query()
                ->where('is_active', true)
                ->where('translation_status', 'completed')
                ->whereNotNull('central_language_id')
                ->exists(),
            // Resolved once for the whole run instead of per product.
            'categoryIdMap' => Category::query()->toBase()
                ->whereNotNull('central_category_id')
                ->pluck('id', 'central_category_id')
                ->all(),
            'writer' => new BulkTranslationWriter(),
        ];
        [$ctx->codeToId, $ctx->activeIds] = $this->tenantLanguageMaps();

        foreach (array_chunk($allIds, self::PRODUCT_CHUNK) as $ids) {
            $products = $this->central(fn() => $this->loadCentralProducts($ids));
            $categoryPivot = $this->central(fn() => \DB::table('category_product')
                ->whereIn('product_id', $ids)
                ->get(['product_id', 'category_id'])
                ->groupBy('product_id')
                ->map(fn($rows) => $rows->pluck('category_id')->all())
                ->all());

            \DB::transaction(fn() => $this->syncProductChunk($products, $categoryPivot, $ctx));

            unset($products, $categoryPivot);
        }

        if ($currentTenantId && $allIds !== []) {
            $this->central(function () use ($allIds, $currentTenantId) {
                foreach (array_chunk($allIds, 1000) as $ids) {
                    ProductTenantAssignment::query()
                        ->whereIn('product_id', $ids)
                        ->where('tenant_id', $currentTenantId)
                        ->update(['synced_at' => now()]);
                }
            });
        }

        $this->deleteStaleCatalogRows($ctx->seenVariants, array_flip($allIds));

        // Bulk writes skip model events; bump the storefront cache versions once instead
        // (Category too: category_product pivot rows and per-category counts change).
        CacheVersion::bumpMany(['Product', 'ProductVariant', 'Category']);

        if ($ctx->flagged !== [] && $currentTenant = tenant()) {
            $this->notifyNewCatalogProducts($currentTenant, $ctx->flagged);
        }

        return count($allIds);
    }

    /**
     * Only the relations the sync reads are loaded (no language/option translations,
     * no unused files), constrained to the columns' needed keys.
     *
     * @param  int[]  $ids
     * @return Collection<int, CentralProduct>
     */
    protected function loadCentralProducts(array $ids): Collection
    {
        return CentralProduct::withTrashed()
            ->with([
                'translations',
                'files' => fn($q) => $q->whereIn('key', ['primary_medium', 'primary_original', 'gallery']),
                'variants.translations',
                'variants.options',
                'variants.files' => fn($q) => $q->where('key', 'variant_thumb'),
            ])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, CentralProduct>  $products
     * @param  array<int, int[]>  $categoryPivot  central product id => central category ids
     */
    protected function syncProductChunk(Collection $products, array $categoryPivot, object $ctx): void
    {
        $now = now();
        $productIds = $products->pluck('id')->all();
        $existing = $this->tenantRowsByCentralId(
            Product::withoutGlobalScope('centralVisible'),
            'central_product_id',
            ['id', 'central_product_id', 'slug', 'price', 'default_price', 'profit', 'fixed_shipping_costs', 'weight_grams', 'central_visible', 'is_tenant_owned', 'has_custom_translations'],
            $productIds,
        );

        $inserts = [];
        $updates = [];
        $costsByProduct = [];

        foreach ($products as $product) {
            /** @var CentralProduct $product */
            $row = $existing[$product->id] ?? null;
            $costs = $this->centralShippingCosts($product, $ctx->shippingCosts);
            $costsByProduct[$product->id] = $costs;
            $pricing = $this->catalogPricing(
                $this->pricing->centralBasePrice($product),
                $costs,
                $row ? $this->jsonColumn($row->profit) : null,
            );
            $weight = $row ? ($row->weight_grams ?? $product->weight_grams) : $product->weight_grams;
            $visible = $product->isVisibleToTenants();
            $tenantOwned = isset($ctx->assigned[$product->id]);

            if ($row === null) {
                $inserts[] = [
                    'central_product_id' => $product->id,
                    'slug' => $product->slug,
                    'price' => json_encode($pricing['prices']),
                    'default_price' => $pricing['default'],
                    'profit' => json_encode($pricing['profit']),
                    'fixed_shipping_costs' => $costs === null ? null : json_encode($costs),
                    'weight_grams' => $weight,
                    'active' => $product->status->value === 'published',
                    'central_visible' => $visible,
                    'featured' => false,
                    'is_tenant_owned' => $tenantOwned,
                    'order_number' => $product->order_number,
                    'needs_ai_translation' => $ctx->hasPaidTranslation,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                continue;
            }

            if (
                $row->slug !== $product->slug
                || $this->jsonColumn($row->price) != $pricing['prices']
                || round((float) $row->default_price, 2) !== round((float) $pricing['default'], 2)
                || $this->jsonColumn($row->profit) != $pricing['profit']
                || $this->jsonColumn($row->fixed_shipping_costs) != $costs
                || ($row->weight_grams === null ? null : (int) $row->weight_grams) !== ($weight === null ? null : (int) $weight)
                || (bool) $row->central_visible !== $visible
                || (bool) $row->is_tenant_owned !== $tenantOwned
            ) {
                $updates[] = [
                    'id' => $row->id,
                    'central_product_id' => $product->id,
                    'slug' => $product->slug,
                    'price' => json_encode($pricing['prices']),
                    'default_price' => $pricing['default'],
                    'profit' => json_encode($pricing['profit']),
                    'fixed_shipping_costs' => $costs === null ? null : json_encode($costs),
                    'weight_grams' => $weight,
                    'central_visible' => $visible,
                    'is_tenant_owned' => $tenantOwned,
                    'updated_at' => $now,
                ];
            }
        }

        $newCentralIds = array_column($inserts, 'central_product_id');

        foreach (array_chunk($inserts, 200) as $chunk) {
            Product::withoutGlobalScope('centralVisible')->toBase()->insert($chunk);
        }
        foreach (array_chunk($updates, 200) as $chunk) {
            Product::withoutGlobalScope('centralVisible')->toBase()->upsert(
                $chunk,
                ['id'],
                ['slug', 'price', 'default_price', 'profit', 'fixed_shipping_costs', 'weight_grams', 'central_visible', 'is_tenant_owned', 'updated_at'],
            );
        }

        // Tenant product id per central id (existing + freshly inserted).
        $tenantProductIds = [];
        foreach ($existing as $centralId => $row) {
            $tenantProductIds[$centralId] = (int) $row->id;
        }
        if ($newCentralIds !== []) {
            $fresh = Product::withoutGlobalScope('centralVisible')->toBase()
                ->whereIn('central_product_id', $newCentralIds)
                ->orderBy('id')
                ->pluck('id', 'central_product_id');
            foreach ($fresh as $centralId => $id) {
                $tenantProductIds[$centralId] ??= (int) $id;
            }
            if ($ctx->hasPaidTranslation) {
                foreach ($newCentralIds as $centralId) {
                    $ctx->flagged[] = $tenantProductIds[$centralId];
                }
            }
        }

        $this->syncProductTranslations($products, $existing, $tenantProductIds, $ctx);
        $this->syncProductCategories($products, $categoryPivot, $tenantProductIds, $ctx);
        $this->syncVariantsChunk($products, $tenantProductIds, $costsByProduct, $ctx);
    }

    /**
     * @param  Collection<int, CentralProduct>  $products
     * @param  array<int, object>  $existing
     * @param  array<int, int>  $tenantProductIds
     */
    protected function syncProductTranslations(Collection $products, array $existing, array $tenantProductIds, object $ctx): void
    {
        $fields = ['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'];
        $type = (new Product())->getMorphClass();
        $existingTranslations = $ctx->writer->existing($type, array_values($tenantProductIds));
        $desired = [];

        foreach ($products as $product) {
            $tenantId = $tenantProductIds[$product->id];
            // A tenant's admin-approved name/description must survive future syncs;
            // label, summary, meta_keywords, meta_description are never approval-gated so always sync.
            $custom = isset($existing[$product->id]) && $existing[$product->id]->has_custom_translations;

            $desired[$tenantId] = $this->desiredTranslations(
                $existingTranslations[$tenantId] ?? [],
                $custom ? [] : $this->centralLocaleFields($product, $fields, $ctx->centralCodes),
                $custom ? [] : $fields,
                $fields,
                $ctx->codeToId,
                $ctx->activeIds,
            );
        }

        $ctx->writer->replace($type, $desired, $existingTranslations);
    }

    /**
     * Same result as $tenantProduct->categories()->sync([...]): attach the missing links,
     * detach the extra ones and leave links that already match (and their sort_order) alone.
     *
     * @param  Collection<int, CentralProduct>  $products
     * @param  array<int, int[]>  $categoryPivot
     * @param  array<int, int>  $tenantProductIds
     */
    protected function syncProductCategories(Collection $products, array $categoryPivot, array $tenantProductIds, object $ctx): void
    {
        $current = [];
        foreach (\DB::table('category_product')->whereIn('product_id', array_values($tenantProductIds))->get(['product_id', 'category_id']) as $link) {
            $current[(int) $link->product_id][(int) $link->category_id] = true;
        }

        $attach = [];
        $detach = [];
        $now = now();

        foreach ($products as $product) {
            $tenantId = $tenantProductIds[$product->id];
            $wanted = [];

            foreach ($categoryPivot[$product->id] ?? [] as $centralCategoryId) {
                if ($id = $ctx->categoryIdMap[$centralCategoryId] ?? null) {
                    $wanted[(int) $id] = true;
                }
            }

            foreach (array_diff_key($wanted, $current[$tenantId] ?? []) as $categoryId => $_) {
                $attach[] = ['category_id' => $categoryId, 'product_id' => $tenantId, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach (array_diff_key($current[$tenantId] ?? [], $wanted) as $categoryId => $_) {
                $detach[] = [$tenantId, $categoryId];
            }
        }

        foreach (array_chunk($detach, 500) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '(?,?)'));
            \DB::delete("delete from category_product where (product_id, category_id) in ({$placeholders})", array_merge(...$chunk));
        }
        foreach (array_chunk($attach, 500) as $chunk) {
            \DB::table('category_product')->insertOrIgnore($chunk);
        }
    }

    /**
     * @param  Collection<int, CentralProduct>  $products
     * @param  array<int, int>  $tenantProductIds
     * @param  array<int, array<string, float>|null>  $costsByProduct
     */
    protected function syncVariantsChunk(Collection $products, array $tenantProductIds, array $costsByProduct, object $ctx): void
    {
        $now = now();
        $tenantIds = array_values($tenantProductIds);
        $existing = [];

        foreach (ProductVariant::query()->toBase()
            ->whereIn('product_id', $tenantIds)
            ->whereNotNull('central_product_variant_id')
            ->orderBy('id')
            ->get(['id', 'product_id', 'central_product_variant_id', 'option_ids', 'real_price', 'weight_grams', 'sell_price', 'default_sell_price', 'profit', 'stock', 'thumbnail_path']) as $row) {
            $existing[$row->product_id . ':' . $row->central_product_variant_id] ??= $row;
        }

        $inserts = [];
        $updates = [];

        foreach ($products as $product) {
            $tenantProductId = $tenantProductIds[$product->id];
            $productCosts = $costsByProduct[$product->id];

            foreach ($product->variants as $variant) {
                $ctx->seenVariants[$variant->id] = true;

                $row = $existing[$tenantProductId . ':' . $variant->id] ?? null;
                $price = (float) ($variant->price ?? $this->pricing->centralBasePrice($product));
                $pricing = $this->catalogPricing(
                    $price,
                    $this->centralShippingCosts($variant, $ctx->shippingCosts) ?? $productCosts,
                    $row ? $this->jsonColumn($row->profit) : null,
                );
                $optionIds = $variant->options->pluck('id')->map(fn($id) => (int) $id)->all();
                $weight = $row ? ($row->weight_grams ?? $variant->weight_grams) : $variant->weight_grams;
                $stock = $variant->stock ?? 0;
                $thumb = ($row?->thumbnail_path)
                    ?: ($variant->files->first()?->full_path ?? $product->primary_image_url);

                if ($row === null) {
                    $inserts[] = [
                        'product_id' => $tenantProductId,
                        'central_product_variant_id' => $variant->id,
                        'option_ids' => json_encode($optionIds),
                        'real_price' => $price,
                        'weight_grams' => $weight,
                        'sell_price' => json_encode($pricing['prices']),
                        'default_sell_price' => $pricing['default'],
                        'profit' => json_encode($pricing['profit']),
                        'stock' => $stock,
                        'margin_percentage' => $this->profitPercentage,
                        'thumbnail_path' => $thumb,
                        'active' => $variant->status->value === 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    continue;
                }

                if (
                    $this->jsonColumn($row->option_ids) != $optionIds
                    || round((float) $row->real_price, 2) !== round($price, 2)
                    || ($row->weight_grams === null ? null : (int) $row->weight_grams) !== ($weight === null ? null : (int) $weight)
                    || $this->jsonColumn($row->sell_price) != $pricing['prices']
                    || round((float) $row->default_sell_price, 2) !== round((float) $pricing['default'], 2)
                    || $this->jsonColumn($row->profit) != $pricing['profit']
                    || (int) $row->stock !== (int) $stock
                    || $row->thumbnail_path !== $thumb
                ) {
                    $updates[] = [
                        'id' => $row->id,
                        'product_id' => $tenantProductId,
                        'option_ids' => json_encode($optionIds),
                        'real_price' => $price,
                        'weight_grams' => $weight,
                        'sell_price' => json_encode($pricing['prices']),
                        'default_sell_price' => $pricing['default'],
                        'profit' => json_encode($pricing['profit']),
                        'stock' => $stock,
                        'thumbnail_path' => $thumb,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($inserts, 200) as $chunk) {
            ProductVariant::query()->toBase()->insert($chunk);
        }
        foreach (array_chunk($updates, 200) as $chunk) {
            ProductVariant::query()->toBase()->upsert(
                $chunk,
                ['id'],
                ['option_ids', 'real_price', 'weight_grams', 'sell_price', 'default_sell_price', 'profit', 'stock', 'thumbnail_path', 'updated_at'],
            );
        }

        // Variant titles: replace a variant's translations only when central has a title for it.
        $variantIds = [];
        foreach (ProductVariant::query()->toBase()
            ->whereIn('product_id', $tenantIds)
            ->whereNotNull('central_product_variant_id')
            ->orderBy('id')
            ->get(['id', 'product_id', 'central_product_variant_id']) as $row) {
            $variantIds[$row->product_id . ':' . $row->central_product_variant_id] ??= (int) $row->id;
        }

        $type = (new ProductVariant())->getMorphClass();
        $desired = [];

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $titles = $this->centralLocaleFields($variant, ['title'], $ctx->centralCodes);
                if (empty(array_filter($titles, fn($f) => !empty($f['title'])))) {
                    continue;
                }

                $tenantVariantId = $variantIds[$tenantProductIds[$product->id] . ':' . $variant->id] ?? null;
                if ($tenantVariantId) {
                    $desired[$tenantVariantId] = $this->desiredTranslations([], $titles, ['title'], ['title'], $ctx->codeToId, []);
                }
            }
        }

        $ctx->writer->replace($type, $desired);
    }

    /**
     * Remove tenant rows whose central counterpart no longer exists.
     *
     * @param  array<int, true>  $seenVariants  central variant ids that were synced
     * @param  array<int, int>  $syncedProducts  central product ids that were synced (as keys)
     */
    protected function deleteStaleCatalogRows(array $seenVariants, array $syncedProducts): void
    {
        $staleVariants = [];
        foreach (ProductVariant::query()->toBase()->whereNotNull('central_product_variant_id')->pluck('central_product_variant_id', 'id') as $id => $centralId) {
            if (! isset($seenVariants[$centralId])) {
                $staleVariants[] = $id;
            }
        }
        foreach (array_chunk($staleVariants, 1000) as $ids) {
            ProductVariant::query()->whereIn('id', $ids)->delete();
        }

        // Blank variants without a central link on a catalog product are leftovers of
        // the old product-form bug; the real variants were re-created above.
        ProductVariant::query()
            ->whereNull('central_product_variant_id')
            ->whereIn('product_id', Product::withoutGlobalScope('centralVisible')
                ->whereNotNull('central_product_id')
                ->where('is_own_product', false)
                ->select('id'))
            ->delete();

        // Only delete catalog products that are no longer in either the catalog or the tenant assignments
        $staleProducts = [];
        foreach (Product::withoutGlobalScope('centralVisible')->toBase()
            ->whereNotNull('central_product_id')
            ->where('is_own_product', false)
            ->pluck('central_product_id', 'id') as $id => $centralId) {
            if (! isset($syncedProducts[$centralId])) {
                $staleProducts[] = $id;
            }
        }
        foreach (array_chunk($staleProducts, 1000) as $ids) {
            Product::withoutGlobalScope('centralVisible')->whereIn('id', $ids)->delete();
        }
    }

    /**
     * @return array<int, string> active central language id => code
     */
    protected function centralLanguageCodes(): array
    {
        return CentralLanguage::query()->where('is_active', true)->pluck('code', 'id')->all();
    }

    /**
     * @return array{0: array<string, int>, 1: int[]} tenant language code => id (all languages), ids of the active ones
     */
    protected function tenantLanguageMaps(): array
    {
        $languages = Language::query()->toBase()->get(['id', 'code', 'is_active']);

        return [
            $languages->pluck('id', 'code')->map(fn($id) => (int) $id)->all(),
            $languages->where('is_active', true)->pluck('id')->map(fn($id) => (int) $id)->all(),
        ];
    }

    /**
     * In-memory equivalent of Translator::translationsByLocale() for a central model whose
     * `translations` relation is already loaded.
     *
     * @param  array<int, string>  $centralCodes
     * @return array<string, array<string, string>> locale => field => value
     */
    protected function centralLocaleFields(object $model, array $fields, array $centralCodes): array
    {
        $byLanguage = [];
        foreach ($model->translations as $translation) {
            $byLanguage[$translation->language_id][$translation->field] ??= $translation->value;
        }

        $out = [];
        foreach ($centralCodes as $languageId => $code) {
            foreach ($fields as $field) {
                $out[$code][$field] = $byLanguage[$languageId][$field] ?? '';
            }
        }

        return $out;
    }

    /**
     * Final translation rows for one model, equal to mergeTranslatedFields() followed by
     * Translator::syncTranslations(): tenant-edited values in active languages are kept,
     * the managed fields are overwritten by central, empties are dropped.
     *
     * @param  array<int, array<string, string>>  $existing  languageId => field => value
     * @param  array<string, array<string, string>>  $incoming  locale => field => value
     * @param  array<string, int>  $codeToId
     * @param  int[]  $activeIds
     * @return array<int, array<string, string>>
     */
    protected function desiredTranslations(array $existing, array $incoming, array $managed, array $allFields, array $codeToId, array $activeIds): array
    {
        $out = [];

        foreach ($activeIds as $languageId) {
            foreach ($allFields as $field) {
                $value = trim((string) ($existing[$languageId][$field] ?? ''));
                if ($value !== '') {
                    $out[$languageId][$field] = $value;
                }
            }
        }

        foreach ($incoming as $locale => $fields) {
            $languageId = $codeToId[$locale] ?? null;
            if (! $languageId) {
                continue;
            }

            foreach ($managed as $field) {
                $value = trim((string) ($fields[$field] ?? ''));
                if ($value === '') {
                    unset($out[$languageId][$field]);
                } else {
                    $out[$languageId][$field] = $value;
                }
            }
        }

        return array_filter($out);
    }

    /**
     * Tenant rows keyed by their central id (the first row wins when duplicates exist),
     * read without hydrating models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  string[]  $columns
     * @param  int[]|null  $centralIds
     * @return array<int, object>
     */
    protected function tenantRowsByCentralId($query, string $centralColumn, array $columns, ?array $centralIds = null): array
    {
        $rows = [];

        foreach ($query->toBase()
            ->whereNotNull($centralColumn)
            ->when($centralIds !== null, fn($q) => $q->whereIn($centralColumn, $centralIds))
            ->orderBy('id')
            ->get($columns) as $row) {
            $rows[(int) $row->{$centralColumn}] ??= $row;
        }

        return $rows;
    }

    protected function jsonColumn(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = is_string($value) ? json_decode($value, true) : null;

        return is_array($decoded) ? $decoded : null;
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
        $shippingCosts = $this->central(
            fn() => FixedShippingCost::query()->where('is_active', true)->get()->keyBy('country_id')
        );

        tenancy()->initialize($tenant);

        $centralProduct->load(['translations.language', 'categories', 'variants.translations.language', 'variants.options.translations.language', 'variants.files', 'files', 'countries']);

        $tenantProduct = Product::withoutGlobalScope('centralVisible')->firstOrNew(['central_product_id' => $centralProduct->id]);
        $isNewProduct = !$tenantProduct->exists;

        $productCosts = $this->centralShippingCosts($centralProduct, $shippingCosts);
        $productPricing = $this->catalogPricing(
            app(TenantPricingService::class)->centralBasePrice($centralProduct),
            $productCosts,
            $tenantProduct->profit,
        );

        $tenantProduct->fill([
            'slug' => $centralProduct->slug,
            'price' => $productPricing['prices'],
            'default_price' => $productPricing['default'],
            'profit' => $productPricing['profit'],
            'fixed_shipping_costs' => $productCosts,
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
            $tenantProduct->has_custom_translations ? [] : $centralProduct->translationsByLocale(['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description']),
            $tenantProduct->has_custom_translations ? [] : ['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'],
            ['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description']
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
            $centralVariantPrice = (float) ($variant->price ?? app(TenantPricingService::class)->centralBasePrice($centralProduct));
            $variantPricing = $this->catalogPricing(
                $centralVariantPrice,
                $this->centralShippingCosts($variant, $shippingCosts) ?? $productCosts,
                $tenantVariant->profit,
            );
            $centralThumb = $variant->relationLoaded('files')
                ? $variant->files->firstWhere('key', 'variant_thumb')
                : $variant->files()->where('key', 'variant_thumb')->first();

            $tenantVariant->fill([
                'option_ids' => $variant->options->pluck('id')->map(fn($id) => (int) $id)->all(),
                'real_price' => $centralVariantPrice,
                'weight_grams' => $tenantVariant->weight_grams ?? $variant->weight_grams,
                'sell_price' => $variantPricing['prices'],
                'default_sell_price' => $variantPricing['default'],
                'profit' => $variantPricing['profit'],
                'stock' => $variant->stock ?? 0,
                'margin_percentage' => $isNewVariant ? $this->profitPercentage : $tenantVariant->margin_percentage,
                'thumbnail_path' => $tenantVariant->thumbnail_path
                    ?: ($centralThumb?->full_path ?? $centralProduct->primary_image_url),
                'active' => $isNewVariant ? ($variant->status->value === 'active') : $tenantVariant->active,
            ]);
            $tenantVariant->save();

            $variantTitleTranslations = $variant->translationsByLocale(['title']);
            if (!empty(array_filter($variantTitleTranslations, fn($f) => !empty($f['title'])))) {
                $tenantVariant->syncTranslations($variantTitleTranslations);
            }
        }

        if (!$tenantProduct->is_own_product) {
            ProductVariant::query()
                ->where('product_id', $tenantProduct->id)
                ->whereNull('central_product_variant_id')
                ->delete();
        }

        // Category pivot sync, translations and the variant delete skip model events.
        CacheVersion::bumpMany(['Product', 'ProductVariant', 'Category']);

        $this->central(fn() => ProductTenantAssignment::query()
            ->where('product_id', $centralProduct->id)
            ->where('tenant_id', $tenant->id)
            ->update(['synced_at' => now()]));
    }

    /**
     * Central `{country_id: shipping}` map for a product or variant: the stored
     * JSON when present, otherwise derived from weight x active per-gram rules.
     *
     * @param Collection<int, FixedShippingCost> $rules
     * @return array<string, float>|null
     */
    protected function centralShippingCosts(object $central, Collection $rules): ?array
    {
        if (is_array($central->fixed_shipping_costs) && $central->fixed_shipping_costs !== []) {
            return $central->fixed_shipping_costs;
        }

        $weight = (int) ($central->weight_grams ?? 0);

        if ($weight <= 0 || $rules->isEmpty()) {
            return null;
        }

        return $rules
            ->mapWithKeys(fn($rule, $countryId) => [(string) $countryId => round((float) $rule->price_per_gram * $weight, 2)])
            ->all();
    }

    /**
     * @param array<string, mixed>|null $costs
     * @param array<string, mixed>|null $existingProfit
     * @return array{prices: array<string, float>, default: float, profit: array<string, array<string, mixed>>}
     */
    protected function catalogPricing(float $base, ?array $costs, ?array $existingProfit): array
    {
        return ($this->pricing ??= app(TenantPricingService::class))->forCatalog($base, $costs, $existingProfit, $this->profitPercentage);
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
        return $this->central(fn() => CentralAppSetting::query()->where('key', 'default_language')->value('value')
            ?? CentralLanguage::query()->where('is_default', true)->value('code')
            ?? 'en');
    }

    protected function syncFlashSales(): int
    {
        $centralSales = $this->central(
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

        // Pivot sync and the stale delete skip model events.
        CacheVersion::bumpMany(['FlashSale', 'Product']);

        return $centralSales->count();
    }

    protected function syncBadges(): int
    {
        $centralBadges = $this->central(
            fn() => CentralProductBadge::query()->get()
        );

        $centralPivotRows = $this->central(
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

        // Raw pivot writes and the stale delete skip model events.
        CacheVersion::bumpMany(['ProductBadge', 'Product']);

        return $centralBadges->count();
    }

    protected function syncCoupons(): int
    {
        $centralCoupons = $this->central(
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
