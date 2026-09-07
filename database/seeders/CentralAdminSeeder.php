<?php

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Enums\ContentStatus;
use App\Enums\DeliveryScope;
use App\Enums\PackageStatus;
use App\Enums\PackageTerm;
use App\Enums\ProductStatus;
use App\Enums\ShippingZoneStatus;
use App\Enums\VariationStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\AppSetting;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Language;
use App\Models\MaintenanceWindow;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductBadge;
use App\Models\ShippingZone;
use App\Models\ShippingZoneRate;
use App\Models\StaticPage;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\Tenant\AdminRole as TenantAdminRole;
use App\Models\Tenant\AdminUser as TenantAdminUser;
use App\Models\Variation;
use App\Models\VariationOption;
use App\Support\AdminPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;
use Throwable;

class CentralAdminSeeder extends Seeder
{
    protected function adminPermissions(): array
    {
        return AdminPermissions::all();
    }

    public function run(): void
    {
        // $catalog = $this->seedCatalog();
        $this->seedLanguageEnglish();

        $this->seedPackageStarter();
        $this->seedPackagePro();

        // $category = $this->seedCategoryElectronics($catalog);

//        $variation = $this->seedVariationSize();
//        $this->seedVariationOptionSmall($variation);
//        $this->seedVariationOptionMedium($variation);

        $zone = $this->seedShippingZoneGlobal();
        $this->seedShippingZoneRateStandard($zone);

//        $product = $this->seedProductDemo();
        // $this->seedProductCategories($product, $category);
//        $this->seedProductShippingZones($product, $zone);
//        $this->seedProductVariations($product, $variation);

        $adminRole = $this->seedAdminRoleSuperAdmin();
        $this->seedAdminUserCentralAdmin($adminRole);

        $blogCategory = $this->seedBlogCategoryAnnouncements();
        $this->seedBlogPostWelcome($blogCategory);

        $this->seedFaqPlanPayments();
        $this->seedStaticPageAbout();

        $this->seedAppSettings();
        $this->seedTemplates();
        $this->call(\Database\Seeders\CentralEmailTemplateSeeder::class);
        $this->seedMaintenanceWindowScheduled();
        $this->seedProductBadgeNewIn();
        $this->seedProductBadgeBestSelling();

        // $this->syncTenantsFromCentral();
    }

    protected function seedLanguages(): void
    {
        $this->seedLanguageEnglish();
    }

