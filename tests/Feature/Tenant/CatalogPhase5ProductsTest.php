<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Jobs\ApplyTenantProfitPercentageJob;
use App\Models\Tenant\Catalog\MergedProduct;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Services\Catalog\CatalogSyncCheck;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Files;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 5 (products + variants): override tables, ProductCatalog merged read + read-time price,
 * `catalog:dedup-migrate products` (migrate / verify), guards on the unguarded sync jobs, sync-check metrics.
 */
class CatalogPhase5ProductsTest extends TestCase
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

    private function mk(...$args): array
    {
        return $this->tenant->run(fn () => $this->createCentralCatalogVariant(...$args));
    }

    private function flip(string $mode = CatalogSource::CENTRAL): void
    {
        tenancy()->end();
        CatalogSource::set($this->tenant, 'products', $mode);
        CacheVersion::flushMemo();
    }

    public function test_override_tables_exist_and_adoption_is_an_override_row(): void
    {
        $x = $this->mk(price: 100.0);
        $this->tenant->run(function () use ($x) {
            $this->assertTrue(ProductCatalog::schemaReady());
            $catalog = new ProductCatalog();
            $this->assertTrue($catalog->adopt($x['central_product']->id));
            $this->assertFalse($catalog->adopt($x['central_product']->id)); // idempotent
            $catalog->setOverride($x['central_product']->id, ['featured' => true, 'name' => 'ignored: central is read-only']);
            $row = DB::connection('tenant')->table('tenant_product_overrides')->first();
            $this->assertTrue((bool) $row->featured);
            $catalog->release($x['central_product']->id);
            $this->assertSame(0, DB::connection('tenant')->table('tenant_product_overrides')->count());
        });
    }

    public function test_merged_read_prices_through_the_existing_formula(): void
    {
        $x = $this->mk(price: 100.0);
        $this->flip();
        $this->tenant->run(function () use ($x) {
            $catalog = new ProductCatalog();
            $catalog->setOverride($x['central_product']->id, ['active' => true, 'profit' => ['default' => ['profit_type' => 'percentage', 'profit_value' => 10]]]);
            $row = MergedProduct::query()->where('central_product_id', $x['central_product']->id)->first();
            $this->assertNotNull($row);
            $this->assertEqualsWithDelta(110.0, (float) $row->sort_price, 0.01);
            $map = $catalog->priceMap(100.0, null, ['default' => ['profit_type' => 'percentage', 'profit_value' => 10]], 0.0);
            $this->assertEqualsWithDelta((float) $row->sort_price, $map['default'], 0.01);
        });
    }

    public function test_unadopted_and_unpublished_central_products_are_not_merged(): void
    {
        $x = $this->mk();
        $this->flip();
        $this->tenant->run(function () use ($x) {
            $this->assertSame(0, MergedProduct::query()->where('central_product_id', $x['central_product']->id)->count());
            (new ProductCatalog())->adopt($x['central_product']->id);
            $this->assertSame(1, MergedProduct::query()->where('central_product_id', $x['central_product']->id)->count());
        });
        tenancy()->end();
        \App\Models\Product::withoutEvents(fn () => \App\Models\Product::query()->whereKey($x['central_product']->id)->update(['status' => 'draft']));
        $this->tenant->run(fn () => $this->assertSame(0, MergedProduct::query()->where('central_product_id', $x['central_product']->id)->count()));
    }

    public function test_dedup_migrate_then_verify_is_clean_and_idempotent(): void
    {
        $x = $this->mk(price: 100.0);
        $this->tenant->run(fn () => DB::connection('tenant')->table('products')->where('id', $x['product']->id)->update(['featured' => 1, 'default_price' => 100]));
        tenancy()->end();
        $runner = new DedupRunner();

        $dry = $runner->run($this->tenant, 'products', 'dry-run');
        $this->assertSame('done', $dry['status']);
        $this->assertSame(1, $dry['counts']['overrides_written']);
        $this->tenant->run(fn () => $this->assertSame(0, DB::connection('tenant')->table('tenant_product_overrides')->count()));

        $this->assertSame('done', $runner->run($this->tenant, 'products', 'migrate')['status']);
        $second = $runner->run($this->tenant, 'products', 'migrate');
        $this->assertSame(0, $second['counts']['overrides_written']);

        $verify = $runner->run($this->tenant, 'products', 'verify');
        $this->assertSame(0, $verify['diffs'], json_encode($verify['examples'] ?? []));
    }

    public function test_verify_reports_missing_adoption(): void
    {
        $this->mk();
        tenancy()->end();
        $verify = (new DedupRunner())->run($this->tenant, 'products', 'verify');
        $this->assertGreaterThan(0, $verify['diffs']);
    }

    public function test_profit_job_is_skipped_for_central_mode_tenants(): void
    {
        $x = $this->mk(price: 100.0);
        $this->flip();
        $before = $this->tenant->run(fn () => DB::connection('tenant')->table('products')->where('id', $x['product']->id)->value('default_price'));
        (new ApplyTenantProfitPercentageJob((string) $this->tenant->id))->handle(app(\App\Services\Tenant\ProductPriceCalculationService::class));
        $after = $this->tenant->run(fn () => DB::connection('tenant')->table('products')->where('id', $x['product']->id)->value('default_price'));
        $this->assertEquals($before, $after);
    }

    public function test_sync_check_reports_product_parity_metrics(): void
    {
        $this->mk();
        tenancy()->end();
        $report = (new CatalogSyncCheck())->run([(string) $this->tenant->id], ['products']);
        $m = $report[0]['sections']['products'];
        $this->assertSame(1, $m['carried_without_override']);
        $this->assertArrayHasKey('dedup_verify_diffs', $m);
    }

    public function test_merged_variants_honour_overrides_and_stock_override_moves(): void
    {
        $x = $this->mk(price: 100.0);
        $this->flip();
        $this->tenant->run(function () use ($x) {
            $catalog = new ProductCatalog();
            $catalog->adopt($x['central_product']->id);
            $v = \App\Models\Tenant\Catalog\MergedProductVariant::query()->where('central_product_variant_id', $x['central_variant']->id)->first();
            $this->assertNotNull($v);
            $this->assertSame(20, (int) $v->stock); // central stock, no override
            $catalog->setVariantOverride($x['central_variant']->id, $x['central_product']->id, ['stock_override' => 5, 'price_override' => 140]);
            $v = \App\Models\Tenant\Catalog\MergedProductVariant::query()->where('central_product_variant_id', $x['central_variant']->id)->first();
            $this->assertSame(5, (int) $v->stock);
            $this->assertEqualsWithDelta(140.0, (float) $v->sort_price, 0.01);
            $this->assertTrue($catalog->moveOverrideStock($x['central_product']->id, $x['central_variant']->id, 3, -1));
            $this->assertTrue($catalog->moveOverrideStock($x['central_product']->id, $x['central_variant']->id, 9, -1)); // floored at 0
            $this->assertSame(0, (int) DB::connection('tenant')->table('tenant_product_variant_overrides')->value('stock_override'));
            $catalog->moveOverrideStock($x['central_product']->id, $x['central_variant']->id, 2, 1);
            $this->assertSame(2, (int) DB::connection('tenant')->table('tenant_product_variant_overrides')->value('stock_override'));
        });
    }

    public function test_verify_flags_price_map_drift_between_copy_and_merged_formula(): void
    {
        $x = $this->mk(price: 100.0);
        // Copy shows 120 but carries no profit row: the merged formula (central 100 + 0%) yields 100.
        $this->tenant->run(fn () => DB::connection('tenant')->table('products')->where('id', $x['product']->id)->update(['default_price' => 120, 'price' => json_encode(['default' => 120])]));
        tenancy()->end();
        $runner = new DedupRunner();
        $runner->run($this->tenant, 'products', 'migrate');
        $verify = $runner->run($this->tenant, 'products', 'verify');
        $this->assertGreaterThan(0, $verify['diffs']);
        $this->assertSame(1, $verify['counts']['price_map_diffs']);
    }
}
