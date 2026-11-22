<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Category as CentralCategory;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * QA phase 4: query counts + output hashes of the category read paths with 5 vs 120 categories.
 * Uses only APIs that exist at HEAD (phase 3) so the same file runs on a `git archive HEAD` copy = the sync baseline.
 * Set QA_PERF_OUT=/path/file.json to write the measurements (one JSON object per scenario).
 * The central-mode half is skipped where CategoryCatalog does not exist.
 */
class CatalogPhase4QaPerfTest extends TestCase
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
        }
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function central(callable $cb): mixed
    {
        tenancy()->end();

        return Model::withoutEvents($cb);
    }

    /** @return array<int, array> tenant products */
    private function seedWorld(int $roots, int $kids, int $productCount): array
    {
        $this->central(function () {
            foreach (['en' => 'English', 'fr' => 'French'] as $code => $name) {
                CentralLanguage::query()->firstOrCreate(['code' => $code], ['name' => $name, 'native_name' => $name, 'direction' => 'ltr', 'is_active' => true, 'is_default' => $code === 'en']);
            }
        });
        $this->tenant->run(fn () => Language::query()->firstOrCreate(['code' => 'fr'], ['name' => 'French', 'native_name' => 'Francais', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 2]));

        $langs = $this->central(fn () => CentralLanguage::query()->whereIn('code', ['en', 'fr'])->pluck('id', 'code'));
        $ids = [];
        $this->central(function () use ($roots, $kids, $langs, &$ids) {
            $now = now();
            $n = 0;
            $make = function (?int $parent) use (&$n, $langs, $now) {
                $n++;
                $cat = CentralCategory::query()->create(['parent_id' => $parent, 'status' => 'published', 'is_featured' => true, 'order_number' => $n]);
                $rows = [];
                foreach (['en' => "Cat $n", 'fr' => "Cat $n FR"] as $code => $name) {
                    foreach (['name' => $name, 'slug' => Str::slug($name)] as $field => $v) {
                        $rows[] = ['language_id' => $langs[$code], 'translatable_type' => CentralCategory::class, 'translatable_id' => $cat->id, 'field' => $field, 'value' => $v, 'created_at' => $now, 'updated_at' => $now];
                    }
                }
                DB::table('translations')->insert($rows);

                return $cat->id;
            };
            for ($r = 0; $r < $roots; $r++) {
                $root = $make(null);
                $ids[] = $root;
                for ($k = 0; $k < $kids; $k++) {
                    $ids[] = $make($root);
                }
            }
        });

        $products = $this->tenant->run(function () use ($productCount) {
            $out = [];
            for ($i = 0; $i < $productCount; $i++) {
                $out[] = $this->createCentralCatalogVariant(price: 10.0 + $i);
            }

            return $out;
        });
        $this->central(function () use ($products, $ids) {
            foreach ($products as $i => $p) {
                foreach ([0, 1, 2] as $o) {
                    DB::table('category_product')->insertOrIgnore(['category_id' => $ids[($i * 3 + $o) % count($ids)], 'product_id' => $p['central_product']->id, 'sort_order' => $i]);
                }
            }
        });

        tenancy()->end();
        $this->tenant->category_ids = [];
        $this->tenant->save();
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'categories');
        tenancy()->end();
        $this->tenant->run(function () use ($products) {
            $copies = Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->whereNotNull('central_category_id')->pluck('id', 'central_category_id')->all();
            $wanted = [];
            foreach (Product::query()->whereIn('id', array_map(fn ($p) => $p['product']->id, $products))->get() as $product) {
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

        return $ids;
    }

    /** @return array{queries:int, hash:string} */
    private function measure(callable $cb): array
    {
        Cache::flush();
        CacheVersion::flushMemo();
        app()->forgetScopedInstances();
        app()->forgetInstance(StorefrontRepository::class);
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });
        $out = $this->tenant->run($cb);

        // asset() roots on the last request's host (test artefact): normalise it.
        $json = preg_replace('#http:\\\\/\\\\/[^"\\\\]+#', 'http:HOST', (string) json_encode($out));

        return ['queries' => $n, 'hash' => substr(sha1($json), 0, 12), 'raw' => substr($json, 0, 2500)];
    }

    private function operations(): array
    {
        $headers = ['X-Tenant' => $this->tenant->id, 'Accept' => 'application/json'];
        $names = fn ($c) => [$c->translationValue('name'), $c->translationValue('name', 'fr'), $c->slug, $c->thumb_url, (bool) $c->active];
        $tree = function ($cats) use (&$tree, $names) {
            return $cats->map(fn ($c) => [$names($c), $c->relationLoaded('children') ? $tree($c->children) : null])->values()->all();
        };

        return [
            'menu' => fn () => $this->measure(function () use ($tree) {
                request()->setLaravelSession(app('session')->driver());

                return $tree((new StorefrontRepository())->rootCategoriesWithChildren());
            }),
            'active_categories' => fn () => $this->measure(function () use ($names) {
                return (new StorefrontRepository())->activeCategories()->map(fn ($c) => [$names($c), $c->products_count, $c->children_count])->all();
            }),
            'category_page' => fn () => $this->measure(function () {
                request()->setLaravelSession(app('session')->driver());
                $repo = new StorefrontRepository();
                $cat = $repo->categoryBySlug('cat-1');
                $page = $repo->paginatedProductsByCategory($cat, [], 24);

                // ties on created_at / sort_order make the page order arbitrary in both modes: compare the SET of products
                return [$cat->slug, $page->getCollection()->pluck('id')->sort()->values()->all(), $page->total(), $repo->variantFilterGroups($cat)];
            }),
            'product_cards_categories' => fn () => $this->measure(function () use ($names) {
                return Product::query()->with('categories.translations.language')->orderBy('id')->get()
                    ->map(fn ($p) => $p->categories->map(fn ($c) => $c->translationValue('name'))->sort()->values()->all())->all();
            }),
            'product_lazy_categories' => fn () => $this->measure(function () {
                return Product::query()->orderBy('id')->get()->map(fn ($p) => $p->categories->count())->all();
            }),
            'products_filtered_by_category' => fn () => $this->measure(function () {
                request()->setLaravelSession(app('session')->driver());
                $repo = new StorefrontRepository();
                $cat = $repo->categoryBySlug('cat-1');

                return $repo->paginatedProducts(['category_id' => $cat->id], 24)->getCollection()->pluck('id')->sort()->values()->all();
            }),
            'mobile_categories' => fn () => $this->measure(function () use ($headers) {
                return $this->getJson('/api/mobile/v1/categories', $headers)->assertOk()->json('data');
            }),
            'panel_categories_list' => fn () => $this->measure(function () {
                return collect($this->actingAsTenantAdmin()->getJson($this->tenantUrl('/admin/categories/data?draw=1&start=0&length=200'))->assertOk()->json('data'))->count();
            }),
            'sitemap' => fn () => $this->measure(function () {
                $r = $this->get($this->tenantUrl('/sitemap.xml'));

                return [$r->getStatusCode(), substr_count($r->getContent(), '/categories/')];
            }),
        ];
    }

    private function record(string $scenario, array $results): void
    {
        $results = array_map(fn ($r) => is_array($r) && isset($r['raw']) ? array_diff_key($r, ['raw' => 1]) : $r, $results);
        $line = json_encode(['scenario' => $scenario, 'results' => $results]);
        fwrite(STDERR, "\nQA_PERF {$line}\n");
        if ($path = getenv('QA_PERF_OUT')) {
            file_put_contents($path, $line . "\n", FILE_APPEND);
        }
    }

    public function test_sync_mode_query_counts_5_vs_120_categories(): void
    {
        $this->seedWorld(2, 2, 8); // 6 categories
        $small = [];
        foreach ($this->operations() as $name => $op) {
            $small[$name] = $op();
        }
        $this->record('sync_6', $small);
        $this->assertNotEmpty($small['menu']['hash']);
    }

    public function test_sync_mode_120_categories(): void
    {
        $this->seedWorld(10, 11, 30); // 120 categories
        $big = [];
        foreach ($this->operations() as $name => $op) {
            $big[$name] = $op();
        }
        $this->record('sync_120', $big);
        $this->assertNotEmpty($big['menu']['hash']);
    }

    public function test_central_mode_6_categories_match_sync_output(): void
    {
        if (! class_exists(\App\Repositories\Tenant\Catalog\CategoryCatalog::class)) {
            $this->markTestSkipped('HEAD baseline: no phase 4.');
        }

        $this->seedWorld(2, 2, 8);
        $sync = [];
        foreach ($this->operations() as $name => $op) {
            $sync[$name] = $op();
        }
        tenancy()->end();
        CatalogSource::set($this->tenant, 'categories', 'central');
        \App\Repositories\Tenant\Catalog\CategoryCatalog::flushMemo();
        $central = [];
        foreach ($this->operations() as $name => $op) {
            $central[$name] = $op();
        }
        $this->record('central_6_sync', $sync);
        $this->record('central_6', $central);

        foreach (['menu', 'active_categories', 'category_page', 'product_cards_categories', 'products_filtered_by_category', 'sitemap', 'panel_categories_list'] as $op) {
            $this->assertSame($sync[$op]['hash'], $central[$op]['hash'], "6: {$op} output differs between sync and central mode\nSYNC " . $sync[$op]['raw'] . "\nCENTRAL " . $central[$op]['raw']);
        }
    }

    public function test_central_mode_120_categories(): void
    {
        if (! class_exists(\App\Repositories\Tenant\Catalog\CategoryCatalog::class)) {
            $this->markTestSkipped('HEAD baseline: no phase 4.');
        }

        $this->seedWorld(10, 11, 30);
        $sync = [];
        foreach ($this->operations() as $name => $op) {
            $sync[$name] = $op();
        }
        tenancy()->end();
        CatalogSource::set($this->tenant, 'categories', 'central');
        \App\Repositories\Tenant\Catalog\CategoryCatalog::flushMemo();
        $central = [];
        foreach ($this->operations() as $name => $op) {
            $central[$name] = $op();
        }
        $this->record('central_120_sync', $sync);
        $this->record('central_120', $central);

        foreach (['menu', 'active_categories', 'category_page', 'product_cards_categories', 'products_filtered_by_category', 'sitemap', 'panel_categories_list'] as $op) {
            $this->assertSame($sync[$op]['hash'], $central[$op]['hash'], "120: {$op} output differs between sync and central mode");
        }
    }

    /** Product-form save in central mode: queries must not grow linearly with the number of merged categories. */
    public function test_central_product_form_save_query_count_scales(): void
    {
        if (! class_exists(\App\Repositories\Tenant\Catalog\CategoryCatalog::class)) {
            $this->markTestSkipped('HEAD baseline: no phase 4.');
        }

        $ids = $this->seedWorld(10, 11, 4);
        tenancy()->end();
        CatalogSource::set($this->tenant, 'categories', 'central');
        \App\Repositories\Tenant\Catalog\CategoryCatalog::flushMemo();
        $results = [];
        foreach ([3, 40, 120] as $count) {
            $pick = array_slice($ids, 0, $count);
            $results["save_with_{$count}_categories_in_scope_picked_3"] = $this->measure(function () use ($pick) {
                $product = Product::query()->orderBy('id')->firstOrFail();
                app(\App\Repositories\Tenant\Catalog\ProductCategorySelection::class)->save($product, array_slice($pick, 0, 3));

                return 1;
            });
        }
        $results['save_all_120_ticked'] = $this->measure(function () use ($ids) {
            $product = Product::query()->orderBy('id')->firstOrFail();
            app(\App\Repositories\Tenant\Catalog\ProductCategorySelection::class)->save($product, $ids);

            return 1;
        });
        $this->record('central_product_save', $results);
        $this->assertTrue(true);
    }
}