    protected function seedLanguageEnglish(): Language
    {
        return Language::query()->firstOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_default' => true, 'is_active' => true]
        );
    }

    protected function seedPackages(): void
    {
        $this->seedPackageStarter();
        $this->seedPackagePro();
    }

    protected function seedPackageStarter(): Package
    {
        $starter = Package::query()->firstOrCreate(
            ['name' => 'Starter'],
            ['status' => PackageStatus::Published->value, 'term' => PackageTerm::Monthly->value, 'price' => 29, 'trial_days' => 14]
        );
        $starter->syncTranslations(['en' => ['name' => 'Starter', 'title' => 'Starter', 'description' => 'Basic starter package for new stores.']]);

        return $starter;
    }

    protected function seedPackagePro(): Package
    {
        $pro = Package::query()->firstOrCreate(
            ['name' => 'Pro'],
            ['status' => PackageStatus::Published->value, 'term' => PackageTerm::Yearly->value, 'price' => 299, 'trial_days' => 30]
        );
        $pro->syncTranslations(['en' => ['name' => 'Pro', 'title' => 'Professional', 'description' => 'Advanced package for established tenants.']]);

        return $pro;
    }

    protected function seedVariations(): Variation
    {
        $variation = $this->seedVariationSize();
        $this->seedVariationOptionSmall($variation);
        $this->seedVariationOptionMedium($variation);

        return $variation;
    }

    protected function seedVariationSize(): Variation
    {
        $variation = Variation::query()->firstOrCreate(
            ['status' => VariationStatus::Active->value]
        );
        $variation->syncTranslations(['en' => ['name' => 'Size', 'title' => 'Size', 'description' => 'Available size options']]);

        return $variation;
    }

    protected function seedVariationOptionSmall(Variation $variation): VariationOption
    {
        $smallOption = VariationOption::query()->firstOrCreate(
            ['variation_id' => $variation->id],
            ['swatch' => null]
        );
        $smallOption->syncTranslations(['en' => ['name' => 'Small', 'title' => 'Small']]);

        return $smallOption;
    }

    protected function seedVariationOptionMedium(Variation $variation): VariationOption
    {
        $mediumOption = VariationOption::query()->firstOrCreate(
            ['variation_id' => $variation->id],
            ['swatch' => null]
        );
        $mediumOption->syncTranslations(['en' => ['name' => 'Medium', 'title' => 'Medium']]);

        return $mediumOption;
    }

    protected function seedShippingZones(): ShippingZone
    {
        $zone = $this->seedShippingZoneGlobal();
        $this->seedShippingZoneRateStandard($zone);

        return $zone;
    }

    protected function seedShippingZoneGlobal(): ShippingZone
    {
        $zone = ShippingZone::query()->firstOrCreate(
            ['code' => 'global'],
            ['name' => 'Global Zone', 'status' => ShippingZoneStatus::Active->value]
        );
        $zone->syncTranslations(['en' => ['name' => 'Global Zone', 'title' => 'Global Delivery', 'description' => 'Worldwide shipping zone for demo data.']]);

        return $zone;
    }

    protected function seedShippingZoneRateStandard(ShippingZone $zone)
    {
        return ShippingZoneRate::query()->firstOrCreate(
            ['shipping_zone_id' => $zone->id, 'name' => 'Standard'],
            ['min_weight' => 0, 'max_weight' => 1000, 'price' => 10, 'is_active' => true]
        );
    }

    protected function seedProducts(Category $category, Variation $variation, ShippingZone $zone): void
    {
        $product = $this->seedProductDemo();
        $this->seedProductCategories($product, $category);
        $this->seedProductShippingZones($product, $zone);
        $this->seedProductVariations($product, $variation);
    }

    protected function seedProductDemo(): Product
    {
        $product = Product::query()->firstOrCreate(
            ['sku' => 'DEMO-001'],
            ['slug' => 'demo-product', 'status' => ProductStatus::Published->value, 'delivery_scope' => DeliveryScope::AllZones->value, 'base_price' => 49.99, 'sale_price' => 39.99, 'stock' => 100, 'manage_stock' => true, 'is_taxable' => true, 'requires_shipping' => true, 'published_at' => now()]
        );
        $product->syncTranslations(['en' => ['name' => 'Demo Product', 'title' => 'Demo Product', 'summary' => 'Central demo product', 'description' => 'Demo product used to populate the central admin dashboard.']]);

        return $product;
    }

    protected function seedProductCategories(Product $product, Category $category): void
    {
        $product->categories()->syncWithoutDetaching([$category->id]);
    }

    protected function seedProductShippingZones(Product $product, ShippingZone $zone): void
    {
        $product->shippingZones()->syncWithoutDetaching([$zone->id]);
    }

    protected function seedProductVariations(Product $product, Variation $variation): void
    {
        $product->variations()->syncWithoutDetaching([$variation->id]);
    }

    protected function seedAdminUsers(): void
    {
        $adminRole = $this->seedAdminRoleSuperAdmin();
        $this->seedAdminUserCentralAdmin($adminRole);
    }

    protected function seedAdminRoleSuperAdmin(): AdminRole
    {
        return AdminRole::query()->updateOrCreate(
            ['name' => 'Super Admin'],
            ['permissions' => $this->adminPermissions(), 'permissions_count' => count($this->adminPermissions())]
        );
    }

    protected function seedAdminUserCentralAdmin(AdminRole $adminRole): AdminUser
    {
        $admin = AdminUser::query()->firstOrCreate(['email' => 'admin@admin.com'], ['role_id' => $adminRole->id, 'name' => 'Central Admin', 'status' => 'active', 'last_login_at' => now()]);
        if (!$admin->password) {
            $admin->forceFill(['password' => Hash::make('123456')])->save();
        }

        return $admin;
    }

    protected function seedBlogContent(): void
    {
        $blogCategory = $this->seedBlogCategoryAnnouncements();
        $this->seedBlogPostWelcome($blogCategory);
    }

    protected function seedBlogCategoryAnnouncements(): BlogCategory
    {
        $category = BlogCategory::query()->firstOrCreate(
            ['slug' => 'announcements'],
            ['status' => ContentStatus::Active->value]
        );
        $category->syncTranslations(['en' => ['name' => 'Announcements', 'slug' => 'announcements']]);

        return $category;
    }

    protected function seedBlogPostWelcome(BlogCategory $blogCategory): BlogPost
    {
        $post = BlogPost::query()->firstOrCreate(
            ['slug' => 'welcome-to-the-marketplace'],
            ['blog_category_id' => $blogCategory->id, 'status' => ContentStatus::Published->value, 'published_at' => now()->subDays(4)]
        );
        $post->syncTranslations([
            'en' => [
                'title' => 'Welcome to the Marketplace',
                'slug' => 'welcome-to-the-marketplace',
                'excerpt' => 'Initial central admin content post.',
                'content' => 'This is the first seeded blog post.',
            ]
        ]);

        return $post;
    }

    protected function seedFaqs(): void
    {
        $this->seedFaqPlanPayments();
    }

    protected function seedFaqPlanPayments(): Faq
    {
        $faq = Faq::query()
            ->whereHas(
                'translations',
                fn($q) => $q
                    ->where('field', 'question')
                    ->where('value', 'How do plan payments work?')
            )
            ->first() ?? Faq::query()->create(['status' => ContentStatus::Active->value]);

        $faq->syncTranslations([
            'en' => [
                'question' => 'How do plan payments work?',
                'answer' => 'Plan payments are tracked centrally for all tenants.',
                'category' => 'Billing',
            ]
        ]);

        return $faq;
    }

    protected function seedStaticPages(): void
    {
        $this->seedStaticPageAbout();
    }

    protected function seedStaticPageAbout(): StaticPage
    {
        $page = StaticPage::query()->firstOrCreate(
            ['slug' => 'about'],
            ['status' => ContentStatus::Active->value]
        );
        $page->syncTranslations([
            'en' => [
                'title' => 'About',
                'slug' => 'about',
                'content' => 'About the central marketplace.',
            ]
        ]);

        return $page;
    }

    protected function seedAppSettings(): void
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'Multi Vendor Central', 'type' => 'string', 'is_public' => false],
            ['key' => 'marketplace_name', 'value' => 'Marketplace HQ', 'type' => 'string', 'is_public' => true],
            ['key' => 'default_currency', 'value' => 'USD', 'type' => 'string', 'is_public' => true],
            ['key' => 'default_language', 'value' => 'en', 'type' => 'string', 'is_public' => true],
            ['key' => 'support_email', 'value' => 'support@example.com', 'type' => 'string', 'is_public' => true],
            ['key' => 'mail_mailer', 'value' => 'smtp', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_host', 'value' => 'smtp.example.com', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_port', 'value' => '587', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_username', 'value' => 'mailer@example.com', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_password', 'value' => 'secret-demo', 'type' => 'secret', 'is_public' => false],
            ['key' => 'mail_encryption', 'value' => 'tls', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_from_address', 'value' => 'noreply@example.com', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_from_name', 'value' => 'Marketplace HQ', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_queue', 'value' => '1', 'type' => 'boolean', 'is_public' => false],
            ['key' => 'header_logo', 'value' => asset('central-website/assets/image/4ad6eed1943b13bf773152f09ecf0d9ac498c5d3.png'), 'type' => 'string', 'is_public' => true],
            ['key' => 'footer_logo', 'value' => asset('central-website/assets/image/central-footer-logo.png'), 'type' => 'string', 'is_public' => true],
            ['key' => 'favicon', 'value' => '', 'type' => 'string', 'is_public' => true],
            ['key' => 'facebook_url', 'value' => '#', 'type' => 'string', 'is_public' => true],
            ['key' => 'twitter_url', 'value' => '#', 'type' => 'string', 'is_public' => true],
            ['key' => 'instagram_url', 'value' => '#', 'type' => 'string', 'is_public' => true],
            ['key' => 'youtube_url', 'value' => '#', 'type' => 'string', 'is_public' => true],
            ['key' => 'linkedin_url', 'value' => '#', 'type' => 'string', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            $this->seedAppSetting($setting);
        }
    }

    protected function seedAppSetting(array $setting): AppSetting
    {
        return AppSetting::query()->updateOrCreate(['key' => $setting['key']], $setting);
    }

    protected function seedTemplates(): void
    {
        $this->seedTemplateEcommet();
        $this->seedTemplateElora();
        $this->seedTemplateSouqify();
    }

    protected function seedTemplateEcommet(): Template
    {
        return Template::query()->firstOrCreate(['slug' => 'ecommet'], ['name' => 'Ecommet', 'version' => '1.0.0', 'author' => 'Internal Team', 'is_active' => true]);
    }

    protected function seedTemplateElora(): Template
    {
        return Template::query()->firstOrCreate(['slug' => 'elora'], ['name' => 'Elora', 'version' => '1.0.0', 'author' => 'Internal Team', 'is_active' => true]);
    }

    protected function seedTemplateSouqify(): Template
    {
        return Template::query()->firstOrCreate(['slug' => 'souqify'], ['name' => 'Souqify', 'version' => '1.0.0', 'author' => 'Internal Team', 'is_active' => true]);
    }

    protected function seedMaintenanceWindow(): void
    {
        $this->seedMaintenanceWindowScheduled();
    }

    protected function seedMaintenanceWindowScheduled(): MaintenanceWindow
    {
        return MaintenanceWindow::query()->firstOrCreate(['message' => 'Scheduled maintenance window.'], ['is_active' => false]);
    }

    protected function seedProductBadgeNewIn(): ProductBadge
    {
        return ProductBadge::query()->firstOrCreate(['text' => 'new-in'], ['active' => true]);
    }

    protected function seedProductBadgeBestSelling(): ProductBadge
    {
        return ProductBadge::query()->firstOrCreate(['text' => 'best-selling'], ['active' => true]);
    }

    protected function seedTenantAdminAccess(Tenant $tenant, string $password): void
    {
        $this->ensureTenantDatabase($tenant);

        tenancy()->initialize($tenant);

        try {
            $roles = [];

            foreach (TenantAdminRole::defaultRoleDefinitions() as $roleName => $permissions) {
                $roles[$roleName] = TenantAdminRole::query()->updateOrCreate(
                    ['name' => $roleName],
                    ['permissions' => $permissions, 'permissions_count' => count($permissions)]
                );
            }

            $owner = TenantAdminUser::query()->updateOrCreate(
                ['email' => $tenant->email],
                [
                    'role_id' => $roles[TenantAdminRole::STORE_OWNER]->id,
                    'name' => data_get($tenant->data, 'shop_name', $tenant->name),
                    'status' => 'active',
                    'password' => Hash::make($password),
                ]
            );

            TenantAdminUser::query()->firstOrCreate(
                ['email' => 'manager.' . $tenant->slug . '@example.com'],
                [
                    'role_id' => $roles[TenantAdminRole::MANAGER]->id,
                    'name' => data_get($tenant->data, 'shop_name', $tenant->name) . ' Manager',
                    'status' => 'active',
                    'password' => Hash::make('manager12345'),
                ]
            );

            TenantAdminUser::query()->firstOrCreate(
                ['email' => 'support.' . $tenant->slug . '@example.com'],
                [
                    'role_id' => $roles[TenantAdminRole::SUPPORT]->id,
                    'name' => data_get($tenant->data, 'shop_name', $tenant->name) . ' Support',
                    'status' => 'active',
                    'password' => Hash::make('support12345'),
                ]
            );
        } finally {
            tenancy()->end();
        }
    }

    protected function ensureTenantDatabase(Tenant $tenant): void
    {
        try {
            app()->call([new CreateDatabase($tenant), 'handle']);
        } catch (Throwable $exception) {
            if (!str_contains($exception->getMessage(), 'already exists')) {
                throw $exception;
            }
        }

        app()->call([new MigrateDatabase($tenant), 'handle']);
    }

    protected function syncTenantsFromCentral(): void
    {
        // $exitCode = Artisan::call('tenants:sync-data', [
        //     '--all' => true,
        //     '--ensure-database' => true,
        // ]);

        // if ($exitCode !== 0) {
        //     throw new RuntimeException('Tenant sync command failed after central seeding: ' . trim(Artisan::output()));
        // }

        // $this->command?->getOutput()->write(Artisan::output());
    }
}
