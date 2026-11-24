<?php

namespace App\Console\Commands;

use App\Enums\ActivationStatus;
use App\Enums\TenantStatus;
use App\Jobs\ApplyTenantProfitPercentageJob;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\CentralCoupon;
use App\Models\CentralFlashSale;
use App\Models\Product;
use App\Models\ProductBadge;
use App\Models\ProductLabel;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\Tenant\AdminRole as TenantAdminRole;
use App\Models\Tenant\AdminUser as TenantAdminUser;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Setting;
use App\Models\Variation;
use App\Models\VariationOption;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Services\TenantService;
use App\Support\AdminPermissions;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Dedup plan Phase 8 (browser E2E, dev / CI only): builds the dedicated E2E fixtures, idempotently.
 *
 *   php artisan e2e:prepare            create or refresh everything, print the fixture map as JSON
 *   php artisan e2e:prepare --fresh    drop and rebuild the two E2E tenants (their databases are deleted)
 *   php artisan e2e:prepare --teardown switch the E2E central coupon / flash sale off (end of a run)
 *
 * What it owns (never touches anything else):
 *  - central: admin role "E2E Admin" + admin `E2E_ADMIN_EMAIL`; category "E2E Gadgets"; variation "E2E Color";
 *    products `e2e-phone` (2 variants), `e2e-watch` (in the E2E flash sale), `e2e-stock` / `e2e-stock-central` (order
 *    lifecycle of the sync / central tenant);
 *    label "e2e-hot"; `featured` badge links of the E2E products only; coupon `E2E10`; one flash sale.
 *  - two tenants `e2e-sync` / `e2e-central` (domains e2e-sync.<central> / e2e-central.<central>), catalog limited
 *    to the E2E category, owner + limited staff admin + customer, one own product. `e2e-central` runs every
 *    catalog / config section on `central` (dedup migrate → verify → CatalogSource::set), `e2e-sync` stays on `sync`.
 *
 * Central rows are written without model events, so no catalog fan-out job reaches the real tenants.
 */
class E2ePrepareCommand extends Command
{
    protected $signature = 'e2e:prepare
        {--fresh : Delete and rebuild the E2E tenants}
        {--teardown : Switch the E2E central coupon and flash sale off and stop}
        {--force : Allow running outside local / testing environments}';

    protected $description = 'Phase 8: build the dedicated E2E tenants, users and catalog fixtures (dev / CI only)';

    public const TENANTS = ['sync' => 'e2e-sync', 'central' => 'e2e-central'];

    public const CATEGORY_KEY = 'e2e-gadgets';
    public const COUPON_CODE = 'E2E10';
    public const LABEL_TEXT = 'e2e-hot';
    public const FLASH_PERCENT = 20.0;

    /** Sections flipped to `central` for the central tenant (products last: it needs the others first). */
    private const CENTRAL_FLIP_ORDER = [
        'badges', 'labels', 'coupons', 'flash-sales', 'categories', 'products',
        'languages', 'currencies', 'settings', 'payment-gateways', 'themes', 'email-templates', 'pages',
    ];

    /** CatalogSource section => DedupRunner section. */
    private const DEDUP_SECTION = [
        'badges' => 'badges', 'labels' => 'labels', 'coupons' => 'coupons', 'flash-sales' => 'flash_sales',
        'categories' => 'categories', 'products' => 'products', 'languages' => 'languages', 'currencies' => 'currencies',
        'settings' => 'settings', 'payment-gateways' => 'payment_gateways', 'themes' => 'themes',
        'email-templates' => 'email_templates', 'pages' => 'pages',
    ];

