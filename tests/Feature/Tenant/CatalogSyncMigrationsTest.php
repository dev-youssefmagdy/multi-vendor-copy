<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\OrderStatus;
use App\Models\Category as CentralCategory;
use App\Models\ProductBadge as CentralProductBadge;
use App\Models\StaticPage as CentralStaticPage;
use App\Models\Tenant\Category;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Page;
use App\Models\Tenant\ProductBadge;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * QA — dedup plan Phase -1 (docs/TENANT_CENTRAL_DEDUP_PLAN.md §11 item 4): the five tenant migrations
 * 2026_11_21_000001..5 must be safe to run on every tenant DB more than once (tenants:migrate retries, partial
 * deploys), reversible (2..5), and their backfills must classify legacy rows correctly. Plus the R1 panel
 * round-trips (hidden parent) through the real save services.
 */
class CatalogSyncMigrationsTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    private const MIGRATIONS = [
        'order_items' => '2026_11_21_000001_order_items_keep_lines_when_variant_deleted.php',
        'pages' => '2026_11_21_000002_add_source_to_pages_table.php',
        'product_badges' => '2026_11_21_000003_add_central_product_badge_id_to_product_badges_table.php',
        'categories' => '2026_11_21_000004_add_central_visible_to_categories_table.php',
        'category_product' => '2026_11_21_000005_add_source_to_category_product_table.php',
    ];

    /** @var array<string, Migration> */
    private array $migrations = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();

        foreach (self::MIGRATIONS as $table => $file) {
            $this->migrations[$table] = require database_path('migrations/tenant/' . $file);
        }
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function createTable(string $table): string
    {
        return (string) collect((array) DB::selectOne("SHOW CREATE TABLE `{$table}`"))->values()->get(1);
    }

    /** @param callable(): mixed $callback */
    private function central(callable $callback): mixed
    {
        tenancy()->end();

        return Model::withoutEvents($callback);
    }

    public function test_every_phase_minus_one_migration_is_idempotent_and_2_to_5_are_reversible(): void
    {
        $this->tenant->run(function () {
            foreach ($this->migrations as $table => $migration) {
                $before = $this->createTable($table);

                // Second (and third) run on an already-migrated DB: no error, no schema change.
                $migration->up();
                $migration->up();
                $this->assertSame($before, $this->createTable($table), "{$table}: re-running up() changed the schema");

                if ($table === 'order_items') {
                    $migration->down(); // documented no-op (never restore ON DELETE CASCADE)
                    $this->assertSame($before, $this->createTable($table));

                    continue;
                }

                $migration->down();
                $migration->down(); // down() twice must not fail either
                $this->assertNotSame($before, $this->createTable($table), "{$table}: down() did not drop the column");

                $migration->up();
                $migration->up();
                $this->assertSame($before, $this->createTable($table), "{$table}: down() + up() did not restore the schema");
            }
        });
    }

    public function test_order_items_migration_with_orphan_lines_keeps_them_and_adds_the_fk_once_clean(): void
    {
        $this->tenant->run(function () {
            $variant = $this->createOwnProductVariant(stock: 5);
            $order = $this->createOrder($this->createCustomer(), [[$variant, 1]], OrderStatus::Delivered);
            $itemId = (int) $order->items()->value('id');

            // Simulate a legacy DB: cascade FK, then a line whose variant is already gone.
            Schema::table('order_items', fn ($t) => $t->dropForeign('order_items_product_variant_id_foreign'));
            Schema::disableForeignKeyConstraints();
            DB::table('order_items')->where('id', $itemId)->update(['product_variant_id' => 987654321]);
            Schema::enableForeignKeyConstraints();

            $this->migrations['order_items']->up();
            $this->migrations['order_items']->up();

            $this->assertSame(987654321, (int) OrderItem::query()->whereKey($itemId)->value('product_variant_id'), 'historic variant id never nulled');
            $this->assertNull(collect(Schema::getForeignKeys('order_items'))->firstWhere('columns', ['product_variant_id']), 'no FK while orphans exist');

            // Once the orphan is gone, the next run adds the SET NULL FK.
            DB::table('order_items')->where('id', $itemId)->update(['product_variant_id' => $variant->id]);
            $this->migrations['order_items']->up();
            $fk = collect(Schema::getForeignKeys('order_items'))->firstWhere('columns', ['product_variant_id']);
            $this->assertSame('set null', strtolower((string) ($fk['on_delete'] ?? '')));
        });
    }

    public function test_backfills_classify_legacy_rows(): void
    {
        $tag = Str::lower(Str::random(6));

        // ---- central fixtures --------------------------------------------------------------------------------
        [$centralPublished, $centralDraft] = $this->central(fn () => [
            CentralCategory::query()->create(['status' => 'published', 'is_featured' => false, 'order_number' => 1]),
            CentralCategory::query()->create(['status' => 'draft', 'is_featured' => false, 'order_number' => 2]),
        ]);
        $this->central(fn () => CentralStaticPage::query()->create(['title' => 'Central', 'slug' => "qa-central-{$tag}", 'content' => 'x', 'status' => 'published']));
        $centralBadge = $this->central(fn () => CentralProductBadge::query()->create(['text' => "qa-badge-{$tag}", 'active' => true]));

        $this->tenant->run(function () use ($tag, $centralPublished, $centralDraft, $centralBadge) {
            // Legacy (pre-migration) state: drop the new columns first.
            foreach (['pages', 'product_badges', 'categories', 'category_product'] as $table) {
                $this->migrations[$table]->down();
            }

            // pages
            DB::table('pages')->insert([
                ['slug' => "qa-central-{$tag}", 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['slug' => "qa-vendor-{$tag}", 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);

            // badges: the row with central's text is linked, the vendor's own is not (text is unique tenant-side)
            $firstBadge = DB::table('product_badges')->insertGetId(['text' => "qa-badge-{$tag}", 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
            $vendorBadge = DB::table('product_badges')->insertGetId(['text' => "qa-vendor-badge-{$tag}", 'active' => true, 'created_at' => now(), 'updated_at' => now()]);

            // categories: old sync wrote active = central published
            $catPublishedOff = DB::table('categories')->insertGetId(['central_category_id' => $centralPublished->id, 'active' => false, 'featured' => false, 'order_number' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $catDraft = DB::table('categories')->insertGetId(['central_category_id' => $centralDraft->id, 'active' => false, 'featured' => false, 'order_number' => 2, 'created_at' => now(), 'updated_at' => now()]);
            $catVendor = DB::table('categories')->insertGetId(['active' => true, 'featured' => false, 'order_number' => 3, 'created_at' => now(), 'updated_at' => now()]);

            // category_product: central product linked centrally to $centralPublished only
            $catalog = $this->createCentralCatalogVariant();
            DB::connection(config('tenancy.database.central_connection'))->table('category_product')->insert([
                'product_id' => $catalog['central_product']->id, 'category_id' => $centralPublished->id,
            ]);
            $productId = $catalog['product']->id;
            foreach ([$catPublishedOff, $catDraft, $catVendor] as $categoryId) {
                DB::table('category_product')->insert(['product_id' => $productId, 'category_id' => $categoryId, 'created_at' => now(), 'updated_at' => now()]);
            }

            foreach (['pages', 'product_badges', 'categories', 'category_product'] as $table) {
                $this->migrations[$table]->up();
                $this->migrations[$table]->up(); // backfill is not re-applied / not doubled
            }

            // pages.source
            $this->assertSame('central', DB::table('pages')->where('slug', "qa-central-{$tag}")->value('source'));
            $this->assertSame('tenant', DB::table('pages')->where('slug', "qa-vendor-{$tag}")->value('source'));

            // product_badges.central_product_badge_id
            $this->assertSame($centralBadge->id, (int) DB::table('product_badges')->where('id', $firstBadge)->value('central_product_badge_id'));
            $this->assertNull(DB::table('product_badges')->where('id', $vendorBadge)->value('central_product_badge_id'));

            // categories.central_visible (+ documented active reset for centrally unpublished rows)
            $row = fn (int $id) => DB::table('categories')->where('id', $id)->first(['active', 'central_visible']);
            $this->assertEquals((object) ['active' => 0, 'central_visible' => 1], $row($catPublishedOff), 'published row: vendor/legacy active=0 untouched');
            $this->assertEquals((object) ['active' => 1, 'central_visible' => 0], $row($catDraft), 'unpublished row: hidden by central_visible, active reset to 1');
            $this->assertEquals((object) ['active' => 1, 'central_visible' => 1], $row($catVendor));

            // category_product.source: only the pair central has today is central
            $sources = DB::table('category_product')->where('product_id', $productId)->pluck('source', 'category_id')->all();
            $this->assertSame('central', $sources[$catPublishedOff]);
            $this->assertSame('tenant', $sources[$catDraft]);
            $this->assertSame('tenant', $sources[$catVendor]);
        });
    }

    public function test_r1_hidden_parent_product_and_category_saves_keep_links(): void
    {
        // Central parent > child, synced, product linked (by the vendor) to the child only and to both.
        [$parent, $child] = $this->central(function () {
            $parent = CentralCategory::query()->create(['status' => 'published', 'is_featured' => false, 'order_number' => 1]);
            $child = CentralCategory::query()->create(['status' => 'published', 'is_featured' => false, 'order_number' => 2, 'parent_id' => $parent->id]);

            return [$parent, $child];
        });
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'categories');
        tenancy()->end();

        [$childOnly, $both, $tParent, $tChild] = $this->tenant->run(function () use ($parent, $child) {
            $tParent = (int) Category::query()->where('central_category_id', $parent->id)->value('id');
            $tChild = (int) Category::query()->where('central_category_id', $child->id)->value('id');
            $childOnly = $this->createOwnProductVariant()->product;
            $both = $this->createOwnProductVariant()->product;
            $childOnly->categories()->attach($tChild);
            $both->categories()->attach([$tParent, $tChild]);

            return [$childOnly->id, $both->id, $tParent, $tChild];
        });

        // Central unpublishes the parent.
        $this->central(fn () => $parent->forceFill(['status' => 'draft'])->save());
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'categories');
        tenancy()->end();

        $this->tenant->run(function () use ($childOnly, $both, $tParent, $tChild) {
            $repo = app(TenantPanelRepository::class);
            $service = app(TenantPanelService::class);
            $pickable = collect($repo->categoryTreeOptions())->pluck('id')->map(fn ($id) => (int) $id)->all();
            $this->assertContains($tChild, $pickable, 'child of a hidden parent stays pickable');
            $this->assertNotContains($tParent, $pickable);

            $links = fn (int $productId) => DB::table('category_product')->where('product_id', $productId)->pluck('category_id')->map(fn ($id) => (int) $id)->sort()->values()->all();

            // Product form re-submits what the picker showed as selected.
            foreach ([$childOnly, $both] as $productId) {
                $product = \App\Models\Tenant\Product::query()->findOrFail($productId);
                $selected = array_values(array_intersect($links($productId), $pickable));
                $service->saveProduct([
                    'slug' => $product->slug,
                    'translations' => ['en' => ['name' => 'QA product ' . $productId]],
                    'category_ids' => $selected,
                    'active' => true,
                    'is_own_product' => (bool) $product->is_own_product,
                ], $product);
            }
            $this->assertSame([$tChild], $links($childOnly), 'child link kept');
            $this->assertSame(collect([$tParent, $tChild])->sort()->values()->all(), $links($both), 'hidden-parent link kept too');

            // Category edit form: the hidden parent is offered as the current option and saved back unchanged.
            $options = $repo->categoryOptions($tParent);
            $this->assertArrayHasKey($tParent, $options);
            $childModel = Category::query()->findOrFail($tChild);
            $service->saveCategory([
                'central_category_id' => $childModel->central_category_id,
                'parent_id' => $tParent,
                'order_number' => 2,
                'active' => true,
                'featured' => false,
                'translations' => [],
            ], $childModel);
            $this->assertSame($tParent, (int) Category::query()->whereKey($tChild)->value('parent_id'), 'child keeps its hidden parent');
        });
    }
}
