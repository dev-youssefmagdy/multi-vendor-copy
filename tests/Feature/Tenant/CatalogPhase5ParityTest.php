<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Product as CentralProduct;
use App\Models\ProductVariant as CentralVariant;
use App\Models\Tenant\Catalog\MergedProduct;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\ProductKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Files;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 5, price correctness first: for the same seed, the merged (central) read must equal the synced (sync)
 * read: storefrontPricing() of product + variant per country, the SQL country price expression vs the stored per-country
 * `price` JSON, stock, active, and no N+1 growth of the product-card eager loads.
 */
class CatalogPhase5ParityTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        CatalogRefColumns::flush();
        $this->setUpTenantPanel();
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
            if (Schema::hasTable('catalog_dedup_runs')) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->delete();
            }
            foreach (['migrate', 'dry-run', 'verify'] as $step) {
                Files::delete(DedupRunner::reportPath('products', (string) $this->tenant->id, $step));
            }
        }
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    /** @return array{central: CentralProduct, variant: CentralVariant, product: \App\Models\Tenant\Product} synced through the real sync service */
    private function seedSynced(float $price = 100.0, float $sale = 80.0, array $costs = ['5' => 7.0]): array
    {
        $x = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: $price));
        tenancy()->end();
        CentralProduct::withoutEvents(function () use ($x, $sale, $costs) {
            CentralProduct::query()->whereKey($x['central_product']->id)->update(['sale_price' => $sale, 'weight_grams' => 500, 'fixed_shipping_costs' => json_encode($costs)]);
            CentralVariant::query()->whereKey($x['central_variant']->id)->update(['price' => 60]);
        });
        $this->tenant->profit_percentage = 15;
        $this->tenant->save();
        app(TenantCatalogSyncService::class)->syncProductToTenant(CentralProduct::query()->findOrFail($x['central_product']->id), $this->tenant, true);
        tenancy()->end();

        return ['central' => $x['central_product'], 'variant' => $x['central_variant'], 'product' => $x['product']];
    }

    private function flipAll(): void
    {
        tenancy()->end();
        foreach (['badges', 'labels', 'flash-sales', 'categories', 'products'] as $section) {
            CatalogSource::set($this->tenant, $section, CatalogSource::CENTRAL);
        }
        CacheVersion::flushMemo();
    }

    /** @return array<string, mixed> comparable storefront pricing (the flash sale object dropped) */
    private function pricing($product, ?int $country): array
    {
        $variant = $product->variants->first();

        return [
            'product' => array_diff_key($product->storefrontPricing(null, $country), ['flash_sale' => 1]),
            'variant' => array_diff_key($product->storefrontPricing($variant, $country), ['flash_sale' => 1]),
        ];
    }

    public function test_central_mode_storefront_pricing_equals_sync_mode_for_every_country(): void
    {
        $x = $this->seedSynced();
        $countries = [null, 5, 9];

        $sync = $this->tenant->run(function () use ($x, $countries) {
            $product = \App\Models\Tenant\Product::query()->where('central_product_id', $x['central']->id)->with(['variants', 'centralProduct'])->firstOrFail();
            $out = ['stock' => (int) $product->stock, 'active' => (bool) $product->active, 'prices' => []];
            foreach ($countries as $c) {
                $out['prices'][(string) $c] = $this->pricing($product, $c);
            }

            return $out;
        });

        tenancy()->end();
        $this->assertSame('done', (new DedupRunner())->run($this->tenant, 'products', 'migrate')['status']);
        $verify = (new DedupRunner())->run($this->tenant, 'products', 'verify');
        $this->assertSame(0, $verify['diffs'], json_encode($verify['examples'] ?? []));
        $this->flipAll();

        $central = $this->tenant->run(function () use ($x, $countries) {
            $this->assertTrue(ProductCatalog::enabled());
            $product = ProductCatalog::query()->where('central_product_id', $x['central']->id)->with(['variants', 'centralProduct'])->firstOrFail();
            $this->assertInstanceOf(MergedProduct::class, $product);
            $this->assertSame($x['central']->id, (int) $product->id);
            $out = ['stock' => (int) $product->stock, 'active' => (bool) $product->active, 'prices' => []];
            foreach ($countries as $c) {
                $out['prices'][(string) $c] = $this->pricing($product, $c);
            }

            return $out;
        });

        $this->assertSame($sync['stock'], $central['stock']);
        $this->assertSame($sync['active'], $central['active']);
        $this->assertEquals($sync['prices'], $central['prices']);
        // Non-trivial seed: a shipping cost for country 5 and the tenant's 15% profit must be in the price.
        $this->assertGreaterThan($central['prices']['']['product']['price'], $central['prices']['5']['product']['price']);
    }

    public function test_sql_country_price_expression_equals_the_stored_per_country_price_key(): void
    {
        $x = $this->seedSynced();
        tenancy()->end();
        (new DedupRunner())->run($this->tenant, 'products', 'migrate');
        $stored = $this->tenant->run(fn () => DB::connection('tenant')->table('products')->where('central_product_id', $x['central']->id)->first(['price', 'default_price']));
        $this->flipAll();

        $this->tenant->run(function () use ($x, $stored) {
            $prices = (array) json_decode((string) $stored->price, true);
            foreach ([null, 5, 9] as $country) {
                $sql = ProductCatalog::countryPriceSql('m', 'price', 'base_price', 'default_price', $country);
                $row = DB::connection('tenant')->selectOne(
                    "SELECT {$sql} AS p FROM (" . ProductCatalog::productsSql(15.0) . ') m WHERE m.`central_product_id` = ?',
                    [$x['central']->id],
                );
                $expected = $country !== null && isset($prices[(string) $country]) ? (float) $prices[(string) $country] : (float) $stored->default_price;
                $this->assertEqualsWithDelta($expected, (float) $row->p, 0.01, "country {$country}");
            }
        });
    }

    public function test_product_keys_and_refs_resolve_in_one_place(): void
    {
        $this->assertSame(12, ProductKey::keyOf('c:12'));
        $this->assertSame(1_000_000_003, ProductKey::keyOf('t:3'));
        $this->assertSame('t:3', (string) ProductKey::ref(1_000_000_003));
        $this->assertSame(7, ProductKey::fromPair('central', 7));
        $this->assertSame(1_000_000_007, ProductKey::fromPair('tenant', 7));
    }

    public function test_card_eager_loads_do_not_grow_with_the_number_of_products(): void
    {
        $this->seedSynced();
        tenancy()->end();
        (new DedupRunner())->run($this->tenant, 'products', 'migrate');
        $this->flipAll();

        $count = function (int $extra) {
            for ($i = 0; $i < $extra; $i++) {
                $y = $this->tenant->run(fn () => $this->createCentralCatalogVariant(price: 10.0 + $i));
                tenancy()->end();
                $this->tenant->run(fn () => (new ProductCatalog())->adopt($y['central_product']->id));
            }

            return $this->tenant->run(function () {
                $repo = app(StorefrontRepository::class);
                $conn = DB::connection('tenant');
                // Warm-up: schema introspection (CatalogRefColumns) is cached after the first load and is not per-product.
                ProductCatalog::query()->where('active', true)->with($repo->productRelations())->get();
                $conn->enableQueryLog();
                ProductCatalog::query()->where('active', true)->with($repo->productRelations())->get();
                $log = $conn->getQueryLog();
                $conn->disableQueryLog();

                return ['n' => count($log), 'sql' => array_map(fn ($q) => substr(preg_replace('/\\s+/', ' ', $q['query']), 0, 140), $log)];
            });
        };

        $few = $count(1);
        $many = $count(5);
        $this->assertSame($few['n'], $many['n'], "eager-load query count must not depend on the number of products\nFEW:\n" . implode("\n", $few['sql']) . "\nMANY:\n" . implode("\n", $many['sql']));
    }

    public function test_stock_move_of_a_merged_central_line_moves_override_and_central_stock(): void
    {
        $x = $this->seedSynced();
        tenancy()->end();
        (new DedupRunner())->run($this->tenant, 'products', 'migrate');
        $this->flipAll();

        $this->tenant->run(function () use ($x) {
            $catalog = new ProductCatalog();
            $catalog->setVariantOverride($x['variant']->id, $x['central']->id, ['stock_override' => 5]);
            $variant = \App\Models\Tenant\Catalog\MergedProductVariant::query()->where('central_product_variant_id', $x['variant']->id)->firstOrFail();
            $this->assertSame(5, (int) $variant->stock);

            app(\App\Services\Tenant\StockService::class)->reserveVariant($variant, 2);

            $this->assertSame(3, (int) DB::connection('tenant')->table('tenant_product_variant_overrides')->value('stock_override'));
            $this->assertSame(18, (int) CentralVariant::query()->whereKey($x['variant']->id)->value('stock'));
            $this->assertSame(18, (int) CentralProduct::query()->whereKey($x['central']->id)->value('stock'));
            // no tenant copy row was touched
            $this->assertSame(20, (int) DB::connection('tenant')->table('product_variants')->where('central_product_variant_id', $x['variant']->id)->value('stock'));
        });
    }

    public function test_order_line_columns_and_reference_rows_use_typed_pairs_in_central_mode(): void
    {
        $x = $this->seedSynced();
        tenancy()->end();
        (new DedupRunner())->run($this->tenant, 'products', 'migrate');
        $this->flipAll();

        $this->tenant->run(function () use ($x) {
            $product = ProductCatalog::query()->where('central_product_id', $x['central']->id)->with('variants')->firstOrFail();
            $cols = ProductCatalog::lineColumns($product, $product->variants->first());
            $this->assertNull($cols['product_id']);
            $this->assertSame('central', $cols['product_type']);
            $this->assertSame($x['central']->id, $cols['catalog_product_id']);
            $this->assertSame($x['variant']->id, $cols['catalog_variant_id']);
            $this->assertNull($cols['product_variant_id']);

            $this->assertSame(['product_id' => null, 'product_type' => 'central', 'catalog_product_id' => 9], ProductCatalog::refColumnsForKey(9));
            $this->assertSame(['product_id' => 4, 'product_type' => 'tenant', 'catalog_product_id' => 4], ProductCatalog::refColumnsForKey('t:4'));

            // order_items.product_id and favorites.product_id accept NULL (migrations 2026_11_27_000002/3).
            $this->assertTrue(DB::connection('tenant')->selectOne("SELECT IS_NULLABLE n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'product_id'")->n === 'YES');
            $this->assertTrue(DB::connection('tenant')->selectOne("SELECT IS_NULLABLE n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'favorites' AND COLUMN_NAME = 'product_id'")->n === 'YES');
        });
    }

    public function test_central_products_flag_needs_the_other_sections_central_first(): void
    {
        $this->seedSynced();
        tenancy()->end();
        CatalogSource::set($this->tenant, 'products', CatalogSource::CENTRAL);
        $this->tenant->run(fn () => $this->assertFalse(ProductCatalog::enabled()));
        $this->flipAll();
        $this->tenant->run(fn () => $this->assertTrue(ProductCatalog::enabled()));
    }
}
