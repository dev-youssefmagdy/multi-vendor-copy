<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Category as CentralCategory;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant\Catalog\MergedCategory;
use App\Models\Tenant\Category;
use App\Models\Tenant\File;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\Catalog\CategoryAssignments;
use App\Repositories\Tenant\Catalog\CategoryCatalog;
use App\Repositories\Tenant\Catalog\ProductCategorySelection;
use App\Repositories\Tenant\StorefrontRepository;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Catalog\CatalogSyncCheck;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CategoryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Files;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 4 (docs/TENANT_CENTRAL_DEDUP_PLAN.md §4 categories / category_product, §11 "Backend — Phase 4
 * design"): merged categories (central live + overrides ∪ own), typed parents, merged membership (central pairs ∪
 * vendor links ∪ own-category rows), writes through CategoryAssignments, config ref rewrite, sync skip, sync-check,
 * `catalog:dedup-migrate categories`, and sync-vs-central storefront parity after migrate.
 */
class CatalogPhase4CategoriesTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    /** @var array<string, CentralCategory> */
    private array $c = [];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        CatalogRefColumns::flush();
        CategoryCatalog::flushMemo();
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
                Files::delete(DedupRunner::reportPath('categories', (string) $this->tenant->id, $step));
            }
        }
        CategoryCatalog::flushMemo();
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    // ── helpers ─────────────────────────────────────────────────────────────────────────────────────────────────

    private function inTenant(callable $callback): mixed
    {
        CategoryCatalog::flushMemo();

        return $this->tenant->run($callback);
    }

    private function central(callable $callback): mixed
    {
        tenancy()->end();

        return Model::withoutEvents($callback);
    }

    private function flip(string $mode = CatalogSource::CENTRAL): void
    {
        tenancy()->end();
        CatalogSource::set($this->tenant, 'categories', $mode);
        CategoryCatalog::flushMemo();
        CacheVersion::flushMemo();
    }

    private function scope(array $rootIds): void
    {
        tenancy()->end();
        $this->tenant->category_ids = $rootIds;
        $this->tenant->save();
    }

    private function centralLanguages(): void
    {
        $this->central(function () {
            foreach (['en' => 'English', 'fr' => 'French'] as $code => $name) {
                CentralLanguage::query()->firstOrCreate(['code' => $code], ['name' => $name, 'native_name' => $name, 'direction' => 'ltr', 'is_active' => true, 'is_default' => $code === 'en']);
            }
        });
        $this->tenant->run(function () {
            Language::query()->firstOrCreate(['code' => 'fr'], ['name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 2]);
            // A language central does not have: the vendor's (tenant-only, §4.2).
            Language::query()->firstOrCreate(['code' => 'de'], ['name' => 'German', 'native_name' => 'Deutsch', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 3]);
        });
    }

    private function centralCategory(string $name, ?int $parentId = null, string $status = 'published', bool $featured = true, int $order = 0): CentralCategory
    {
        return $this->central(function () use ($name, $parentId, $status, $featured, $order) {
            $category = CentralCategory::query()->create(['parent_id' => $parentId, 'status' => $status, 'is_featured' => $featured, 'order_number' => $order]);
            $languages = CentralLanguage::query()->whereIn('code', ['en', 'fr'])->pluck('id', 'code');
            foreach (['en' => $name, 'fr' => $name . ' FR'] as $code => $value) {
                foreach (['name' => $value, 'slug' => \Illuminate\Support\Str::slug($value)] as $field => $text) {
                    DB::table('translations')->insert([
                        'language_id' => $languages[$code], 'translatable_type' => CentralCategory::class, 'translatable_id' => $category->id,
                        'field' => $field, 'value' => $text, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            return $category;
        });
    }

    private function centralPair(CentralCategory $category, int $centralProductId, int $sort = 0): void
    {
        $this->central(fn () => DB::table('category_product')->insert(['category_id' => $category->id, 'product_id' => $centralProductId, 'sort_order' => $sort]));
    }

    private function syncCategories(): void
    {
        tenancy()->end();
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'categories');
        tenancy()->end();
    }

    /** The products-sync link step for the given tenant product ids (central pairs → copy pivot rows). */
    private function syncLinks(array $tenantProductIds): void
    {
        $this->inTenant(function () use ($tenantProductIds) {
            $copies = Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->whereNotNull('central_category_id')->pluck('id', 'central_category_id')->all();
            $wanted = [];
            foreach (Product::query()->whereIn('id', $tenantProductIds)->get() as $product) {
                $wanted[$product->id] = [];
                foreach (DB::connection(config('tenancy.database.central_connection'))->table('category_product')->where('product_id', $product->central_product_id)->pluck('category_id') as $cid) {
                    if (isset($copies[$cid])) {
                        $wanted[$product->id][] = (int) $copies[$cid];
                    }
                }
            }
            $service = app(TenantCatalogSyncService::class);
            (fn () => $this->syncCentralCategoryLinks($wanted))->call($service);
        });
    }

    private function copyId(CentralCategory $category): int
    {
        return $this->inTenant(fn () => (int) Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->where('central_category_id', $category->id)->value('id'));
    }

    /**
     * A realistic catalogue: central roots A (child A1) and B, hidden root H (child H1), soft-deleted D; three central
     * products (P1 in A + B, P2 in A1 + H1, P3 in D); the tenant scope = [A, B, H, D].
     *
     * @return array{p1: array, p2: array, p3: array}
     */
    private function catalogue(): array
    {
        $this->centralLanguages();
        $this->c['A'] = $this->centralCategory('Alpha', order: 2);
        $this->c['A1'] = $this->centralCategory('Alpha One', $this->c['A']->id, featured: false);
        $this->c['B'] = $this->centralCategory('Beta', order: 1);
        $this->c['H'] = $this->centralCategory('Hidden', status: 'draft');
        $this->c['H1'] = $this->centralCategory('Hidden Child', $this->c['H']->id);
        $this->c['D'] = $this->centralCategory('Deleted');

        $p = $this->inTenant(fn () => [
            'p1' => $this->createCentralCatalogVariant(price: 30.0),
            'p2' => $this->createCentralCatalogVariant(price: 40.0),
            'p3' => $this->createCentralCatalogVariant(price: 50.0),
        ]);
        $this->centralPair($this->c['A'], $p['p1']['central_product']->id, 0);
        $this->centralPair($this->c['B'], $p['p1']['central_product']->id, 0);
        $this->centralPair($this->c['A1'], $p['p2']['central_product']->id, 0);
        $this->centralPair($this->c['H1'], $p['p2']['central_product']->id, 0);
        $this->centralPair($this->c['H'], $p['p2']['central_product']->id, 0);
        $this->centralPair($this->c['D'], $p['p3']['central_product']->id, 0);

        $this->scope([$this->c['A']->id, $this->c['B']->id, $this->c['H']->id, $this->c['D']->id]);
        $this->syncCategories();
        $this->syncLinks([$p['p1']['product']->id, $p['p2']['product']->id, $p['p3']['product']->id]);

        // Central deletes D after the sync (its copy stays until the next sync: an orphan copy).
        $this->central(fn () => CentralCategory::query()->findOrFail($this->c['D']->id)->delete());

        return $p;
    }

    // ── flag off ────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_flag_off_keeps_reading_the_synced_tables(): void
    {
        $p = $this->catalogue();

        $this->inTenant(function () use ($p) {
            $this->assertFalse(CategoryCatalog::enabled());
            $this->assertInstanceOf(Category::class, CategoryCatalog::query()->getModel());
            $this->assertNotInstanceOf(MergedCategory::class, CategoryCatalog::query()->getModel());
            $product = Product::query()->findOrFail($p['p1']['product']->id);
            $this->assertInstanceOf(BelongsToMany::class, $product->categories());
            $this->assertSame('category_product', $product->categories()->getTable());
            $this->assertNotInstanceOf(MergedCategory::class, $product->categories->first());
            $this->assertSame(['integer', 'exists:categories,id'], app(ProductCategorySelection::class)->itemRules());
            $roots = (new StorefrontRepository())->rootCategoriesWithChildren();
            $this->assertNotInstanceOf(MergedCategory::class, $roots->first());
        });
    }

    // ── merged read ─────────────────────────────────────────────────────────────────────────────────────────────

    public function test_merged_categories_central_own_overrides_hidden_deleted_tree_and_translations(): void
    {
        $p = $this->catalogue();
        $this->flip();

        $this->inTenant(function () use ($p) {
            $this->assertTrue(CategoryCatalog::enabled());
            // Own category under the central A (typed parent), own child of the own one, own root.
            $own = app(CategoryAssignments::class)->saveOwn(new Category(), ['active' => true, 'order_number' => 0], 'c:' . $this->c['A']->id);
            $own->syncTranslations(['en' => ['name' => 'Own Under Alpha', 'slug' => 'alpha']]); // slug collides with central A (D3)
            $ownChild = app(CategoryAssignments::class)->saveOwn(new Category(), ['active' => true], CategoryKey::of(\App\Support\Catalog\CatalogRef::tenant($own->id)));
            $ownChild->syncTranslations(['en' => ['name' => 'Own Child', 'slug' => 'own-child']]);

            $catalog = app(CategoryCatalog::class);
            $keys = $catalog->keys();
            sort($keys);
            $ownKey = $own->id + CategoryKey::OWN_OFFSET;
            $expected = [$this->c['A']->id, $this->c['A1']->id, $this->c['B']->id, $this->c['H1']->id, $ownKey, $ownChild->id + CategoryKey::OWN_OFFSET];
            sort($expected);
            $this->assertSame($expected, $keys, 'hidden H and soft-deleted D are not merged; H1 (child of hidden) is');

            $a = $catalog->find('c:' . $this->c['A']->id);
            $this->assertSame('c:' . $this->c['A']->id, $a->ref);
            $this->assertSame($this->c['A']->id, $a->id);
            $this->assertTrue($a->active);
            $this->assertTrue($a->featured);
            $this->assertSame(2, $a->order_number);
            $this->assertSame('Alpha', $a->translationValue('name'));
            $this->assertSame('Alpha FR', $a->translationValue('name', 'fr'));
            $this->assertSame('alpha-c', $a->translationValue('slug'), 'D3: own slug wins, central gets -c');
            $this->assertSame('alpha', $catalog->find($ownKey)->translationValue('slug'));
            $this->assertSame($ownKey, (int) (new StorefrontRepository())->categoryBySlug('alpha')->id);
            $this->assertSame($this->c['A']->id, (int) (new StorefrontRepository())->categoryBySlug('alpha-c')->id);

            // Tree: own under central A, own child under own; H1 is an orphan (hidden parent): root in pickers.
            $this->assertSame($this->c['A']->id, $catalog->find($ownKey)->parent_id);
            $this->assertSame($ownKey, $catalog->find($ownChild->id + CategoryKey::OWN_OFFSET)->parent_id);
            $this->assertSame($this->c['H']->id, $catalog->find($this->c['H1']->id)->parent_id);
            $tree = collect($catalog->treeOptions())->keyBy('id');
            $this->assertSame(0, $tree[$this->c['H1']->id]['depth']);
            $this->assertSame(1, $tree[$ownKey]['depth']);
            $this->assertSame(2, $tree[$ownChild->id + CategoryKey::OWN_OFFSET]['depth']);
            $this->assertEqualsCanonicalizing(
                [$this->c['A']->id, $this->c['A1']->id, $ownKey, $ownChild->id + CategoryKey::OWN_OFFSET],
                $catalog->withDescendants($this->c['A']->id),
            );
            $roots = (new StorefrontRepository())->rootCategoriesWithChildren();
            $this->assertNotContains($this->c['H1']->id, $roots->pluck('id')->all(), 'orphans stay out of the root menu, as in sync mode');
            $this->assertContains($this->c['A']->id, $roots->pluck('id')->all());

            // Overrides win; NULL follows central; panel toggles write overrides only.
            $assign = app(CategoryAssignments::class);
            $assign->update($a, ['featured' => false, 'order_number' => 9, 'active' => true]);
            $this->assertSame(['central_category_id' => $this->c['A']->id, 'active' => null, 'featured' => 0, 'order_number' => 9],
                (array) DB::table(CategoryCatalog::OVERRIDES)->select('central_category_id', 'active', 'featured', 'order_number')->first());
            $a = $catalog->find($this->c['A']->id);
            $this->assertFalse($a->featured);
            $this->assertSame(9, $a->order_number);
            $assign->update($a, ['active' => false]);
            $this->assertNotContains($this->c['A']->id, (new StorefrontRepository())->activeCategories()->pluck('id')->all());
            $assign->update($catalog->find($this->c['A']->id), ['active' => true, 'featured' => true, 'order_number' => 2]);
            $this->assertSame(0, DB::table(CategoryCatalog::OVERRIDES)->count(), 'back to central defaults = no override row');

            // Tenant-only language: vendor text kept; platform languages ignored.
            $assign->saveTenantOnlyTranslations($a, ['de' => ['name' => 'Alpha DE'], 'en' => ['name' => 'Hacked']]);
            $a = $catalog->find($this->c['A']->id);
            $this->assertSame('Alpha DE', $a->translationValue('name', 'de'));
            $this->assertSame('Alpha', $a->translationValue('name', 'en'));

            // Thumb: vendor file wins over central, removed → back to central (null here) → default asset.
            $file = File::query()->create(['key' => 'thumb', 'path' => 'categories/x.avif', 'storage_type' => 'public', 'file_type' => 'image', 'model_type' => CategoryCatalog::CENTRAL_FILE_TYPE, 'model_id' => $this->c['A']->id]);
            $assign->setThumb($a, $file->id);
            $this->assertSame($file->full_path, $catalog->find($this->c['A']->id)->thumb_url);
            $assign->setThumb($a, null);
            $this->assertStringContainsString('category-default', $catalog->find($this->c['A']->id)->thumb_url);

            // Read-only + nothing written to central (invariant 1).
            $this->expectException(\LogicException::class);
            $catalog->find($this->c['B']->id)->update(['active' => false]);
        });
    }

    public function test_membership_union_sort_hide_vendor_links_and_product_form_save(): void
    {
        $p = $this->catalogue();
        $this->flip();

        $this->inTenant(function () use ($p) {
            $catalog = app(CategoryCatalog::class);
            $assign = app(CategoryAssignments::class);
            $own = $assign->saveOwn(new Category(), ['active' => true], null);
            $ownKey = $own->id + CategoryKey::OWN_OFFSET;
            $ownProduct = $this->createOwnProductVariant(price: 20.0)->product;
            $p1 = Product::query()->findOrFail($p['p1']['product']->id);
            $p2 = Product::query()->findOrFail($p['p2']['product']->id);
            $A = $this->c['A']->id;
            $B = $this->c['B']->id;

            $this->assertSame([$p1->id], $catalog->productIds($A));
            $this->assertEqualsCanonicalizing([$A, $B], $p1->categories->pluck('id')->all());
            $this->assertSame([$this->c['A1']->id, $this->c['H1']->id], $p2->categories->pluck('id')->sort()->values()->all());

            // Product form: P1 → only B + own category + A1 (vendor link central lacks). A is hidden, not deleted.
            app(ProductCategorySelection::class)->save($p1, [(string) $B, 'c:' . $this->c['A1']->id, (string) $ownKey]);
            $p1->unsetRelation('categories');
            $this->assertEqualsCanonicalizing([$B, $this->c['A1']->id, $ownKey], $p1->categories->pluck('id')->all());
            $this->assertSame(1, DB::table(CategoryCatalog::PAIR_OVERRIDES)->where(['category_id' => $A, 'hidden' => 1, 'source' => 'central'])->count());
            $this->assertSame(1, DB::table(CategoryCatalog::PAIR_OVERRIDES)->where(['category_id' => $this->c['A1']->id, 'hidden' => 0, 'source' => 'tenant'])->count());
            $this->assertSame(1, DB::table('category_product')->where(['category_id' => $own->id, 'product_id' => $p1->id, 'category_type' => 'tenant'])->count());
            // Own product in a central category (typed tenant product).
            app(ProductCategorySelection::class)->save($ownProduct, [(string) $B]);
            $this->assertEqualsCanonicalizing([$p1->id, $ownProduct->id], $catalog->productIds($B));

            // A product in a centrally hidden category keeps that link when saved (Phase -1): H is not in the form.
            app(ProductCategorySelection::class)->save($p2, [(string) $this->c['A1']->id, (string) $this->c['H1']->id]);
            $this->assertSame(0, DB::table(CategoryCatalog::PAIR_OVERRIDES)->whereIn('category_id', [$this->c['H']->id, $this->c['H1']->id])->count());

            // Sort: override rows for central pairs, pivot for own categories; whereHas filter by key.
            $assign->sortProducts($catalog->find($B), [$ownProduct->id, $p1->id]);
            $this->assertSame([$ownProduct->id, $p1->id], $catalog->productIds($B));
            $this->assertSame([$ownProduct->id, $p1->id], $catalog->find($B)->products->pluck('id')->all());
            $this->assertSame(
                [$p1->id],
                Product::query()->whereHas('categories', fn ($q) => $q->where('categories.id', $ownKey))->pluck('id')->all(),
            );
            $counts = MergedCategory::query()->withCount('products')->get()->pluck('products_count', 'id');
            $this->assertSame(2, (int) $counts[$B]);
            $this->assertSame(0, (int) $counts[$A]);

            // Un-hide A again; validation accepts keys / refs only in the merged set.
            app(ProductCategorySelection::class)->save($p1, [(string) $A, (string) $B]);
            $this->assertSame([$p1->id], $catalog->productIds($A));
            $rules = app(ProductCategorySelection::class)->itemRules();
            $v = validator(['ids' => [(string) $A, 'c:' . $this->c['H']->id, 'nonsense']], ['ids.*' => $rules]);
            $this->assertEqualsCanonicalizing(['ids.1', 'ids.2'], array_keys($v->errors()->messages()));
        });

        // Invariant 1: central pairs untouched (6 inserted, D's removed by its soft delete).
        $this->assertSame(5, DB::connection(config('tenancy.database.central_connection'))->table('category_product')->whereIn('category_id', collect($this->c)->pluck('id'))->count());
    }

    // ── sync guards ─────────────────────────────────────────────────────────────────────────────────────────────

    public function test_sync_skips_central_mode_tenants_on_every_category_path(): void
    {
        $p = $this->catalogue();
        $this->flip();
        $before = $this->inTenant(fn () => [Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->count(), DB::table('category_product')->count()]);

        $new = $this->centralCategory('Brand New');
        $this->centralPair($new, $p['p1']['central_product']->id);
        $this->scope([$this->c['A']->id, $this->c['B']->id, $new->id]);
        $this->assertSame(0, app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'categories'));
        tenancy()->end();
        app(TenantCatalogSyncService::class)->syncForTenant($this->tenant, ['categories']);
        tenancy()->end();
        $this->syncLinks([$p['p1']['product']->id]);

        $this->assertSame($before, $this->inTenant(fn () => [Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->count(), DB::table('category_product')->count()]));
        // ...and the new central category is visible at once, with its pair.
        $this->inTenant(fn () => $this->assertSame([$p['p1']['product']->id], app(CategoryCatalog::class)->productIds($new->id)));

        // Fan-out bumps the global tag instead of copying.
        $version = CacheVersion::get(CategoryCatalog::CACHE_TAG);
        app(\App\Services\Tenant\CentralCatalogTenantSyncService::class)->syncAllTenants(['categories']);
        CacheVersion::flushMemo();
        $this->assertGreaterThan($version, CacheVersion::get(CategoryCatalog::CACHE_TAG));
    }

    // ── tooling ─────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_dedup_migrate_dry_run_idempotent_verify_and_config_refs(): void
    {
        $p = $this->catalogue();
        $copyA = $this->copyId($this->c['A']);
        $copyB = $this->copyId($this->c['B']);
        $this->inTenant(function () use ($copyA, $copyB, $p) {
            // Vendor state in sync mode.
            Category::query()->whereKey($copyB)->update(['featured' => false]);
            Category::query()->whereKey($copyA)->update(['order_number' => 7]);
            DB::table('category_product')->where(['category_id' => $copyA, 'product_id' => $p['p1']['product']->id])->update(['sort_order' => 3]);
            DB::table('category_product')->insert(['category_id' => $copyB, 'product_id' => $p['p2']['product']->id, 'source' => 'tenant', 'sort_order' => 1]);
            $own = Category::query()->create(['parent_id' => $copyA, 'active' => true]);
            $own->syncTranslations(['en' => ['name' => 'Own', 'slug' => 'own']]);
            Category::query()->findOrFail($copyA)->translations()->create(['language_id' => Language::query()->where('code', 'de')->value('id'), 'field' => 'name', 'value' => 'Alpha DE']);
            File::query()->create(['key' => 'thumb', 'path' => 'categories/b.avif', 'storage_type' => 'public', 'file_type' => 'image', 'model_type' => Category::class, 'model_id' => $copyB]);
            DB::table('banners')->insert(['url' => "https://shop.test/new-in?category_id={$copyA}&x=1", 'image_path' => 'b.png', 'serial_number' => 1, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('mobile_app_settings')->insert(['key' => 'p4_test', 'value' => json_encode(['category_ids' => [$copyB, 999999], 'title' => 'categories']), 'created_at' => now(), 'updated_at' => now()]);
        });
        // The orphan copy of D is a drift the next categories sync fixes: run it, as the deploy steps say.
        $this->syncCategories();

        $runner = app(DedupRunner::class);
        $dry = $runner->run($this->tenant, 'categories', 'dry-run');
        $this->assertSame('done', $dry['status'], json_encode($dry));
        $this->assertSame(0, $this->inTenant(fn () => DB::table(CategoryCatalog::OVERRIDES)->count() + DB::table(CategoryCatalog::PAIR_OVERRIDES)->count()));
        $this->assertSame(1, $dry['counts']['config_unknown_ids']);
        $this->assertSame(2, $dry['counts']['config_refs_rewritten']);

        $first = $runner->run($this->tenant, 'categories', 'migrate');
        $this->assertSame('done', $first['status'], json_encode($first));
        // A (order), B (featured + thumb) and the hidden H (inserted inactive while it was a draft: kept, Phase -1 rule).
        $this->assertSame(3, $first['counts']['overrides_written']);
        $this->assertSame(1, $first['counts']['sort_overrides']);
        $this->assertSame(1, $first['counts']['links_written']);
        $this->assertSame(1, $first['counts']['typed_parents']);
        $this->assertSame(1, $first['counts']['tenant_only_translations']);
        $second = $runner->run($this->tenant, 'categories', 'migrate');
        foreach (['overrides_written', 'overrides_refreshed', 'sort_overrides', 'links_written', 'typed_parents', 'tenant_only_translations', 'config_refs_rewritten'] as $k) {
            $this->assertSame(0, $second['counts'][$k], "{$k} must be idempotent");
        }

        $this->inTenant(function () use ($copyA, $copyB) {
            $this->assertSame("https://shop.test/new-in?category_id=c%3A{$this->c['A']->id}&x=1", DB::table('banners')->value('url'));
            $this->assertSame(['c:' . $this->c['B']->id, 999999], json_decode((string) DB::table('mobile_app_settings')->where('key', 'p4_test')->value('value'), true)['category_ids']);
            // Readers accept refs in sync mode too (rollback-safe).
            $this->assertSame($copyA, CategoryCatalog::filterId('c:' . $this->c['A']->id));
            $this->assertSame($copyB, CategoryCatalog::filterId((string) $copyB));
        });

        $verify = $runner->run($this->tenant, 'categories', 'verify');
        $this->assertSame(0, $verify['diffs'], json_encode($verify, JSON_PRETTY_PRINT));

        // sync-check: parity metrics are gate-able and 0 after migrate; the dedup verify is recorded.
        $report = app(CatalogSyncCheck::class)->run([(string) $this->tenant->id], ['categories'])[0]['sections']['categories'];
        foreach (['pairs_missing_in_copy', 'sort_overrides_pending', 'links_pending', 'category_overrides_pending', 'stale_central_links'] as $metric) {
            $this->assertSame(0, $report[$metric], $metric);
        }
        $this->assertSame(0, $report['dedup_verify_diffs']);
        $this->assertSame(0, Artisan::call('catalog:sync-check', ['--tenant' => [(string) $this->tenant->id], '--section' => ['categories'], '--gate' => ['dedup_verify_diffs:0', 'pairs_missing_in_copy:0']]));

        // A drift after migrate fails verify (fail-closed).
        $this->inTenant(fn () => Category::query()->whereKey($copyA)->update(['active' => false]));
        $this->assertGreaterThan(0, $runner->run($this->tenant, 'categories', 'verify')['diffs']);
    }

    public function test_storefront_menu_category_page_and_mobile_api_identical_in_sync_and_central_mode_after_migrate(): void
    {
        $p = $this->catalogue();
        $copyA = $this->copyId($this->c['A']);
        $this->inTenant(function () use ($copyA, $p) {
            Category::query()->whereKey($copyA)->update(['order_number' => 5]);
            DB::table('category_product')->where(['category_id' => $copyA, 'product_id' => $p['p1']['product']->id])->update(['sort_order' => 2]);
            $own = Category::query()->create(['parent_id' => $copyA, 'active' => true, 'order_number' => 1]);
            $own->syncTranslations(['en' => ['name' => 'Own Kid', 'slug' => 'own-kid']]);
            $own->products()->attach($p['p2']['product']->id, ['sort_order' => 0, 'source' => 'tenant']);
        });
        $this->syncCategories();

        $map = $this->inTenant(fn () => Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->get()
            ->mapWithKeys(fn (Category $c) => [$c->id => $c->central_category_id ?? $c->id + CategoryKey::OWN_OFFSET])->all());
        $sync = $this->snapshot($map);

        $this->assertSame('done', app(DedupRunner::class)->run($this->tenant, 'categories', 'migrate')['status']);
        $this->assertSame(0, app(DedupRunner::class)->run($this->tenant, 'categories', 'verify')['diffs']);
        $this->flip();
        $central = $this->snapshot(null);

        $this->assertNotEmpty($sync['menu']);
        $this->assertSame($sync, $central);
    }

    /** Storefront + mobile output with category ids normalised to keys ($map: tenant id → key; null = already keys). */
    private function snapshot(?array $map): array
    {
        $norm = function (mixed $id) use ($map) {
            return $id === null ? null : ($map === null ? (int) $id : $map[(int) $id]);
        };
        $tree = function ($categories) use (&$tree, $norm) {
            return $categories->map(fn ($c) => [
                'id' => $norm($c->id), 'parent' => $norm($c->parent_id), 'name' => $c->translationValue('name'), 'name_fr' => $c->translationValue('name', 'fr'),
                'slug' => $c->slug, 'thumb' => $c->thumb_url, 'count' => $c->products_count ?? null, 'active' => (bool) $c->active,
                'children' => $c->relationLoaded('children') ? $tree($c->children) : null,
            ])->values()->all();
        };

        // StorefrontRepository is a scoped singleton with a per-instance memo; the test app outlives a "request".
        app()->forgetScopedInstances();
        app()->forgetInstance(StorefrontRepository::class);
        $out = $this->inTenant(function () use ($tree, $norm) {
            request()->setLaravelSession(app('session')->driver()); // the country resolver reads the session
            $repo = new StorefrontRepository();
            $category = $repo->categoryBySlug('alpha');

            return [
                'menu' => $tree($repo->rootCategoriesWithChildren()),
                'active' => $tree($repo->activeCategories()),
                'page' => $repo->paginatedProductsByCategory($category, [], 50)->getCollection()->pluck('id')->all(),
                'related' => $repo->productsByCategory($category, 10)->pluck('id')->all(),
                'filter' => $repo->paginatedProducts(['category_id' => $category->id], 50)->getCollection()->pluck('id')->sort()->values()->all(),
                'breadcrumb' => Product::query()->with('categories')->get()->mapWithKeys(fn ($p) => [$p->id => $p->categories->map(fn ($c) => $norm($c->id))->sort()->values()->all()])->all(),
                'picker' => collect(app(TenantPanelRepository::class)->categoryTreeOptions())->map(fn ($o) => [$norm($o['id']), $o['name'], $o['depth']])->sortBy(0)->values()->all(),
            ];
        });

        $headers = ['X-Tenant' => $this->tenant->id, 'Accept' => 'application/json'];
        $normJson = function (array $rows) use (&$normJson, $norm) {
            return array_map(fn ($r) => ['id' => $norm($r['id'])] + ['children' => $normJson($r['children'] ?? [])] + array_diff_key($r, ['id' => 1, 'children' => 1]), $rows);
        };
        $out['api_categories'] = $normJson($this->getJson('/api/mobile/v1/categories', $headers)->assertOk()->json('data'));
        $show = $this->getJson('/api/mobile/v1/categories/alpha', $headers)->assertOk()->json('data');
        $out['api_category'] = $normJson([$show['category']])[0];
        $out['api_products'] = collect($show['products']['data'])->pluck('id')->all();

        return $out;
    }

    // ── panel ───────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_panel_lists_merged_categories_edits_overrides_and_creates_own_under_central(): void
    {
        $this->catalogue();
        $this->flip();
        $A = $this->c['A']->id;

        $data = $this->actingAsTenantAdmin()->getJson($this->tenantUrl('/admin/categories/data?draw=1&start=0&length=50'))->assertOk()->json('data');
        $this->assertCount(4, $data, 'A, A1, B and the orphan H1 (hidden H, soft-deleted D are not listed)');

        // HTML pages sit behind the onboarding-tour middleware.
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());
        $this->actingAsTenantAdmin()->get($this->tenantUrl("/admin/categories/{$A}/edit"))->assertOk()->assertSee('Managed by the platform in this language', false);
        $this->actingAsTenantAdmin()->patchJson($this->tenantUrl("/admin/categories/{$A}/featured"))->assertOk();
        $this->inTenant(fn () => $this->assertSame(0, (int) DB::table(CategoryCatalog::OVERRIDES)->where('central_category_id', $A)->value('featured')));
        $this->actingAsTenantAdmin()->deleteJson($this->tenantUrl("/admin/categories/{$A}"))->assertForbidden();

        $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/categories'), [
            'active_locale' => 'en', 'parent_id' => "c:{$A}", 'order_number' => 3, 'active' => 1,
            'translations' => ['en' => ['name' => 'Vendor Sub']],
        ])->assertOk();
        $this->inTenant(function () use ($A) {
            $own = Category::query()->whereNull('central_category_id')->latest('id')->firstOrFail();
            $this->assertSame(['central', $A, null], [$own->parent_type, (int) $own->catalog_parent_id, $own->parent_id]);
            $merged = app(CategoryCatalog::class)->find('t:' . $own->id);
            $this->assertSame($A, $merged->parent_id);
            $this->assertSame('vendor-sub', $merged->slug);
        });
        $this->actingAsTenantAdmin()->deleteJson($this->tenantUrl('/admin/categories/' . $this->inTenant(fn () => Category::query()->whereNull('central_category_id')->latest('id')->value('id') + CategoryKey::OWN_OFFSET)))->assertOk();
        $this->assertSame(0, $this->inTenant(fn () => Category::query()->whereNull('central_category_id')->count()));
    }

    public function test_migration_is_idempotent_and_reversible(): void
    {
        $path = 'database/migrations/tenant/2026_11_26_000001_create_tenant_category_overrides_tables.php';
        $this->inTenant(function () use ($path) {
            $migration = require base_path($path);
            $migration->up(); // already applied by tenant creation: no-op
            $this->assertTrue(Schema::hasTable(CategoryCatalog::OVERRIDES));
            $migration->down();
            $this->assertFalse(Schema::hasTable(CategoryCatalog::OVERRIDES));
            $this->assertFalse(Schema::hasTable(CategoryCatalog::PAIR_OVERRIDES));
            $this->assertFalse(Schema::hasColumn('categories', 'parent_type'));
            CatalogRefColumns::flush();
            $this->assertFalse(CategoryCatalog::schemaReady());
            $migration->down();
            $migration->up();
            $migration->up();
            CatalogRefColumns::flush();
            $this->assertTrue(CategoryCatalog::schemaReady());
        });
    }
}
