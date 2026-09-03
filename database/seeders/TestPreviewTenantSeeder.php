<?php

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Models\Language;
use App\Models\Tenant;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Enums\Tenant\CouponType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use RuntimeException;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Creates (or re-seeds) a dedicated "preview" tenant used by the central admin
 * to preview any installed template via:
 *   https://preview.{central_domain}?_tpl={template_slug}
 *
 * Usage:
 *   php artisan db:seed --class=TestPreviewTenantSeeder
 *
 * This seeder is idempotent — running it multiple times will not create
 * duplicate tenants, badges, flash-sales, or coupons.
 */
class TestPreviewTenantSeeder extends Seeder
{
    /** Fixed UUID so the seeder is fully idempotent. */
    private const TENANT_ID = 'preview00-0000-4000-a000-000000000001';

    public function run(): void
    {
        $centralDomain = config('tenancy.central_domains.0')
            ?: (parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');

        $previewDomain = 'preview.' . $centralDomain;

        // ── 1. Ensure tenant exists ───────────────────────────────────────────
        /** @var Tenant $tenant */
        $tenant = Tenant::query()->where('id', self::TENANT_ID)->first();

        if (!$tenant) {
            $primaryLanguage = Language::query()->orderBy('id')->first();

            $tenant = new Tenant(['id' => self::TENANT_ID]);
            $tenant->fill([
                'name' => 'Preview Store',
                'slug' => 'preview',
                'email' => 'preview@preview.local',
                'phone' => null,
                'status' => TenantStatus::Active->value,
                'catalog_id' => null,
                'all_catalogs' => true,
                'primary_language_id' => $primaryLanguage?->id,
                'package_id' => null,
                'activated_at' => now(),
                'profit_percentage' => 0,
                'data' => ['shop_name' => 'Preview Store', 'is_preview_tenant' => true],
            ]);
            $tenant->saveQuietly();

            $this->command?->info("Preview tenant created (ID: " . self::TENANT_ID . ").");
        } else {
            $this->command?->info("Preview tenant already exists — re-seeding.");
        }

        // ── 2. Ensure domain mapping ──────────────────────────────────────────
        Domain::query()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['domain' => $previewDomain]
        );

        $this->command?->info("Preview domain: {$previewDomain}");

        // ── 3. Sync all central data (languages, currencies, themes, products …)
        $this->command?->info("Syncing central catalog data into preview tenant…");

        $exitCode = Artisan::call('tenants:sync-data', [
            'tenant' => $tenant->id,
            '--ensure-database' => true,
        ]);

        $output = trim(Artisan::output());
        if ($output) {
            $this->command?->getOutput()->write($output . "\n");
        }

        if ($exitCode !== 0) {
            throw new RuntimeException("Tenant sync failed for preview tenant. Exit code: {$exitCode}");
        }

        // ── 4. Seed demo data inside the tenant ───────────────────────────────
        tenancy()->initialize($tenant);

        try {
            $this->seedBadges();
            $this->seedFlashSales();
            $this->seedCoupons();
        } finally {
            tenancy()->end();
        }

        $this->command?->info("Preview tenant fully seeded. Preview URL:");
        $scheme = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_SCHEME) ?: 'http';
        $this->command?->line("  {$scheme}://{$previewDomain}?_tpl=elora");
        $this->command?->line("  {$scheme}://{$previewDomain}?_tpl=souqify");
        $this->command?->line("  {$scheme}://{$previewDomain}?_tpl=ecommet");
    }

    // ── Badge seeding ─────────────────────────────────────────────────────────

    protected function seedBadges(): void
    {
        $newIn = ProductBadge::query()->firstOrCreate(
            ['text' => 'new-in'],
            ['active' => true]
        );

        $bestSelling = ProductBadge::query()->firstOrCreate(
            ['text' => 'best-selling'],
            ['active' => true]
        );

        // Ensure both badges are active
        $newIn->update(['active' => true]);
        $bestSelling->update(['active' => true]);

        $allProducts = Product::query()
            ->where('active', true)
            ->orderByDesc('created_at')
            ->get();

        if ($allProducts->isEmpty()) {
            $this->command?->warn('No active products in preview tenant — skipping badge assignment.');
            return;
        }

        // "new-in": 10 most recently synced products
        $recentIds = $allProducts->take(10)->pluck('id')->all();
        $newIn->products()->syncWithoutDetaching($recentIds);

        // "best-selling": up to 8 products that don't already have new-in
        $bestSellingIds = $allProducts
            ->filter(fn($p) => !in_array($p->id, $recentIds, true))
            ->take(8)
            ->pluck('id')
            ->all();

        if (!empty($bestSellingIds)) {
            $bestSelling->products()->syncWithoutDetaching($bestSellingIds);
        }

        $this->command?->info("Badges seeded — new-in: " . count($recentIds) . ", best-selling: " . count($bestSellingIds) . ".");
    }

    // ── Flash-sale seeding ────────────────────────────────────────────────────

    protected function seedFlashSales(): void
    {
        $products = Product::query()
            ->where('active', true)
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $count = 0;

        $fs = FlashSale::query()->create(
            [
                'product_id' => $products->first()->id,
                'discount_percentage' => 20,
                'start_date' => now()->subDay(),
                'end_date' => now()->addYears(5), // well into the future
                'active' => true,
            ]
        );

        $fs->products()->attach($products->pluck('id')->all());


        $this->command?->info("Flash sales seeded: {$count}.");
    }

    // ── Coupon seeding ────────────────────────────────────────────────────────

    protected function seedCoupons(): void
    {
        $coupons = [
            [
                'code' => 'PREVIEW10',
                'type' => CouponType::Percentage,
                'value' => 10.00,
                'minimum_spend' => 0,
                'start_date' => now()->subDay(),
                'end_date' => now()->setYear(2030)->endOfYear(),
            ],
            [
                'code' => 'PREVIEW25',
                'type' => CouponType::Percentage,
                'value' => 25.00,
                'minimum_spend' => 50.00,
                'start_date' => now()->subDay(),
                'end_date' => now()->setYear(2030)->endOfYear(),
            ],
            [
                'code' => 'FLAT15',
                'type' => CouponType::Fixed,
                'value' => 15.00,
                'minimum_spend' => 30.00,
                'start_date' => now()->subDay(),
                'end_date' => now()->setYear(2030)->endOfYear(),
            ],
            [
                'code' => 'WELCOME5',
                'type' => CouponType::Fixed,
                'value' => 5.00,
                'minimum_spend' => 0,
                'start_date' => now()->subDay(),
                'end_date' => now()->setYear(2030)->endOfYear(),
            ],
        ];

        $count = 0;
        foreach ($coupons as $data) {
            $coupon = Coupon::query()->firstOrCreate(
                ['code' => $data['code']],
                [
                    'type' => $data['type'],
                    'value' => $data['value'],
                    'minimum_spend' => $data['minimum_spend'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                ]
            );

            // Ensure expiry is far away even if the record already existed
            if ($coupon->end_date?->isPast()) {
                $coupon->update(['end_date' => now()->setYear(2030)->endOfYear()]);
            }

            $count++;
        }

        $this->command?->info("Coupons seeded: {$count}.");
    }
}
