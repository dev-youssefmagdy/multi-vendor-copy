<?php

namespace Database\Seeders;

use App\Enums\Tenant\CouponType;
use App\Enums\TenantStatus;
use App\Models\Language;
use App\Models\Tenant;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Services\TenantService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * Creates (or re-seeds) a dedicated "preview" tenant used by the central admin
 * to preview any installed template via:
 *   https://preview.{central_domain}?_tpl={template_slug}
 *
 * Provisioning goes through the exact same {@see TenantService::save()} flow
 * used by a real store's "Complete Registration" submission (see
 * WebsiteRegistrationService::finalize()): tenant row, subdomain, tenant
 * database creation/migration, owner admin + roles + appearance defaults, and
 * a full central-catalog sync all run through that one path, so the preview
 * tenant is provisioned identically to a real tenant instead of by a
 * hand-rolled, drifting copy of that logic.
 *
 * The catalog/owner-admin sync normally happens on the queue (`tenant-sync`);
 * this seeder forces the sync queue driver for the duration of the call so
 * everything completes before the command returns.
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

    public function run(TenantService $tenantService): void
    {
        $centralDomain = config('tenancy.central_domains.0')
            ?: (parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');

        $previewDomain = 'preview.' . $centralDomain;

        // ── 1. Provision the tenant through the real registration flow ────────
        /** @var Tenant|null $existing */
        $existing = Tenant::query()->where('id', self::TENANT_ID)->first();
        $isNew = $existing === null;

        $tenant = $existing ?? new Tenant(['id' => self::TENANT_ID]);

        $primaryLanguage = Language::query()->orderBy('id')->first();

        $attributes = [
            'name' => 'Preview Store',
            'slug' => 'preview',
            'email' => 'preview@preview.local',
            'phone' => null,
            'status' => TenantStatus::Active->value,
            'category_ids' => null,
            'package_id' => null,
            'primary_language_id' => $primaryLanguage?->id,
            'trial_ends_at' => null,
            'shop_name' => 'Preview Store',
            'profit_percentage' => 0,
            // Owner admin is only ever used internally; never surfaced for login.
            'password' => Str::random(40),
        ];

        $this->command?->info($isNew ? 'Provisioning preview tenant…' : 'Preview tenant already exists — re-syncing.');

        // TenantService::save() dispatches SyncTenantOwnerAdminJob and
        // SyncTenantSectionsJob onto the `tenant-sync` queue; force the sync
        // driver so this command doesn't return before they've actually run.
        $previousQueueDefault = Config::get('queue.default');
        Config::set('queue.default', 'sync');

        try {
            $tenant = $tenantService->save($attributes, $tenant);
        } finally {
            Config::set('queue.default', $previousQueueDefault);
        }

        $this->command?->info("Preview tenant ready (ID: {$tenant->id}, domain: {$previewDomain}).");

        // ── 2. Seed demo data inside the tenant (preview-only, not part of the
        //      standard registration flow — makes the theme previews look
        //      populated rather than empty) ─────────────────────────────────
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

        if ($products->isEmpty()) {
            $this->command?->warn('No active products in preview tenant — skipping flash sale.');
            return;
        }

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

        $this->command?->info("Flash sale seeded with " . $products->count() . " products.");
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
