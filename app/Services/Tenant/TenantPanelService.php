<?php

namespace App\Services\Tenant;

use App\Models\AppSetting as CentralAppSetting;
use App\Models\DefaultBanner;
use App\Models\ProductEditRequest;
use App\Models\Tenant\AdminRole;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Banner;
use App\Models\Tenant\Category;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Customer;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\EmailTemplateTranslation;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Language;
use App\Models\Tenant\Page;
use App\Models\Tenant\PaymentGateway;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Models\Tenant\SocialLink;
use App\Models\Tenant\Subscriber;
use App\Models\Tenant\Theme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantPanelService
{
    public function saveAdmin(array $attributes, ?AdminUser $admin = null): AdminUser
    {
        return DB::transaction(function () use ($attributes, $admin) {
            $admin ??= new AdminUser();
            $admin->fill([
                'role_id' => $attributes['role_id'] ?: null,
                'name' => $attributes['name'],
                'email' => strtolower(trim((string) $attributes['email'])),
                'status' => $attributes['status'],
                'last_login_at' => $attributes['last_login_at'] ?? $admin->last_login_at,
            ]);

            if (filled($attributes['password'] ?? null)) {
                $admin->password = Hash::make((string) $attributes['password']);
            }

            $admin->save();

            return $admin->fresh('role');
        });
    }

    public function saveAdminRole(array $attributes, ?AdminRole $role = null): AdminRole
    {
        return DB::transaction(function () use ($attributes, $role) {
            $role ??= new AdminRole();
            $permissions = array_values(array_unique(array_filter($attributes['permissions'] ?? [])));

            $role->fill([
                'name' => $attributes['name'],
                'permissions' => $permissions,
                'permissions_count' => count($permissions),
            ]);
            $role->save();

            return $role->fresh('admins');
        });
    }

    public function saveProduct(array $attributes, ?Product $product = null): Product
    {
        return DB::transaction(function () use ($attributes, $product) {
            $product ??= new Product();

            // `price` may arrive as an array (per-country JSON map) or as a
            // legacy scalar (e.g. from the own-product form). Normalise here.
            $incomingPrice = $attributes['price'] ?? null;
            if (is_numeric($incomingPrice)) {
                // Own-product / own-product forms still send a single scalar.
                $newPrice = ['default' => (float) $incomingPrice];
                $newDefaultPrice = (float) $incomingPrice;
            } elseif (is_array($incomingPrice) && !empty($incomingPrice)) {
                $newPrice = $incomingPrice;
                $newDefaultPrice = (float) ($incomingPrice['default'] ?? min(array_filter($incomingPrice, 'is_numeric')) ?: 0);
            } else {
                $newPrice = $product->price ?? ['default' => 0];
                $newDefaultPrice = (float) ($product->default_price ?? 0);
            }

            $productFill = [
                'slug' => Str::slug($attributes['slug'] ?: data_get($attributes, 'translations.' . ($attributes['default_locale'] ?? 'en') . '.name', 'product')),
                'price' => $newPrice,
                'default_price' => $newDefaultPrice,
                'active' => (bool) ($attributes['active'] ?? true),
                'featured' => (bool) ($attributes['featured'] ?? false),
                'is_own_product' => (bool) ($attributes['is_own_product'] ?? false),
                'requires_shipping' => (bool) ($attributes['requires_shipping'] ?? true),
                'stock' => array_key_exists('stock', $attributes) ? $attributes['stock'] : $product->stock,
            ];

            if (array_key_exists('central_product_id', $attributes)) {
                $productFill['central_product_id'] = $attributes['central_product_id'];
            }
            if (array_key_exists('sku', $attributes)) {
                $productFill['sku'] = $attributes['sku'] ?: null;
            }
            if (array_key_exists('sale_price', $attributes)) {
                $productFill['sale_price'] = $attributes['sale_price'] !== '' && $attributes['sale_price'] !== null ? (float) $attributes['sale_price'] : null;
            }
            if (array_key_exists('cost_price', $attributes)) {
                $productFill['cost_price'] = $attributes['cost_price'] !== '' && $attributes['cost_price'] !== null ? (float) $attributes['cost_price'] : null;
            }
            if (array_key_exists('min_stock', $attributes)) {
                $productFill['min_stock'] = (int) $attributes['min_stock'];
            }
            if (array_key_exists('manage_stock', $attributes)) {
                $productFill['manage_stock'] = (bool) $attributes['manage_stock'];
            }
            if (array_key_exists('is_taxable', $attributes)) {
                $productFill['is_taxable'] = (bool) $attributes['is_taxable'];
            }
            if (array_key_exists('weight_grams', $attributes)) {
                $productFill['weight_grams'] = $attributes['weight_grams'] !== '' && $attributes['weight_grams'] !== null ? (int) $attributes['weight_grams'] : null;
            }

            $product->fill($productFill);
            $product->save();
            $product->categories()->sync($attributes['category_ids'] ?? []);
            $product->syncTranslations($attributes['translations'] ?? []);

            if (array_key_exists('variants', $attributes)) {
                $keptVariantIds = [];

                foreach ($attributes['variants'] ?? [] as $variantAttributes) {
                    $variant = null;

                    if (filled($variantAttributes['id'] ?? null)) {
                        $variant = $product->variants()->find($variantAttributes['id']);
                    }

                    if (!$variant && filled($variantAttributes['central_product_variant_id'] ?? null)) {
                        $variant = $product->variants()
                            ->where('central_product_variant_id', $variantAttributes['central_product_variant_id'])
                            ->first();
                    }

                    $variant ??= $product->variants()->make();

                    // Normalise sell_price (may be scalar or array).
                    $incomingSellPrice = $variantAttributes['sell_price'] ?? $variant->sell_price ?? $variantAttributes['real_price'] ?? 0;
                    if (is_numeric($incomingSellPrice)) {
                        $newSellPrice = ['default' => (float) $incomingSellPrice];
                        $newDefaultSellPrice = (float) $incomingSellPrice;
                    } elseif (is_array($incomingSellPrice) && !empty($incomingSellPrice)) {
                        $newSellPrice = $incomingSellPrice;
                        $newDefaultSellPrice = (float) ($incomingSellPrice['default'] ?? min(array_filter($incomingSellPrice, 'is_numeric')) ?: 0);
                    } else {
                        $newSellPrice = $variant->sell_price ?? ['default' => 0];
                        $newDefaultSellPrice = (float) ($variant->default_sell_price ?? 0);
                    }

                    $fill = [
                        'central_product_variant_id' => $variantAttributes['central_product_variant_id'] ?? $variant->central_product_variant_id,
                        'title' => trim((string) ($variantAttributes['title'] ?? '')) ?: null,
                        'sku' => trim((string) ($variantAttributes['sku'] ?? '')) ?: null,
                        'weight_grams' => ($variantAttributes['weight_grams'] ?? '') !== '' && ($variantAttributes['weight_grams'] ?? null) !== null ? (int) $variantAttributes['weight_grams'] : null,
                        'real_price' => $variantAttributes['real_price'] ?? $variant->real_price ?? 0,
                        'sell_price' => $newSellPrice,
                        'default_sell_price' => $newDefaultSellPrice,
                        'active' => (bool) ($variantAttributes['active'] ?? $variant->active ?? true),
                    ];

                    if (array_key_exists('option_ids', $variantAttributes)) {
                        $fill['option_ids'] = collect($variantAttributes['option_ids'] ?? [])
                            ->filter(fn($id) => filled($id))
                            ->map(fn($id) => (int) $id)
                            ->unique()
                            ->values()
                            ->all();
                    }

                    if (array_key_exists('stock', $variantAttributes)) {
                        $fill['stock'] = $variantAttributes['stock'] === null
                            ? null
                            : (int) $variantAttributes['stock'];
                    }

                    // Handle image upload (UploadedFile) — store and set thumbnail_path.
                    $image = $variantAttributes['image'] ?? null;
                    if ($image instanceof \Illuminate\Http\UploadedFile) {
                        $path = $image->store('product-variants', 'public');
                        $fill['thumbnail_path'] = $path;
                    } elseif (($variantAttributes['remove_image'] ?? false) === true) {
                        $fill['thumbnail_path'] = null;
                    } elseif (array_key_exists('thumbnail_path', $variantAttributes) && $variantAttributes['thumbnail_path'] !== null) {
                        $fill['thumbnail_path'] = $variantAttributes['thumbnail_path'];
                    }

                    $variant->fill($fill);
                    $variant->save();
                    $keptVariantIds[] = $variant->id;
                }

                $product->variants()
                    ->when($keptVariantIds !== [], fn($query) => $query->whereNotIn('id', $keptVariantIds))
                    ->delete();
            }

            return $product->fresh(['translations.language', 'categories', 'variants']);
        });
    }

    public function saveCategory(array $attributes, ?Category $category = null): Category
    {
        return DB::transaction(function () use ($attributes, $category) {
            $category ??= new Category();

            // Build per-locale slugs: auto-generate from locale name if slug field is empty
            $translations = $attributes['translations'] ?? [];
            foreach ($translations as $locale => &$localeData) {
                $localeName = trim((string) data_get($localeData, 'name', ''));
                $localeSlugInput = trim((string) data_get($localeData, 'slug', ''));

                if ($localeName === '' && $localeSlugInput === '') {
                    continue;
                }

                $generated = Str::slug($localeSlugInput ?: $localeName);
                $localeData['slug'] = $this->uniqueTenantCategorySlug($generated, $locale, $category->getKey());
            }
            unset($localeData);

            $category->fill([
                'central_category_id' => $attributes['central_category_id'] ?? null,
                'parent_id' => $attributes['parent_id'] ?: null,
                'order_number' => (int) ($attributes['order_number'] ?? 0),
                'active' => (bool) ($attributes['active'] ?? true),
                'featured' => (bool) ($attributes['featured'] ?? false),
            ]);
            $category->save();
            $category->syncTranslations($translations);

            return $category->fresh(['translations.language', 'parent']);
        });
    }

    protected function uniqueTenantCategorySlug(string $base, string $locale, mixed $ignoreId = null): string
    {
        $base = $base ?: Str::random(8);
        $slug = $base;
        $index = 1;

        while (
            \App\Models\Tenant\Translation::query()
                ->where('translatable_type', Category::class)
                ->when($ignoreId, fn($q) => $q->where('translatable_id', '!=', $ignoreId))
                ->whereHas('language', fn($q) => $q->where('code', $locale))
                ->where('field', 'slug')
                ->where('value', $slug)
                ->exists()
        ) {
            $index++;
            $slug = sprintf('%s-%s', $base, $index);
        }

        return $slug;
    }

    public function saveCustomer(array $attributes, ?Customer $customer = null): Customer
    {
        return DB::transaction(function () use ($attributes, $customer) {
            $customer ??= new Customer();
            $customer->fill([
                'full_name' => $attributes['full_name'],
                'email' => strtolower(trim((string) $attributes['email'])),
                'phone' => $attributes['phone'] ?: null,
                'address' => $attributes['address'] ?: null,
                'country_id' => $attributes['country_id'] ?: null,
                'city_id' => $attributes['city_id'] ?: null,
                'active' => (bool) ($attributes['active'] ?? true),
            ]);

            if (filled($attributes['password'] ?? null)) {
                $customer->password = Hash::make((string) $attributes['password']);
            }

            $customer->save();

            return $customer->fresh();
        });
    }

    public function savePage(array $attributes, ?Page $page = null): Page
    {
        return DB::transaction(function () use ($attributes, $page) {
            $page ??= new Page();
            $page->fill([
                'slug' => Str::slug($attributes['slug'] ?: data_get($attributes, 'translations.' . ($attributes['default_locale'] ?? 'en') . '.title', 'page')),
                'active' => (bool) ($attributes['active'] ?? true),
            ]);
            $page->save();
            $page->syncTranslations($attributes['translations'] ?? []);
            $this->markDefaultPagesReviewed();

            return $page->fresh('translations.language');
        });
    }

    public function saveCoupon(array $attributes, ?Coupon $coupon = null): Coupon
    {
        return DB::transaction(function () use ($attributes, $coupon) {
            $coupon ??= new Coupon();
            $coupon->fill([
                'code' => strtoupper(trim((string) $attributes['code'])),
                'type' => $attributes['type'],
                'value' => $attributes['value'],
                'start_date' => $attributes['start_date'] ?? null,
                'end_date' => $attributes['end_date'] ?? null,
                'minimum_spend' => filled($attributes['minimum_spend'] ?? null) ? $attributes['minimum_spend'] : 0,
            ]);
            $coupon->save();
            $coupon->syncTranslations($attributes['translations'] ?? []);

            return $coupon->fresh('translations.language');
        });
    }

    public function saveFlashSale(array $attributes, ?FlashSale $flashSale = null): FlashSale
    {
        return DB::transaction(function () use ($attributes, $flashSale) {
            $flashSale ??= new FlashSale();
            $productIds = collect($attributes['product_ids'] ?? [])
                ->filter(fn($id) => filled($id))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $flashSale->fill([
                'product_id' => $productIds[0] ?? $attributes['product_id'] ?? $flashSale->product_id,
                'discount_percentage' => $attributes['discount_percentage'],
                'start_date' => $attributes['start_date'] ?? null,
                'end_date' => $attributes['end_date'] ?? null,
                'active' => (bool) ($attributes['active'] ?? true),
                'banner_image' => $attributes['banner_image'] ?? $flashSale->banner_image,
            ]);
            $flashSale->save();

            $flashSale->products()->sync(
                $productIds !== []
                ? $productIds
                : array_filter([(int) ($flashSale->product_id ?? 0)])
            );

            return $flashSale->fresh(['product.translations.language', 'products.translations.language']);
        });
    }

    public function saveGateway(array $attributes, PaymentGateway $gateway): PaymentGateway
    {
        $requiredValues = collect($attributes['required_values'] ?? [])
            ->reject(fn($v, $k) => $k === 'sandbox')
            ->all();

        $requiredKeys = collect((array) ($gateway->required_keys ?? []))
            ->filter(fn($k) => $k !== 'sandbox')
            ->values()
            ->all();

        $gateway->fill([
            'mode'            => $attributes['mode'],
            'use_own'         => (bool) ($attributes['use_own'] ?? false),
            'required_keys'   => $requiredKeys,
            'required_values' => $requiredValues,
        ]);
        $gateway->save();

        // If the tenant has just enabled their own credentials, auto-unblock their
        // storefront in case they were previously blocked by the gateway limit policy.
        if ($gateway->use_own) {
            $tenant = tenant();
            if ($tenant instanceof \App\Models\Tenant) {
                app(\App\Services\TenantGatewayLimitService::class)->unblockIfConfigured($tenant);
            }
        }

        return $gateway->fresh();
    }

    public function activateTheme(Theme $theme): void
    {
        DB::transaction(function () use ($theme) {
            if ($theme->is_universal) {
                // Universal themes (no country restrictions) behave like a radio button —
                // only one can be the primary active theme at a time. Country-specific
                // themes are left alone since they can coexist with a universal default.
                Theme::query()
                    ->where('id', '!=', $theme->id)
                    ->where('is_universal', true)
                    ->update(['is_active' => false]);
            }

            // Country-specific themes just toggle on independently.
            $theme->update(['is_active' => true]);
        });
    }

    public function deactivateTheme(Theme $theme): void
    {
        // Only meaningful for country-specific themes; deactivating a universal
        // theme would leave the storefront with no fallback, so we guard it.
        if ($theme->is_universal) {
            throw new \DomainException(
                'Universal themes cannot be deactivated directly — activate another universal theme to replace it.'
            );
        }

        $theme->update(['is_active' => false]);
    }

    public function markDefaultCurrency(Currency $currency): void
    {
        DB::transaction(function () use ($currency) {
            Currency::query()->update(['is_default' => false]);
            $currency->update(['is_default' => true, 'is_active' => true]);
            Setting::query()->updateOrCreate(['name' => 'default_currency'], ['value' => $currency->code, 'type' => 'string', 'group' => 'general']);
        });
    }

    public function markDefaultLanguage(Language $language): void
    {
        DB::transaction(function () use ($language) {
            Language::query()->update(['is_default' => false]);
            $language->update(['is_default' => true, 'is_active' => true]);
            Setting::query()->updateOrCreate(['name' => 'default_language'], ['value' => $language->code, 'type' => 'string', 'group' => 'general']);
        });
    }

    public function saveMailSettings(array $attributes): void
    {
        $centralMailSettings = tenant()
            ? tenancy()->central(fn() => CentralAppSetting::query()
                ->whereIn('key', array_keys($attributes))
                ->pluck('value', 'key')
                ->all())
            : [];

        foreach ($attributes as $name => $value) {
            $normalizedValue = trim((string) ($value ?? ''));
            $centralValue = trim((string) ($centralMailSettings[$name] ?? ''));

            if ($normalizedValue === '' || $normalizedValue === $centralValue) {
                Setting::query()->where('name', $name)->delete();
                continue;
            }

            Setting::query()->updateOrCreate(['name' => $name], ['value' => $normalizedValue, 'type' => 'string', 'group' => 'mail']);
        }
    }

    public function saveEmailTemplate(array $attributes, EmailTemplate $template): EmailTemplate
    {
        $template->fill([
            'subject' => $attributes['subject'],
            'body' => $attributes['body'] ?? null,
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ]);
        $template->save();

        foreach ($attributes['translations'] ?? [] as $locale => $trans) {
            $subject = trim($trans['subject'] ?? '');
            $body = trim($trans['body'] ?? '');

            if ($subject === '' && $body === '') {
                EmailTemplateTranslation::query()
                    ->where('email_template_id', $template->id)
                    ->where('locale', $locale)
                    ->delete();
                continue;
            }

            EmailTemplateTranslation::query()->updateOrCreate(
                ['email_template_id' => $template->id, 'locale' => $locale],
                ['subject' => $subject ?: $template->subject, 'body' => $body ?: null],
            );
        }

        return $template->fresh();
    }

    public function updateAccount(array $attributes): void
    {
        $tenant = tenant();
        $admin = auth('tenant')->user();

        if (!$tenant) {
            return;
        }

        $data = $tenant->data ?? [];

        if ($admin instanceof AdminUser) {
            $admin->fill([
                'name' => $attributes['admin_name'] ?? $admin->name,
                'email' => $attributes['admin_email'] ?? $admin->email,
            ]);

            if (filled($attributes['password'] ?? null)) {
                $admin->password = Hash::make((string) $attributes['password']);
            }

            $admin->save();
        }

        $incomingPhone = (string) ($attributes['phone'] ?? $tenant->phone ?? '');
        $phone = str_contains($incomingPhone, 'object') ? null : ($incomingPhone ?: null);
        $shopName = $attributes['shop_name'] ?? ($data['shop_name'] ?? $tenant->name);
        $address = $attributes['address'] ?? ($data['address'] ?? null);
        $description = $attributes['description'] ?? ($data['description'] ?? null);

        // Must run in central context: inside a tenant request the default DB
        // connection is switched to the tenant DB; Tenant model lives in central.
        // The Tenant model virtualizes non-column attributes into the `data`
        // JSON column on save, so these must be set as top-level attributes
        // (not nested under `data`) or they'll be discarded on save.
        tenancy()->central(function () use ($tenant, $phone, $shopName, $address, $description) {
            $tenant->phone = $phone;
            $tenant->description = $description;
            $tenant->address = $address;
            $tenant->shop_name = $shopName;
            $tenant->save();
        });

        if (array_key_exists('shop_name', $attributes)) {
            Setting::query()->updateOrCreate(
                ['name' => 'store_name'],
                ['value' => (string) $attributes['shop_name'], 'group' => 'appearance']
            );
        }
    }

    /**
     * Persist the full Compliance Center form. `$attributes` keys are the plain
     * field names (see ComplianceCenterPage); this maps them to `compliance_*`
     * keys inside the tenant's `data` JSON column, keeping anything not passed.
     */
    public function updateCompliance(array $attributes): void
    {
        $tenant = tenant();

        if (!$tenant) {
            return;
        }

        $data = $tenant->data ?? [];

        $fieldMap = [
            // Business Info
            'business_name' => 'compliance_business_name',
            'store_name' => 'compliance_store_name',
            'country' => 'compliance_country',
            'city' => 'compliance_city',
            'phone' => 'compliance_phone',
            'email' => 'compliance_email',
            // Owner Info
            'owner_name' => 'compliance_owner_name',
            'owner_id_number' => 'compliance_owner_id_number',
            'owner_dob' => 'compliance_owner_dob',
            'owner_contact' => 'compliance_owner_contact',
            // Company Info
            'company_name' => 'compliance_company_name',
            'registration_number' => 'compliance_registration_number',
            'registration_expiry' => 'compliance_registration_expiry',
            'vat_number' => 'compliance_vat_number',
            'registration_document_path' => 'compliance_registration_document_path',
            // Bank Account
            'bank_name' => 'compliance_bank_name',
            'bank_holder_name' => 'compliance_bank_holder_name',
            'bank_iban' => 'compliance_bank_iban',
            'bank_account_number' => 'compliance_bank_account_number',
            'bank_currency' => 'compliance_bank_currency',
            // Verification Documents
            'doc_national_id_path' => 'compliance_doc_national_id_path',
            'doc_commercial_registration_path' => 'compliance_doc_commercial_registration_path',
            'doc_tax_certificate_path' => 'compliance_doc_tax_certificate_path',
            'doc_additional_paths' => 'compliance_doc_additional_paths',
        ];

        $updates = [];
        foreach ($fieldMap as $attributeKey => $dataKey) {
            if (array_key_exists($attributeKey, $attributes)) {
                $updates[$dataKey] = $attributes[$attributeKey];
            }
        }
        dd($updates);
        // Must run in central context (tenant request switches default DB to tenant DB)
        tenancy()->central(function () use ($tenant, $data, $updates) {
            $tenant->fill($updates);
            $tenant->save();
        });
    }

    /**
     * Store a Compliance Center document upload under a per-tenant folder and
     * return its public tenant_asset() URL.
     */
    public function storeComplianceDocument(\Illuminate\Http\UploadedFile $file): string
    {
        return tenant_asset($file->store('compliance-documents', 'public'));
    }

    /** Marks the setup-progress "default pages reviewed" step, called whenever a tenant saves a storefront page. */
    public function markDefaultPagesReviewed(): void
    {
        Setting::query()->updateOrCreate(
            ['name' => 'default_pages_reviewed_at'],
            ['value' => (string) now(), 'group' => 'general']
        );
    }

    public function deleteModel(Model $model): void
    {
        $model->delete();
    }

    public function deleteSubscriber(Subscriber $subscriber): void
    {
        $subscriber->delete();
    }

    public function deleteAdminRole(AdminRole $role): void
    {
        DB::transaction(function () use ($role) {
            $role->admins()->update(['role_id' => null]);
            $role->delete();
        });
    }

    public function saveBanner(array $data, ?Banner $banner = null): Banner
    {
        return DB::transaction(function () use ($data, $banner) {
            $banner ??= new Banner();
            $banner->fill([
                'url' => $data['url'] ?? null,
                'image_path' => $data['image_path'] ?? null,
                'serial_number' => (int) ($data['serial_number'] ?? 0),
                'country_id' => $data['country_id'] ?? null,
            ]);
            $banner->save();
            $banner->syncTranslations($data['translations'] ?? []);

            return $banner->fresh('translations');
        });
    }

    public function saveSocialLink(array $data, ?SocialLink $link = null): SocialLink
    {
        $link ??= new SocialLink();
        $link->fill([
            'icon' => $data['icon'],
            'url' => $data['url'],
            'serial_number' => (int) ($data['serial_number'] ?? 0),
        ]);
        $link->save();

        return $link;
    }


    public function saveTrackingSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            Setting::query()->updateOrCreate(
                ['name' => $key],
                ['value' => (string) ($value ?? ''), 'group' => 'tracking']
            );
        }
    }

    public function saveAppearanceSettings(array $data): void
    {
        $pathKeys = ['logo_path_ar', 'logo_path_en'];

        $previousPaths = [];
        foreach ($pathKeys as $pathKey) {
            if (array_key_exists($pathKey, $data)) {
                $previousPaths[$pathKey] = (string) (Setting::query()->where('name', $pathKey)->value('value') ?? '');
            }
        }

        foreach ($data as $key => $value) {
            Setting::query()->updateOrCreate(
                ['name' => $key],
                ['value' => (string) ($value ?? ''), 'group' => 'appearance']
            );
        }

        // Sync logo URL to the central tenant record so it's visible in the admin
        // panel. The English logo is the canonical one shown centrally.
        if (array_key_exists('logo_path_en', $data)) {
            $central = tenant();
            if ($central instanceof \App\Models\Tenant) {
                tenancy()->central(function () use ($central, $data) {
                    $tenantRecord = \App\Models\Tenant::query()->find($central->getTenantKey());
                    if ($tenantRecord) {
                        $tenantData = $tenantRecord->data ?? [];
                        $tenantData['logo_url'] = (string) ($data['logo_path_en'] ?? '');
                        $tenantRecord->update(['data' => $tenantData]);
                    }
                });
            }
        }

        foreach ($previousPaths as $pathKey => $previousPath) {
            $newPath = (string) ($data[$pathKey] ?? '');
            if (!$previousPath || $previousPath === $newPath) {
                continue;
            }

            // AR and EN can point at the same file (e.g. right after the legacy
            // single-logo migration), so never delete one that is still in use.
            $stillUsed = Setting::query()
                ->whereIn('name', $pathKeys)
                ->where('value', $previousPath)
                ->exists();

            if (!$stillUsed) {
                $this->deleteStoredAsset($previousPath);
            }
        }
    }

    /**
     * Delete a file previously stored on the "public" disk and exposed via
     * tenant_asset(), given its tenant_asset URL. Used to remove logos/banners
     * that are replaced so unused uploads/AI generations don't pile up.
     */
    public function deleteStoredAsset(string $assetUrl): void
    {
        $path = parse_url($assetUrl, PHP_URL_QUERY);
        if (!$path) {
            return;
        }

        parse_str($path, $query);
        $relativePath = $query['path'] ?? null;

        if (!$relativePath || !is_string($relativePath)) {
            return;
        }

        // Only ever delete files under the appearance-managed folders.
        if (!Str::startsWith($relativePath, ['appearances/logos/', 'appearances/banners/'])) {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }

    /**
     * Persist per-locale footer copy (footer_text + footer_copyright).
     *
     * Stores the default-locale value in the legacy `value` column for backwards
     * compatibility and writes one translation row per (setting, locale, field).
     *
     * @param  array<string, array{footer_text?:string,footer_copyright?:string}>  $translations
     *         Outer key is the locale code, inner keys are the setting names.
     */
    public function saveFooterTranslations(array $translations): void
    {
        $defaultLocale = Language::query()->where('is_default', true)->value('code')
            ?? config('app.fallback_locale', 'en');

        DB::transaction(function () use ($translations, $defaultLocale) {
            foreach (['footer_text', 'footer_copyright'] as $name) {
                $fallback = (string) ($translations[$defaultLocale][$name] ?? '');

                /** @var Setting $setting */
                $setting = Setting::query()->updateOrCreate(
                    ['name' => $name],
                    ['value' => $fallback, 'group' => 'appearance']
                );

                $payload = [];
                foreach ($translations as $locale => $fields) {
                    $payload[$locale] = ['value' => (string) ($fields[$name] ?? '')];
                }

                $setting->syncTranslations($payload);
            }
        });
    }

    public function seedAppearanceDefaults(string $shopName = ''): void
    {
        // Store name and copyright are derived from the tenant's shop name, so
        // keep them in sync with the central record on every sync — not just
        // seeded once and left static.
        $synced = [
            'store_name' => $shopName ?: 'My Store',
            'footer_copyright' => '© ' . date('Y') . ' ' . ($shopName ?: 'My Store') . '. All rights reserved.',
        ];

        foreach ($synced as $key => $value) {
            Setting::query()->updateOrCreate(
                ['name' => $key],
                ['value' => $value, 'group' => 'appearance']
            );
        }

        $defaults = [
            'logo_path' => '',
            'footer_text' => '',
            'logo_mode' => 'image',
            'logo_text_ar' => $shopName ?: 'My Store',
            'logo_text_en' => $shopName ?: 'My Store',
            'logo_color' => '#111827',
            'logo_bg_color' => '#ffffff',
            'logo_shape' => 'rectangle',
            'logo_font_ar' => 'cairo',
            'logo_font_en' => 'poppins',
            'logo_path_ar' => '',
            'logo_path_en' => '',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(
                ['name' => $key],
                ['value' => $value, 'group' => 'appearance']
            );
        }

        $this->syncDefaultBanners();
    }

    public function syncDefaultBanners(): void
    {
        // Only sync once — skip if the tenant already has banners.
        if (Banner::query()->exists()) {
            return;
        }

        $defaults = DefaultBanner::query()
            ->with('translations.language')
            ->orderBy('serial_number')
            ->orderBy('id')
            ->get();

        foreach ($defaults as $default) {
            $banner = Banner::query()->create([
                'url' => $default->url,
                'image_path' => $default->image_path,
                'serial_number' => $default->serial_number,
            ]);

            // Re-map translations: central locale code → tenant Language ID
            $translationRows = [];
            foreach ($default->translations as $translation) {
                $localeCode = $translation->language?->code;
                if (!$localeCode) {
                    continue;
                }
                $tenantLanguageId = \App\Models\Tenant\Language::query()
                    ->where('code', $localeCode)
                    ->value('id');
                if (!$tenantLanguageId) {
                    continue;
                }
                $translationRows[] = [
                    'language_id' => $tenantLanguageId,
                    'field' => $translation->field,
                    'value' => $translation->value,
                ];
            }

            if ($translationRows !== []) {
                $banner->translations()->createMany($translationRows);
            }
        }
    }

    /**
     * Submit a product name/description edit request to the central admin for review.
     *
     * @param  Product $product            The tenant product being edited
     * @param  array   $changedTranslations { locale => ['name' => ..., 'description' => ...] }
     * @param  array   $currentTranslations { locale => ['name' => ..., 'description' => ...] }
     */
    public function submitProductEditRequest(Product $product, array $changedTranslations, array $currentTranslations): void
    {
        $tenantId = tenant('id');
        tenancy()->central(function () use ($product, $changedTranslations, $currentTranslations, $tenantId) {
            ProductEditRequest::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'product_id' => $product->id,
                ],
                [
                    'product_slug' => $product->slug,
                    'requested_translations' => $changedTranslations,
                    'current_translations' => $currentTranslations,
                    'status' => 'pending',
                    'admin_note' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]
            );
        });
    }
}
