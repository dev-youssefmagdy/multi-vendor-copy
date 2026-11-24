<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Repositories\Tenant\Catalog\CategoryCatalog;
use App\Repositories\Tenant\Catalog\CouponCatalog;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Support\Catalog\CatalogSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Dedup rollout QA (browser E2E `vendor` project, dev / CI only): READ-ONLY JSON snapshot of a real tenant, so the
 * non-destructive Playwright smoke suite discovers its fixtures (products, category, coupon, languages, gateways,
 * pages) instead of hard-coding them. Writes nothing.
 *
 *   php artisan e2e:vendor-probe <tenant-id>
 */
class E2eVendorProbeCommand extends Command
{
    protected $signature = 'e2e:vendor-probe {tenant}';

    protected $description = 'Dedup rollout QA: read-only fixture snapshot of a real tenant for the vendor E2E smoke suite';

    public function handle(): int
    {
        $tenant = Tenant::query()->with('domains')->find((string) $this->argument('tenant'));
        if (! $tenant) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        $out = $tenant->run(function () use ($tenant) {
            $products = ProductCatalog::query()->where('active', 1)->withCount('variants')->get();
            $pick = fn ($rows) => $rows->map(fn ($p) => [
                'id' => (int) $p->getKey(),
                'slug' => (string) $p->slug,
                'name' => (string) ($p->t('name', 'en') ?? ''),
                'source' => (string) ($p->source ?? 'tenant'),
                'variants' => (int) $p->variants_count,
            ])->values()->all();

            // E2E fixture rows (central `e2e-*` products / category, E2E* coupons) must never be picked as real-tenant
            // fixtures; they are reported under `e2e_contamination` instead (they leaked in through earlier E2E runs).
            $isE2e = fn ($slug) => str_starts_with(strtolower((string) $slug), 'e2e');
            $e2eProducts = $products->filter(fn ($p) => $isE2e($p->slug));
            $products = $products->reject(fn ($p) => $isE2e($p->slug));

            $withVariants = $products->filter(fn ($p) => $p->variants_count > 1 && $p->variants_count <= 12)->take(3);
            $central = $products->where('source', 'central')->take(3);
            $own = $products->where('source', 'tenant')->take(3);

            $allCategories = CategoryCatalog::query()->get()->filter(fn ($c) => (bool) $c->active);
            $e2eCategories = $allCategories->filter(fn ($c) => $isE2e($c->slug))->map(fn ($c) => (string) $c->slug)->values()->all();
            $categories = $allCategories->reject(fn ($c) => $isE2e($c->slug))
                ->map(fn ($c) => ['id' => (int) $c->getKey(), 'slug' => (string) $c->slug, 'name' => (string) ($c->t('name', 'en') ?? '')])
                ->values()->all();

            $allCoupons = (new CouponCatalog())->all();
            $e2eCoupons = $allCoupons->filter(fn ($c) => $isE2e($c->code))->pluck('code')->values()->all();
            $coupons = $allCoupons->reject(fn ($c) => $isE2e($c->code))->map(fn ($c) => [
                'code' => (string) $c->code,
                'name' => (string) ($c->name ?? $c->code),
                'source' => $c->isCentral() ? 'central' : 'tenant',
                'valid' => (bool) (new CouponCatalog())->findValidByCode($c->code),
            ])->values()->all();

            return [
                'e2e_contamination' => ['products' => $e2eProducts->pluck('slug')->values()->all(), 'categories' => $e2eCategories, 'coupons' => $e2eCoupons],
                'product_count' => $products->count(),
                'central_product_count' => $products->where('source', 'central')->count(),
                'own_product_count' => $products->where('source', 'tenant')->count(),
                'products_with_variants' => $pick($withVariants),
                'central_products' => $pick($central),
                'own_products' => $pick($own),
                'categories' => $categories,
                'coupons' => $coupons,
                'languages' => \App\Models\Tenant\Language::query()->where('is_active', 1)->pluck('code')->values()->all(),
                'gateways' => \App\Models\Tenant\PaymentGateway::query()->where('is_active', 1)->pluck('name')->values()->all(),
                'pages' => \App\Models\Tenant\Page::query()->pluck('slug')->values()->all(),
                'email_templates' => \App\Models\Tenant\EmailTemplate::query()->count(),
                'currencies_active' => \App\Models\Tenant\Currency::query()->where('is_active', 1)->count(),
                'orders' => DB::table('orders')->count(),
            ];
        });
        tenancy()->end();

        $out['id'] = (string) $tenant->id;
        $out['domain'] = (string) ($tenant->domains->first()?->domain ?? '');
        $out['slug'] = (string) ($tenant->slug ?? '');
        $out['catalog_source'] = CatalogSource::map($tenant);

        $this->line(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
