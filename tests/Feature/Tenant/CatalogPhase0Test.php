<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\ProductStatus;
use App\Jobs\SyncCentralProductToTenantJob;
use App\Jobs\SyncTenantSectionsJob;
use App\Models\Language as CentralLanguage;
use App\Models\Product as CentralProduct;
use App\Models\ProductBadge as CentralBadge;
use App\Models\ProductVariant as CentralVariant;
use App\Models\Tenant;
use App\Models\Tenant\Catalog\CentralProductBadge;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Models\Tenant\ProductVariant;
use App\Services\Catalog\CatalogSyncCheck;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CentralTable;
use App\Support\Catalog\CentralWriteAttempted;
use App\Support\Catalog\CentralWriteGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 0 (docs/TENANT_CENTRAL_DEDUP_PLAN.md §2b, §2b.1, §3.2, §9 invariant 1, §0.3-Q3):
 * central-mode sections are skipped by every sync entry point, tenant code cannot write to central through the
 * read-only central models, the sync still treats soft-deleted central variants as deleted, and
 * `catalog:sync-check` reports drift without writing anything.
 */
class CatalogPhase0Test extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function sync(): TenantCatalogSyncService
    {
        return app(TenantCatalogSyncService::class);
    }

    private function inTenant(callable $callback): mixed
    {
        return $this->tenant->run($callback);
    }

    /** Central fixtures without model events (no fan-out to tenants). */
    private function central(callable $callback): mixed
    {
        tenancy()->end();

        return Model::withoutEvents($callback);
    }

    private function centralProductWithVariants(int $variants): array
    {
        return $this->central(function () use ($variants) {
            $product = CentralProduct::query()->create([
                'slug' => 'cp-' . Str::lower(Str::random(8)),
                'sku' => 'CP-' . Str::upper(Str::random(6)),
                'status' => ProductStatus::Published,
                'base_price' => 20,
                'stock' => 10,
            ]);
            $rows = [];
            for ($i = 1; $i <= $variants; $i++) {
                $rows[] = CentralVariant::query()->create(['product_id' => $product->id, 'sku' => $product->sku . "-{$i}", 'price' => 20, 'stock' => 5]);
            }

            return [$product, $rows];
        });
    }

    // ---- §2b guard -------------------------------------------------------------------------------------------

    public function test_central_mode_sections_are_skipped_by_every_sync_entry_point(): void
    {
        $this->central(fn () => CentralBadge::query()->create(['text' => 'phase0-badge', 'active' => true]));
        [$product] = $this->centralProductWithVariants(1);

        CatalogSource::set($this->tenant, 'badges', CatalogSource::CENTRAL);
        CatalogSource::set($this->tenant, 'products', CatalogSource::CENTRAL);

        $stored = Tenant::query()->findOrFail($this->tenant->id);
        $this->assertSame(['badges' => 'central', 'products' => 'central'], CatalogSource::map($stored), 'persisted in tenants.data');
        $this->assertTrue(CatalogSource::isCentral($this->tenant, 'badges'), 'in-memory model updated');

        $sync = $this->sync();
        $this->assertSame(0, $sync->syncSection($this->tenant, 'badges'));
        $atomic = $sync->syncSectionsAtomically($this->tenant, ['badges', 'labels']);
        $this->assertSame(0, $atomic['badges']);
        $this->assertArrayHasKey('labels', $atomic, 'sync-mode sections still run');
        $this->assertSame(0, $sync->syncForTenant($this->tenant, ['badges'])['badges']);
        $sync->syncProductToTenant($product, $this->tenant, force: true);
        $this->assertSame(0, $sync->syncNewProductsForTenant($this->tenant, [$product->id]));
        $this->assertSame(0, $sync->syncSection($this->tenant, 'products', initialCatalog: true));
        tenancy()->end();

        $this->inTenant(function () use ($product) {
            $this->assertFalse(ProductBadge::query()->where('text', 'phase0-badge')->exists(), 'badge not copied');
            $this->assertFalse(Product::withoutGlobalScope('centralVisible')->where('central_product_id', $product->id)->exists(), 'product not copied');
        });

        // Fan-out: central-mode sections are filtered per tenant, product pushes skipped.
        Queue::fake();
        $fanOut = app(CentralCatalogTenantSyncService::class);
        $fanOut->syncAllTenants(['badges', 'labels']);
        $fanOut->syncAllTenants(['badges']);
        $fanOut->syncProduct($product);
        Queue::assertPushed(SyncTenantSectionsJob::class, 1);
        Queue::assertPushed(SyncTenantSectionsJob::class, fn ($job) => $job->tenantId === $this->tenant->id && $job->sections === ['labels']);
        Queue::assertNotPushed(SyncCentralProductToTenantJob::class);

        // Back to `sync`: today's behaviour.
        CatalogSource::set($this->tenant, 'badges', CatalogSource::SYNC);
        $this->assertGreaterThan(0, $this->sync()->syncSection($this->tenant, 'badges'));
        tenancy()->end();
        $this->assertTrue($this->inTenant(fn () => ProductBadge::query()->where('text', 'phase0-badge')->exists()));
    }

    // ---- §9 invariant 1 --------------------------------------------------------------------------------------

    public function test_tenant_code_cannot_write_to_central_through_read_only_models_or_the_tenant_connection(): void
    {
        $centralDb = CentralTable::database();

        $this->inTenant(function () use ($centralDb) {
            // Reads work cross-schema on the tenant connection.
            $query = CentralProductBadge::query()->where('active', true);
            $this->assertSame('tenant', $query->getConnection()->getName());
            $this->assertStringContainsString("`{$centralDb}`.`product_badges`", $query->toSql());
            $this->assertIsInt($query->count());

            $existing = (new CentralProductBadge())->newFromBuilder(['id' => 1, 'text' => 'x', 'active' => 1]);
            $attempts = [
                'save' => fn () => (new CentralProductBadge(['text' => 'nope']))->save(),
                'create' => fn () => CentralProductBadge::query()->create(['text' => 'nope']),
                'firstOrCreate' => fn () => CentralProductBadge::query()->firstOrCreate(['text' => 'phase0-never-exists']),
                'model update' => fn () => $existing->update(['text' => 'y']),
                'model delete' => fn () => $existing->delete(),
                'model forceDelete' => fn () => $existing->forceDelete(),
                'model touch' => fn () => $existing->touch(),
                'model increment' => fn () => $existing->increment('id'),
                'builder update' => fn () => CentralProductBadge::query()->whereKey(0)->update(['active' => false]),
                'builder delete' => fn () => CentralProductBadge::query()->whereKey(0)->delete(),
                'builder forceDelete' => fn () => CentralProductBadge::query()->whereKey(0)->forceDelete(),
                'builder increment' => fn () => CentralProductBadge::query()->whereKey(0)->increment('id'),
                'insert' => fn () => CentralProductBadge::query()->insert(['text' => 'nope']),
                'insertOrIgnore' => fn () => CentralProductBadge::query()->insertOrIgnore(['text' => 'nope']),
                'upsert' => fn () => CentralProductBadge::query()->upsert([['text' => 'nope']], ['text']),
                'updateOrInsert' => fn () => CentralProductBadge::query()->toBase()->updateOrInsert(['text' => 'nope']),
                'toBase update' => fn () => CentralProductBadge::query()->toBase()->update(['active' => false]),
                'toBase delete' => fn () => CentralProductBadge::query()->toBase()->delete(),
                'truncate' => fn () => CentralProductBadge::query()->truncate(),
            ];

            $writes = 0;
            DB::connection('tenant')->listen(function (QueryExecuted $q) use (&$writes) {
                if (preg_match('/^\s*(insert|update|delete|replace|truncate)/i', $q->sql)) {
                    $writes++;
                }
            });

            foreach ($attempts as $name => $attempt) {
                try {
                    $attempt();
                    $this->fail("{$name} must throw CentralWriteAttempted");
                } catch (CentralWriteAttempted) {
                    $this->addToAssertionCount(1);
                }
            }
            $this->assertSame(0, $writes, 'no write statement reached the database');

            // Raw SQL on the tenant connection: the statement guard catches central targets, tenant writes pass.
            CentralWriteGuard::install(DB::connection('tenant'));
            try {
                DB::connection('tenant')->table(CentralTable::qualified('product_badges'))->where('id', 0)->update(['active' => false]);
                $this->fail('raw central update must be rejected');
            } catch (CentralWriteAttempted) {
                $this->addToAssertionCount(1);
            }
            DB::connection('tenant')->table('product_badges')->where('id', 0)->update(['active' => false]);
            $this->assertIsInt(DB::connection('tenant')->table(CentralTable::qualified('product_badges'))->count());
        });

        // The sync service itself never writes central through the tenant connection.
        tenancy()->initialize($this->tenant);
        CentralWriteGuard::install(DB::connection('tenant'));
        foreach (['currencies', 'pages', 'categories', 'products', 'badges', 'labels', 'flash-sales', 'coupons'] as $section) {
            $this->sync()->syncSection($this->tenant, $section);
        }
        tenancy()->end();
    }

    // ---- §0.3-Q3: sync treats soft-deleted central variants as deleted ---------------------------------------

    public function test_soft_deleted_central_variants_are_removed_from_the_tenant_like_deleted_ones(): void
    {
        [$product, [$keep, $drop]] = $this->centralProductWithVariants(2);

        $this->sync()->syncSection($this->tenant, 'products', initialCatalog: true);
        tenancy()->end();
        $this->assertSame(2, $this->inTenant(fn () => ProductVariant::query()->whereIn('central_product_variant_id', [$keep->id, $drop->id])->count()));

        Queue::fake();
        $drop->delete(); // soft delete (central admin removes the variant)
        $this->assertNotNull(CentralVariant::withTrashed()->find($drop->id));

        $this->sync()->syncSection($this->tenant, 'products');
        tenancy()->end();
        $this->inTenant(function () use ($keep, $drop) {
            $this->assertTrue(ProductVariant::query()->where('central_product_variant_id', $keep->id)->exists());
            $this->assertFalse(ProductVariant::query()->where('central_product_variant_id', $drop->id)->exists(), 'tenant copy removed');
        });

        // Single-product push: like after a hard delete, the push only refreshes the variants central still has
        // (pre-existing behaviour); the next full products sync removes the copy.
        $other = $this->central(fn () => CentralVariant::query()->create(['product_id' => $product->id, 'sku' => $product->sku . '-3', 'price' => 20, 'stock' => 5]));
        $this->sync()->syncProductToTenant($product->fresh(), $this->tenant);
        tenancy()->end();
        $this->assertTrue($this->inTenant(fn () => ProductVariant::query()->where('central_product_variant_id', $other->id)->exists()));
        $other->delete();
        $this->sync()->syncProductToTenant($product->fresh(), $this->tenant);
        tenancy()->end();
        $this->assertTrue($this->inTenant(fn () => ProductVariant::query()->where('central_product_variant_id', $other->id)->exists()));
        $this->sync()->syncSection($this->tenant, 'products');
        tenancy()->end();
        $this->assertFalse($this->inTenant(fn () => ProductVariant::query()->where('central_product_variant_id', $other->id)->exists()));
    }

    // ---- §2b.1 catalog:sync-check ----------------------------------------------------------------------------

    public function test_sync_check_reports_drift_and_never_writes(): void
    {
        $en = $this->central(fn () => CentralLanguage::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true, 'is_free' => true, 'sort_order' => 1]));
        [$catA, $catB] = $this->central(fn () => [
            \App\Models\Category::query()->create(['status' => 'published']),
            \App\Models\Category::query()->create(['status' => 'published']),
        ]);
        $this->sync()->syncSection($this->tenant, 'categories');
        tenancy()->end();
        Queue::fake();
        $catB->delete(); // soft-deleted centrally, tenant copy not re-synced yet → stale_trashed

        $central = $this->inTenant(function () use ($catA) {
            Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->where('central_category_id', $catA->id)->update(['central_visible' => false]);
            Category::query()->create(['central_category_id' => 99999999, 'active' => true]); // central row never existed
            Category::query()->create(['active' => true]); // own

            $catalog = $this->createCentralCatalogVariant();
            $this->createVariantFor($catalog['product']); // orphan: central product, variant without link
            $catalog['product']->forceFill(['has_custom_translations' => true])->save();


            $zz = \App\Models\Tenant\Language::query()->create(['code' => 'zz', 'name' => 'Zz', 'native_name' => 'Zz', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 9]);
            $type = $catalog['product']->getMorphClass();
            DB::table('translations')->insert([
                ['translatable_type' => $type, 'translatable_id' => $catalog['product']->id, 'language_id' => $this->activeLanguage->id, 'field' => 'name', 'value' => 'Same', 'created_at' => now(), 'updated_at' => now()],
                ['translatable_type' => $type, 'translatable_id' => $catalog['product']->id, 'language_id' => $this->activeLanguage->id, 'field' => 'description', 'value' => 'vendor text', 'created_at' => now(), 'updated_at' => now()],
                ['translatable_type' => $type, 'translatable_id' => $catalog['product']->id, 'language_id' => $this->activeLanguage->id, 'field' => 'meta_description', 'value' => 'only here', 'created_at' => now(), 'updated_at' => now()],
                ['translatable_type' => $type, 'translatable_id' => $catalog['product']->id, 'language_id' => $zz->id, 'field' => 'name', 'value' => 'Zz name', 'created_at' => now(), 'updated_at' => now()],
            ]);

            return $catalog['central_product'];
        });
        $this->central(fn () => DB::table('translations')->insert([
            ['translatable_type' => CentralProduct::class, 'translatable_id' => $central->id, 'language_id' => $en->id, 'field' => 'name', 'value' => 'Same', 'created_at' => now(), 'updated_at' => now()],
            ['translatable_type' => CentralProduct::class, 'translatable_id' => $central->id, 'language_id' => $en->id, 'field' => 'description', 'value' => 'Central text', 'created_at' => now(), 'updated_at' => now()],
        ]));

        // A tenant row whose database does not exist (no TenantCreated pipeline).
        $noDb = Tenant::withoutEvents(fn () => Tenant::query()->create(['id' => 'nodb-' . Str::lower(Str::random(8)), 'shop_name' => 'No DB']));

        $writes = [];
        Event::listen(QueryExecuted::class, function (QueryExecuted $q) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete|replace|truncate|alter|create|drop)\b/i', $q->sql)) {
                $writes[] = $q->connectionName . ': ' . $q->sql;
            }
        });

        $report = collect(app(CatalogSyncCheck::class)->run([$this->tenant->id, $noDb->id]))->keyBy('tenant');

        $this->assertSame([], $writes, 'sync-check must not write');
        $this->assertSame('tenant database does not exist', $report[$noDb->id]['skipped']);

        $sections = $report[$this->tenant->id]['sections'];
        $this->assertSame('sync', $sections['products']['mode']);

        $categories = $sections['categories'];
        $this->assertGreaterThanOrEqual(3, $categories['linked']);
        $this->assertGreaterThanOrEqual(1, $categories['own']);
        $this->assertSame(1, $categories['stale_missing']);
        $this->assertSame(1, $categories['stale_trashed']);
        $this->assertSame(0, $categories['missing']);
        $this->assertSame(1, $categories['central_visible_mismatch']);

        $products = $sections['products'];
        $this->assertSame(1, $products['linked']);
        $this->assertNull($products['missing'], 'adoption: not derivable');
        $this->assertSame(1, $products['orphan_variants']);
        $this->assertSame(0, $products['stale_variants']);
        $this->assertSame(1, $products['custom_translations_without_request']);
        $this->assertSame(['equal' => 1, 'different' => 1, 'tenant_only_language' => 1, 'tenant_only_field' => 1], $products['translations']);

        $this->assertSame(0, $sections['badges']['duplicate_texts'], 'tenant product_badges.text is unique');
        $this->assertArrayHasKey('note', $sections['settings']);

        // Session is writable again afterwards.
        $this->central(fn () => DB::table('product_badges')->insert(['text' => 'after-check', 'active' => true]));

        // Command: default gate (orphan_variants <= 0) fails, a looser gate passes, bad input is rejected.
        $this->artisan('catalog:sync-check', ['--tenant' => [$this->tenant->id], '--json' => true])->assertExitCode(1);
        $this->artisan('catalog:sync-check', ['--tenant' => [$this->tenant->id], '--gate' => ['orphan_variants:5']])->assertExitCode(0);
        $this->artisan('catalog:sync-check', ['--tenant' => [$this->tenant->id], '--section' => ['products'], '--gate' => ['translations.different:0']])->assertExitCode(1);
        $this->artisan('catalog:sync-check', ['--section' => ['nope']])->assertExitCode(2);
        $this->artisan('catalog:sync-check', ['--gate' => ['bad gate']])->assertExitCode(2);
    }
}