    public function handle(TenantCatalogSyncService $sync, TenantService $tenantService, DedupRunner $dedup): int
    {
        if (! $this->option('force') && ! app()->environment(['local', 'testing', 'e2e'])) {
            $this->error('e2e:prepare only runs in local / testing / e2e environments (use --force on a disposable CI box).');

            return self::FAILURE;
        }

        if ($this->option('teardown')) {
            $this->teardown();

            return self::SUCCESS;
        }

        $central = $this->centralFixtures();

        $tenants = [];
        foreach (self::TENANTS as $mode => $id) {
            $tenants[$mode] = $this->tenantFixtures($mode, $id, $central, $sync, $tenantService, $dedup);
        }

        CacheVersion::bumpMany(CacheVersion::GLOBAL_TAGS);
        CacheVersion::flushMemo();

        $this->line(json_encode([
            'central_url' => rtrim((string) config('app.url'), '/'),
            'central' => $central,
            'tenants' => $tenants,
            'passwords' => 'E2E_PASSWORD (default e2e-Pass-12345)',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    public static function password(): string
    {
        return (string) (env('E2E_PASSWORD') ?: 'e2e-Pass-12345');
    }

    public static function centralDomain(): string
    {
        return (string) (env('E2E_CENTRAL_DOMAIN') ?: collect(config('tenancy.central_domains', []))
            ->first(fn ($d) => ! in_array($d, ['127.0.0.1', 'localhost'], true)));
    }

    // ─── Central ────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function centralFixtures(): array
    {
        $role = AdminRole::query()->updateOrCreate(['name' => 'E2E Admin'], [
            'permissions' => AdminPermissions::all(),
            'permissions_count' => count(AdminPermissions::all()),
        ]);
        $adminEmail = (string) (env('E2E_ADMIN_EMAIL') ?: 'e2e-admin@e2e.test');
        $admin = AdminUser::query()->firstOrNew(['email' => $adminEmail]);
        $admin->fill(['role_id' => $role->id, 'name' => 'E2E Admin', 'status' => ActivationStatus::Active]);
        // Re-hash only when needed: a new hash would invalidate the sessions the suite stored (login is throttled).
        if (! $admin->password || ! Hash::check(self::password(), $admin->password)) {
            $admin->password = Hash::make(self::password());
        }
        $admin->save();

        return Product::withoutEvents(fn () => Category::withoutEvents(fn () => ProductVariant::withoutEvents(fn () => CentralCoupon::withoutEvents(fn () => CentralFlashSale::withoutEvents(fn () => ProductLabel::withoutEvents(function () use ($admin) {
            $category = $this->centralCategory();
            [$variation, $options] = $this->centralVariation();

            $phone = $this->centralProduct('e2e-phone', 'E2E Phone', 'هاتف E2E', 100.00, 100, [
                ['sku' => 'E2E-PHONE-RED', 'title' => 'Red', 'price' => 100.00, 'stock' => 500, 'option' => $options['red']],
                ['sku' => 'E2E-PHONE-BLUE', 'title' => 'Blue', 'price' => 120.00, 'stock' => 500, 'option' => $options['blue']],
            ], $category, $variation);
            // Every central product carries at least one variant (as the real catalog does: synced simple products have no
            // tenant stock copy in `sync` mode).
            $watch = $this->centralProduct('e2e-watch', 'E2E Flash Watch', 'ساعة E2E', 50.00, 500, [
                ['sku' => 'E2E-WATCH-STD', 'title' => 'Standard', 'price' => 50.00, 'stock' => 500, 'option' => null],
            ], $category, null);
            // One order-lifecycle product per mode: both tenants move the same CENTRAL stock, so parallel mode projects
            // must not share it.
            $stock = $this->centralProduct('e2e-stock', 'E2E Stock Item', 'منتج مخزون E2E', 40.00, 30, [
                ['sku' => 'E2E-STOCK-STD', 'title' => 'Standard', 'price' => 40.00, 'stock' => 30, 'option' => null],
            ], $category, null);
            $stockCentral = $this->centralProduct('e2e-stock-central', 'E2E Stock Item C', 'منتج مخزون E2E C', 40.00, 30, [
                ['sku' => 'E2E-STOCKC-STD', 'title' => 'Standard', 'price' => 40.00, 'stock' => 30, 'option' => null],
            ], $category, null);

            // Tenant panel specs: one product the vendor overrides (price), one the tenant has NOT adopted yet.
            $override = $this->centralProduct('e2e-override', 'E2E Override Lamp', 'مصباح E2E', 60.00, 100, [
                ['sku' => 'E2E-OVR-STD', 'title' => 'Standard', 'price' => 60.00, 'stock' => 100, 'option' => null],
            ], $category, null);
            $adopt = $this->centralProduct('e2e-adopt', 'E2E Adopt Chair', 'كرسي E2E', 70.00, 100, [
                ['sku' => 'E2E-ADOPT-STD', 'title' => 'Standard', 'price' => 70.00, 'stock' => 100, 'option' => null],
            ], $category, null);

            // Central admin specs: one product the admin renames (live on the central tenant), one a vendor files an edit
            // request for.
            $adminEdit = $this->centralProduct('e2e-admin-edit', 'E2E Admin Kettle', 'غلاية E2E', 45.00, 100, [
                ['sku' => 'E2E-ADMIN-STD', 'title' => 'Standard', 'price' => 45.00, 'stock' => 100, 'option' => null],
            ], $category, null);
            $request = $this->centralProduct('e2e-request', 'E2E Request Vase', 'مزهرية E2E', 35.00, 100, [
                ['sku' => 'E2E-REQ-STD', 'title' => 'Standard', 'price' => 35.00, 'stock' => 100, 'option' => null],
            ], $category, null);

            $this->cleanupAdminSpecRows($watch->id, $adminEdit->id);

            // `featured` badge on the phone only (country_id NULL = every country); other badge links untouched.
            $featured = ProductBadge::query()->where('text', 'featured')->first();
            if ($featured) {
                // phone + watch: two rows for the per-country (GB) badge sort spec; GB rows reset to phone first
                foreach ([[$phone->id, 1], [$watch->id, 2]] as [$productId, $sort]) {
                    DB::table('product_badge_product')->updateOrInsert(
                        ['product_badge_id' => $featured->id, 'product_id' => $productId, 'country_id' => null],
                        ['sort_order' => $sort, 'created_at' => now(), 'updated_at' => now()],
                    );
                }
                // GB rows (only E2E products): the admin badge-sort spec reorders this country tab, never the default one
                $gb = DB::table('countries')->where('iso2', 'GB')->value('id');
                DB::table('product_badge_product')->where('product_badge_id', $featured->id)->where('country_id', $gb)->delete();
                foreach ([[$phone->id, 1], [$watch->id, 2]] as [$productId, $sort]) {
                    DB::table('product_badge_product')->insert(['product_badge_id' => $featured->id, 'product_id' => $productId,
                        'country_id' => $gb, 'sort_order' => $sort, 'created_at' => now(), 'updated_at' => now()]);
                }
            }

            $label = ProductLabel::withTrashed()->firstOrNew(['text' => self::LABEL_TEXT]);
            $label->fill(['translations' => ['en' => 'E2E Hot', 'ar' => 'E2E ساخن', 'fr' => 'E2E Chaud'], 'icon' => 'fire', 'active' => true, 'sort_order' => 99]);
            $label->deleted_at = null;
            $label->save();
            DB::table('product_label_product')->updateOrInsert(
                ['product_label_id' => $label->id, 'product_id' => $phone->id],
                ['created_at' => now(), 'updated_at' => now()],
            );

            $coupon = CentralCoupon::withTrashed()->firstOrNew(['code' => self::COUPON_CODE]);
            $coupon->fill(['name' => 'E2E central coupon', 'type' => 'percentage', 'value' => 10, 'start_date' => now()->subDay(),
                'end_date' => now()->addYears(5), 'minimum_spend' => 0, 'active' => true]);
            $coupon->deleted_at = null;
            $coupon->save();
            DB::table('central_coupon_country')->where('central_coupon_id', $coupon->id)->delete(); // no row = every country

            $flashId = DB::table('central_flash_sale_product')->where('product_id', $watch->id)->value('central_flash_sale_id');
            $flash = $flashId ? CentralFlashSale::withTrashed()->find($flashId) : new CentralFlashSale();
            $flash->fill(['discount_percentage' => self::FLASH_PERCENT, 'start_date' => now()->subDay(), 'end_date' => now()->addYears(5), 'active' => true]);
            $flash->deleted_at = null;
            $flash->save();
            DB::table('central_flash_sale_country')->where('central_flash_sale_id', $flash->id)->delete();
            DB::table('central_flash_sale_product')->updateOrInsert(
                ['central_flash_sale_id' => $flash->id, 'product_id' => $watch->id],
                ['created_at' => now(), 'updated_at' => now()],
            );

            return [
                'admin_email' => $admin->email,
                'category' => ['id' => $category->id, 'name' => 'E2E Gadgets'],
                'products' => [
                    'phone' => ['id' => $phone->id, 'slug' => $phone->slug, 'name' => 'E2E Phone', 'variants' => $phone->variants()->pluck('id', 'title')->all()],
                    'watch' => ['id' => $watch->id, 'slug' => $watch->slug, 'name' => 'E2E Flash Watch', 'variants' => $watch->variants()->pluck('id', 'title')->all()],
                    'stock' => ['id' => $stock->id, 'slug' => $stock->slug, 'name' => 'E2E Stock Item', 'variants' => $stock->variants()->pluck('id', 'title')->all()],
                    'override' => ['id' => $override->id, 'slug' => $override->slug, 'name' => 'E2E Override Lamp', 'variants' => $override->variants()->pluck('id', 'title')->all()],
                    'adopt' => ['id' => $adopt->id, 'slug' => $adopt->slug, 'name' => 'E2E Adopt Chair', 'variants' => $adopt->variants()->pluck('id', 'title')->all()],
                    'admin_edit' => ['id' => $adminEdit->id, 'slug' => $adminEdit->slug, 'name' => 'E2E Admin Kettle', 'variants' => $adminEdit->variants()->pluck('id', 'title')->all()],
                    'request' => ['id' => $request->id, 'slug' => $request->slug, 'name' => 'E2E Request Vase', 'variants' => $request->variants()->pluck('id', 'title')->all()],
                    'stock_central' => ['id' => $stockCentral->id, 'slug' => $stockCentral->slug, 'name' => 'E2E Stock Item C', 'variants' => $stockCentral->variants()->pluck('id', 'title')->all()],
                ],
                'label' => ['id' => $label->id, 'text' => self::LABEL_TEXT, 'name' => 'E2E Hot'],
                'badge' => ['id' => $featured?->id, 'text' => 'featured'],
                'coupon' => ['id' => $coupon->id, 'code' => self::COUPON_CODE, 'percent' => 10],
                'flash_sale' => ['id' => $flash->id, 'percent' => self::FLASH_PERCENT, 'product' => 'watch'],
            ];
        }))))));
    }

    /**
     * Rows the central admin specs create through the UI (prefix E2E-UI / E2EUI) and the edit requests of the E2E
     * tenants: soft-deleted / removed so every run starts from the same state and nothing leaks to real tenants.
     */
    private function cleanupAdminSpecRows(int $watchId, int $adminEditId): void
    {
        Product::query()->where('sku', 'like', 'E2E-UI-%')->get()->each->delete();
        foreach ([[Category::class, 'name'], [ProductLabel::class, null]] as [$model, $field]) {
            if ($field === null) {
                ProductLabel::query()->where('text', 'like', 'e2e-ui%')->get()->each->delete();
                continue;
            }
            $ids = DB::table('translations')->where('translatable_type', (new $model())->getMorphClass())->where('field', $field)
                ->where('value', 'like', 'E2E-UI%')->pluck('translatable_id');
            $model::query()->whereIn('id', $ids)->get()->each->delete();
        }
        CentralCoupon::query()->where('code', 'like', 'E2EUI%')->get()->each->delete();
        $saleIds = DB::table('central_flash_sale_product')->where('product_id', $adminEditId)->pluck('central_flash_sale_id');
        CentralFlashSale::query()->whereIn('id', $saleIds)->get()->each->delete();
        DB::table('product_edit_requests')->whereIn('tenant_id', array_values(self::TENANTS))->delete();
    }

    private function centralCategory(): Category
    {
        // Identified by its English slug translation (central categories have no slug column).
        $id = DB::table('translations')->where('translatable_type', (new Category())->getMorphClass())
            ->where('field', 'slug')->where('value', self::CATEGORY_KEY)->value('translatable_id');
        $category = $id ? Category::withTrashed()->find($id) : null;
        $category ??= new Category();
        $category->fill(['status' => 'published', 'is_featured' => true, 'order_number' => 900, 'parent_id' => null]);
        $category->deleted_at = null;
        $category->save();
        $category->syncTranslations([
            'en' => ['name' => 'E2E Gadgets', 'slug' => self::CATEGORY_KEY],
            'ar' => ['name' => 'أدوات E2E', 'slug' => self::CATEGORY_KEY . '-ar'],
            'fr' => ['name' => 'Gadgets E2E', 'slug' => self::CATEGORY_KEY . '-fr'],
        ]);

        return $category;
    }

    /** @return array{0: Variation, 1: array{red: VariationOption, blue: VariationOption}} */
    private function centralVariation(): array
    {
        $morph = (new Variation())->getMorphClass();
        $id = DB::table('translations')->where('translatable_type', $morph)->where('field', 'name')->where('value', 'E2E Color')->value('translatable_id');
        $variation = $id ? Variation::withTrashed()->find($id) : null;
        if (! $variation) {
            $variation = new Variation();
            $variation->forceFill(['status' => 'active'])->save();
        }
        $variation->syncTranslations(['en' => ['name' => 'E2E Color'], 'ar' => ['name' => 'لون E2E'], 'fr' => ['name' => 'Couleur E2E']]);

        $options = [];
        foreach (['red' => ['E2E Red', '#d00000'], 'blue' => ['E2E Blue', '#0033cc']] as $key => [$name, $swatch]) {
            $optMorph = (new VariationOption())->getMorphClass();
            $optId = DB::table('translations')->where('translatable_type', $optMorph)->where('field', 'name')->where('value', $name)->value('translatable_id');
            $option = $optId ? VariationOption::withTrashed()->find($optId) : null;
            $option ??= VariationOption::query()->create(['variation_id' => $variation->id, 'swatch' => $swatch]);
            $option->syncTranslations(['en' => ['name' => $name], 'ar' => ['name' => $name], 'fr' => ['name' => $name]]);
            $options[$key] = $option;
        }

        return [$variation, $options];
    }

    /** @param array<int, array{sku: string, title: string, price: float, stock: int, option: ?VariationOption}> $variants */
    private function centralProduct(string $slug, string $name, string $nameAr, float $price, int $stock, array $variants, Category $category, ?Variation $variation): Product
    {
        $product = Product::withTrashed()->firstOrNew(['slug' => $slug]);
        $product->fill([
            'sku' => strtoupper($slug),
            'status' => 'published',
            'base_price' => $price,
            'sale_price' => null,
            'cost_price' => null,
            'weight_grams' => 100,
            'fixed_shipping_costs' => null,
            'stock' => $variants === [] ? $stock : array_sum(array_column($variants, 'stock')),
            'manage_stock' => true,
            'requires_shipping' => true,
            'published_at' => now()->subDay(),
            'order_number' => 1,
        ]);
        $product->forceFill(['sold_count' => 0, 'deleted_at' => null, 'out_of_stock_at' => null]);
        $product->save();
        $product->syncTranslations([
            'en' => ['name' => $name, 'description' => "<p>{$name} description for the browser E2E suite.</p>", 'summary' => "{$name} summary"],
            'ar' => ['name' => $nameAr, 'description' => "<p>{$nameAr}</p>", 'summary' => $nameAr],
            'fr' => ['name' => "{$name} FR", 'description' => "<p>{$name} FR</p>", 'summary' => "{$name} FR"],
        ]);
        $product->categories()->syncWithoutDetaching([$category->id]);

        if ($variation) {
            $product->variations()->syncWithoutDetaching([$variation->id]);
        }
        foreach ($variants as $position => $row) {
            $variant = ProductVariant::withTrashed()->firstOrNew(['sku' => $row['sku']]);
            $variant->fill(['product_id' => $product->id, 'title' => $row['title'], 'price' => $row['price'], 'stock' => $row['stock'],
                'position' => $position, 'status' => 'active', 'weight_grams' => 100]);
            $variant->deleted_at = null;
            $variant->save();
            $variant->options()->sync($row['option'] ? [$row['option']->id] : []);
        }

        return $product->fresh(['variants']);
    }

    private function teardown(): void
    {
        CentralCoupon::withoutEvents(fn () => CentralCoupon::query()->where('code', self::COUPON_CODE)->update(['active' => false]));
        $watchId = Product::withTrashed()->where('slug', 'e2e-watch')->value('id');
        if ($watchId) {
            $ids = DB::table('central_flash_sale_product')->where('product_id', $watchId)->pluck('central_flash_sale_id');
            CentralFlashSale::withoutEvents(fn () => CentralFlashSale::query()->whereIn('id', $ids)->update(['active' => false]));
        }
        CacheVersion::bumpMany(['CentralCoupon', 'CentralFlashSale']);
        $this->info('E2E central coupon and flash sale switched off (run e2e:prepare again to re-enable).');
    }

    // ─── Tenants ────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function tenantFixtures(string $mode, string $id, array $central, TenantCatalogSyncService $sync, TenantService $tenantService, DedupRunner $dedup): array
    {
        $domain = "{$id}." . self::centralDomain();
        $tenant = Tenant::query()->find($id);

        if ($tenant && $this->option('fresh')) {
            tenancy()->end();
            $tenant->delete();
            $tenant = null;
        }

        $attributes = [
            'name' => "E2E {$mode}",
            'slug' => $id,
            'email' => "e2e-owner+{$mode}@e2e.test",
            'phone' => '+15550000000',
            'status' => TenantStatus::Active->value,
            'shop_name' => "E2E {$mode} store",
            'description' => 'Dedicated browser E2E tenant. Never used for real customers.',
            'address' => '1 Test Street, Test City',
            'account_type' => 'individual',
            'launch_ready' => true,
            'provision_status' => 'ready',
            'sync_status' => 'ready',
            'profit_percentage' => 25,
            'category_ids' => [$central['category']['id']],
            'country_ids' => DB::table('countries')->pluck('id')->map(fn ($v) => (int) $v)->all(),
            'primary_language_id' => DB::table('languages')->where('code', 'en')->value('id'),
            'activated_at' => now(),
        ];

        $isNew = $tenant === null;
        if ($isNew) {
            $this->info("Creating tenant {$id} (database + migrations)…");
            $tenant = Tenant::create(['id' => $id] + $attributes); // TenantCreated pipeline: create + migrate DB
        } else {
            $tenant->fill($attributes);
            $tenant->save();
            $this->call('tenants:migrate', ['--tenants' => [$id], '--force' => true]);
        }
        Domain::query()->firstOrCreate(['domain' => $domain], ['tenant_id' => $id]);
        // Store countries (per-country badge tabs, "sold in" pickers): GB (the specs' visitor country), US, EG.
        foreach (DB::table('countries')->whereIn('iso2', ['GB', 'US', 'EG'])->pluck('id') as $countryId) {
            \App\Models\TenantCountry::query()->updateOrCreate(['tenant_id' => $id, 'country_id' => $countryId], ['is_active' => true]);
        }
        tenancy()->end();

        // Approved vendor edit requests of earlier runs (field overrides) are dropped: the copies follow central again.
        $tenant->run(function () {
            if (Schema::hasTable('tenant_field_overrides')) {
                DB::table('tenant_field_overrides')->delete();
            }
            if (Schema::hasColumn('products', 'has_custom_translations')) {
                DB::table('products')->update(['has_custom_translations' => false]);
            }
        });
        tenancy()->end();

        // Rebuild on `sync` first (the copies are what the dedup migrate reads), then flip the central tenant.
        foreach (CatalogSource::SECTIONS as $section) {
            CatalogSource::set($tenant, $section, CatalogSource::SYNC);
        }
        $tenant->refresh();

        $this->info("Syncing catalog into {$id}…");
        foreach (['languages', 'currencies', 'settings', 'payment-gateways', 'themes', 'email-templates', 'pages', 'categories', 'products', 'badges', 'labels', 'flash-sales', 'coupons'] as $section) {
            $sync->syncSection($tenant, $section, initialCatalog: true);
            tenancy()->end();
        }
        ApplyTenantProfitPercentageJob::dispatchSync($tenant->id);
        tenancy()->end();

        // a fresh tenant DB has no owner yet: admins.password is NOT NULL, so the first insert needs it
        $tenantService->syncTenantOwnerAdmin($tenant, ['email' => $attributes['email'], 'name' => 'E2E Owner', 'shop_name' => $attributes['shop_name'], 'password' => self::password()]);
        tenancy()->end();

        $own = $tenant->run(fn () => $this->seedTenant($tenant, $central));
        tenancy()->end();

        if ($mode === 'central') {
            $this->flipToCentral($tenant, $dedup);
        }

        // Only E2E products may be carried (a stray adoption, e.g. from a failed spec, would leak real catalog rows in).
        $e2eIds = array_map(fn ($p) => (int) $p['id'], $central['products']);
        $tenant->run(function () use ($e2eIds) {
            foreach (['tenant_product_variant_overrides', 'tenant_product_overrides'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->whereNotIn('central_product_id', $e2eIds)->delete();
                }
            }
        });
        tenancy()->end();

        // `e2e-adopt` must be a "new" (not yet adopted) central product for the adopt spec.
        $adoptId = (int) $central['products']['adopt']['id'];
        $tenant->run(function () use ($mode, $adoptId) {
            if ($mode === 'central') {
                app(\App\Repositories\Tenant\Catalog\ProductCatalog::class)->release($adoptId);
                DB::table('tenant_category_product_overrides')->where('product_type', 'central')->where('product_id', $adoptId)->delete();
            }
            $copyIds = DB::table('products')->where('central_product_id', $adoptId)->pluck('id');
            DB::table('category_product')->whereIn('product_id', $copyIds)->delete();
            DB::table('product_variants')->whereIn('product_id', $copyIds)->delete();
            DB::table('products')->whereIn('id', $copyIds)->delete();
            CacheVersion::bumpMany(['Product', 'ProductVariant', 'Category']);
        });
        tenancy()->end();

        CacheVersion::flushMemo();
        $tenant->run(fn () => CacheVersion::bumpStorefront());
        tenancy()->end();

        // Presentation key of the E2E category: tenant copy id on `sync`, CategoryKey (= central id) on `central`.
        $categoryKey = $mode === 'central'
            ? (int) $central['category']['id']
            : (int) $tenant->run(fn () => DB::table('categories')->where('central_category_id', $central['category']['id'])->value('id'));
        tenancy()->end();

        return [
            'id' => $id,
            'mode' => $mode,
            'category_key' => $categoryKey,
            'domain' => $domain,
            'url' => 'http://' . $domain,
            'owner_email' => $attributes['email'],
            'staff_email' => "e2e-staff+{$mode}@e2e.test",
            'customer_email' => "e2e-customer+{$mode}@e2e.test",
            'own_product' => $own,
            'catalog_source' => CatalogSource::map($tenant->fresh()),
        ];
    }

    /** @return array{id: int, slug: string, name: string} */
    private function seedTenant(Tenant $tenant, array $central): array
    {
        $mode = $tenant->id === self::TENANTS['central'] ? 'central' : 'sync';

        foreach ([
            'logo_mode' => 'text', 'logo_text_en' => "E2E {$mode}", 'logo_text_ar' => "E2E {$mode}",
            'default_pages_reviewed_at' => now()->toDateTimeString(),
            'compliance_business_name' => 'E2E Business', 'compliance_store_name' => 'E2E Store', 'compliance_country' => 'GB',
            'compliance_city' => 'London', 'compliance_phone' => '+15550000000', 'compliance_email' => 'e2e@e2e.test',
            'compliance_owner_name' => 'E2E Owner', 'compliance_owner_id_number' => 'E2E-1', 'compliance_registration_number' => 'E2E-REG',
            'compliance_bank_name' => 'E2E Bank', 'compliance_bank_holder_name' => 'E2E Owner', 'compliance_bank_account_number' => '0001',
            'compliance_bank_iban' => 'GB00E2E0000000', 'compliance_doc_national_id_path' => 'compliance/e2e.pdf',
        ] as $name => $value) {
            Setting::query()->updateOrCreate(['name' => $name], ['value' => $value, 'type' => 'string', 'group' => str_starts_with($name, 'compliance_') ? 'compliance' : 'general']);
        }

        // Theme: elora (default theme of the starter kit tenants), active languages en / ar / fr.
        DB::table('themes')->update(['is_active' => false]);
        DB::table('themes')->where('slug', 'elora')->update(['is_active' => true]);
        DB::table('languages')->whereIn('code', ['en', 'ar', 'fr'])->update(['is_active' => true]);
        DB::table('languages')->update(['is_default' => false]);
        DB::table('languages')->where('code', 'en')->update(['is_default' => true]);
        foreach (['USD', 'EUR', 'GBP'] as $code) {
            DB::table('currencies')->where('code', $code)->update(['is_active' => true]);
        }

        // A storefront gateway must be "ready" for checkout to accept orders; the E2E orders are COD.
        $gateway = \App\Models\Tenant\PaymentGateway::query()->where('code', 'stripe')->first();
        if ($gateway) {
            $gateway->forceFill([
                'is_active' => true, 'use_own' => true, 'connection_status' => 'connected', 'hide' => false,
                'required_values' => ['public_key' => 'pk_test_e2e', 'secret_key' => 'sk_test_e2e'],
            ])->save();
        }

        $owner = TenantAdminUser::query()->where('email', "e2e-owner+{$mode}@e2e.test")->first();
        if ($owner && (! $owner->password || ! Hash::check(self::password(), $owner->password))) {
            $owner->password = Hash::make(self::password());
            $owner->save();
        }

        // Limited staff role (dashboard only) → panel catalog screens must answer 403.
        $limited = TenantAdminRole::query()->updateOrCreate(['name' => 'E2E Limited'], ['permissions' => ['dashboard.view'], 'permissions_count' => 1]);
        $staff = TenantAdminUser::query()->firstOrNew(['email' => "e2e-staff+{$mode}@e2e.test"]);
        $staff->fill(['role_id' => $limited->id, 'name' => 'E2E Staff', 'status' => ActivationStatus::Active->value]);
        if (! $staff->password || ! Hash::check(self::password(), $staff->password)) {
            $staff->password = Hash::make(self::password());
        }
        $staff->save();
        // No onboarding tour / compliance modal in front of the panel screens.
        $seen = ['email_verified_at' => now()];
        foreach (['tour_seen_at', 'setup_dismissed_at', 'payment_readiness_skipped_at'] as $column) {
            if (Schema::hasColumn('admins', $column)) {
                $seen[$column] = now();
            }
        }
        TenantAdminUser::query()->whereIn('email', ["e2e-owner+{$mode}@e2e.test", "e2e-staff+{$mode}@e2e.test"])->update($seen);
        app(\App\Services\Tenant\ComplianceService::class)->accept();

        $customer = Customer::query()->firstOrNew(['email' => "e2e-customer+{$mode}@e2e.test"]);
        $customer->fill(['full_name' => 'E2E Customer', 'phone' => '+447700900123', 'active' => true, 'language' => 'en',
            'country_id' => DB::connection(config('tenancy.database.central_connection'))->table('countries')->where('iso2', 'GB')->value('id')]);
        if (! $customer->password || ! Hash::check(self::password(), $customer->password)) {
            $customer->password = Hash::make(self::password());
        }
        $customer->save();
        if (Schema::hasColumn('customers', 'email_verified_at')) {
            DB::table('customers')->whereKey($customer->id)->update(['email_verified_at' => now()]);
        }
        $gb = DB::connection(config('tenancy.database.central_connection'))->table('countries')->where('iso2', 'GB')->first(['id', 'name']);
        DB::table('customer_addresses')->updateOrInsert(['customer_id' => $customer->id, 'label' => 'E2E Home'], [
            'full_name' => 'E2E Customer', 'email' => $customer->email, 'phone' => '+447700900123', 'address_line_1' => '10 Downing Street',
            'city' => 'London', 'state' => null, 'country' => $gb?->name, 'country_id' => $gb?->id, 'is_default' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Rows earlier spec runs created and did not delete (own categories / products / coupons): start clean.
        $mugId = DB::table('products')->where('slug', 'e2e-own-mug')->value('id');
        $strayProducts = DB::table('products')->where('is_own_product', true)->when($mugId, fn ($q) => $q->where('id', '!=', $mugId))->pluck('id');
        DB::table('category_product')->whereIn('product_id', $strayProducts)->delete();
        DB::table('product_variants')->whereIn('product_id', $strayProducts)->delete();
        DB::table('products')->whereIn('id', $strayProducts)->delete();
        $ownCategories = DB::table('categories')->whereNull('central_category_id')->pluck('id');
        DB::table('category_product')->whereIn('category_id', $ownCategories)->delete();
        DB::table('categories')->whereIn('id', $ownCategories)->delete();
        DB::table('coupons')->whereNull('central_coupon_id')->delete();

        // One own product (tenant row) for the mixed cart / own CRUD checks.
        $own = \App\Models\Tenant\Product::withoutGlobalScopes()->firstOrNew(['slug' => 'e2e-own-mug']);
        $own->forceFill([
            'sku' => 'E2E-OWN-MUG', 'price' => ['default' => 30], 'default_price' => 30, 'active' => true, 'is_own_product' => true,
            'is_tenant_owned' => true, 'requires_shipping' => true, 'stock' => 200, 'manage_stock' => true, 'central_visible' => true,
        ])->save();
        $own->syncTranslations(['en' => ['name' => 'E2E Own Mug', 'description' => 'E2E own product']]);

        return ['id' => $own->id, 'slug' => 'e2e-own-mug', 'name' => 'E2E Own Mug'];
    }

    private function flipToCentral(Tenant $tenant, DedupRunner $dedup): void
    {
        $this->info("Migrating {$tenant->id} to central reads…");
        foreach (self::CENTRAL_FLIP_ORDER as $section) {
            $dedupSection = self::DEDUP_SECTION[$section];
            $migrate = $dedup->run($tenant, $dedupSection, 'migrate', ['accept_stale' => true, 'remap_carts' => false, 'reverse' => false]);
            tenancy()->end();
            if ($section === 'badges') {
                $dedup->run($tenant, 'field_overrides', 'migrate', ['accept_stale' => true, 'remap_carts' => false, 'reverse' => false]);
                tenancy()->end();
            }
            $verify = $dedup->run($tenant, $dedupSection, 'verify', ['accept_stale' => false, 'remap_carts' => false, 'reverse' => false]);
            tenancy()->end();
            if (($migrate['status'] ?? '') === 'failed' || ($verify['diffs'] ?? 0) > 0) {
                $this->warn("  {$section}: migrate={$migrate['status']} verify diffs=" . ($verify['diffs'] ?? '?') . ' (flipped anyway: E2E tenant)');
            }
            CatalogSource::set($tenant, $section, CatalogSource::CENTRAL);
        }
    }
}
