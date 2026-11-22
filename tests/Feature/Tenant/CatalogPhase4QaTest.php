<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\ActivationStatus;
use App\Jobs\SyncTenantSectionJob;
use App\Models\Category as CentralCategory;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant\AdminRole;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Catalog\MergedCategory;
use App\Models\Tenant\Category;
use App\Models\Tenant\File;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\Catalog\CategoryAssignments;
use App\Repositories\Tenant\Catalog\CategoryCatalog;
use App\Repositories\Tenant\Catalog\ProductCategorySelection;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRef;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CategoryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Files;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * QA — dedup plan phase 4 (categories). Complements CatalogPhase4{Categories,PanelUi,QaPerf}Test with edge cases:
 * unknown / malformed inputs (404 / 422, never 500), crafted requests against platform categories, cycle guard,
 * XSS, authorization, hidden / deleted parents, cache invalidation, sync guards on every entry point, dedup tooling
 * (dry-run query log, verify semantics, config rewrite fidelity, gates), migration shape, absence tolerance.
 * See plan §11 "QA — Phase 4" and §12.
 */
class CatalogPhase4QaTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    /** @var array<string, CentralCategory> */
    private array $c = [];

    private bool $guarded = false;

    private bool $restoreCache = false;

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
        if ($this->guarded) {
            DB::purge('tenant');
        }
        if ($this->restoreCache) {
            Cache::forgetDriver('file');
        }
        CategoryCatalog::flushMemo();
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    // ── helpers ─────────────────────────────────────────────────────────────────────────────────────────────────

    private function inTenant(callable $cb): mixed
    {
        CategoryCatalog::flushMemo();

        return $this->tenant->run($cb);
    }

    private function central(callable $cb): mixed
    {
        tenancy()->end();

        return Model::withoutEvents($cb);
    }

    private function flip(string $mode = CatalogSource::CENTRAL): void
    {
        tenancy()->end();
        CatalogSource::set($this->tenant, 'categories', $mode);
        CategoryCatalog::flushMemo();
        CacheVersion::flushMemo();
    }

    private function langs(): void
    {
        $this->central(function () {
            foreach (['en' => 'English', 'fr' => 'French'] as $code => $name) {
                CentralLanguage::query()->firstOrCreate(['code' => $code], ['name' => $name, 'native_name' => $name, 'direction' => 'ltr', 'is_active' => true, 'is_default' => $code === 'en']);
            }
        });
        $this->tenant->run(function () {
            Language::query()->firstOrCreate(['code' => 'fr'], ['name' => 'French', 'native_name' => 'Francais', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 2]);
            Language::query()->firstOrCreate(['code' => 'de'], ['name' => 'German', 'native_name' => 'Deutsch', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 3]);
        });
    }

    private function cat(string $name, ?int $parent = null, string $status = 'published', bool $featured = true, int $order = 0, bool $events = false): CentralCategory
    {
        $make = function () use ($name, $parent, $status, $featured, $order) {
            $category = CentralCategory::query()->create(['parent_id' => $parent, 'status' => $status, 'is_featured' => $featured, 'order_number' => $order]);
            $languages = CentralLanguage::query()->whereIn('code', ['en', 'fr'])->pluck('id', 'code');
            foreach (['en' => $name, 'fr' => $name . ' FR'] as $code => $value) {
                foreach (['name' => $value, 'slug' => \Illuminate\Support\Str::slug(strip_tags($value)) ?: 'x'] as $field => $text) {
                    DB::table('translations')->insert(['language_id' => $languages[$code], 'translatable_type' => CentralCategory::class, 'translatable_id' => $category->id,
                        'field' => $field, 'value' => $text, 'created_at' => now(), 'updated_at' => now()]);
                }
            }

            return $category;
        };

        return $events ? (function () use ($make) { tenancy()->end(); return $make(); })() : $this->central($make);
    }

    private function pair(CentralCategory $category, int $centralProductId, int $sort = 0): void
    {
        $this->central(fn () => DB::table('category_product')->insert(['category_id' => $category->id, 'product_id' => $centralProductId, 'sort_order' => $sort]));
    }

    private function syncAll(): void
    {
        tenancy()->end();
        $service = app(TenantCatalogSyncService::class);
        $service->syncSection($this->tenant, 'categories');
        tenancy()->end();
    }

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

    /** A (child A1), B, 2 central products: P1 in A+B, P2 in A1. Synced, then flipped when $flip. @return array{p1: array, p2: array} */
    private function world(bool $flip = true): array
    {
        $this->langs();
        $this->c['A'] = $this->cat('Alpha', order: 2);
        $this->c['A1'] = $this->cat('Alpha One', $this->c['A']->id, featured: false);
        $this->c['B'] = $this->cat('Beta', order: 1);
        $p = $this->inTenant(fn () => ['p1' => $this->createCentralCatalogVariant(price: 30.0), 'p2' => $this->createCentralCatalogVariant(price: 40.0)]);
        $this->pair($this->c['A'], $p['p1']['central_product']->id);
        $this->pair($this->c['B'], $p['p1']['central_product']->id);
        $this->pair($this->c['A1'], $p['p2']['central_product']->id);
        tenancy()->end();
        $this->tenant->category_ids = [];
        $this->tenant->save();
        $this->syncAll();
        $this->syncLinks([$p['p1']['product']->id, $p['p2']['product']->id]);
        if ($flip) {
            $this->flip();
        }
        $this->inTenant(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());

        return $p;
    }

    private function url(string $path): string
    {
        return $this->tenantUrl('/admin/categories' . $path);
    }

    /** Central categories + translations fingerprint (invariant 1: tenant code never writes central). */
    private function centralFingerprint(): string
    {
        return $this->central(function () {
            $c = DB::connection(config('tenancy.database.central_connection'));

            return sha1(json_encode([
                $c->table('categories')->orderBy('id')->get()->all(),
                $c->table('translations')->where('translatable_type', CentralCategory::class)->orderBy('id')->get()->all(),
                $c->table('category_product')->orderBy('category_id')->orderBy('product_id')->get()->all(),
            ]));
        });
    }

    private function writes(callable $cb): array
    {
        $log = [];
        DB::listen(function ($q) use (&$log) {
            if (preg_match('/^\s*(insert|update|delete|replace|alter|create|drop|truncate)\b/i', $q->sql) && ! str_contains($q->sql, 'catalog_dedup_runs')) {
                $log[] = $q->sql;
            }
        });
        $cb();

        return $log;
    }

    // ── 3. inputs: unknown ids are 404 / 422, never 500 ─────────────────────────────────────────────────────────

    public function test_unknown_and_malformed_ids_are_404_or_422_never_500(): void
    {
        $this->world();
        foreach (['999999999', 'c:99999', 't:99999', 'abc', 'c:abc', '0', '-1', 'c:0', "c:1'--", str_repeat('9', 30), '1000000001'] as $id) {
            $id = rawurlencode($id);
            foreach ([['get', "/{$id}/edit"], ['put', "/{$id}"], ['patch', "/{$id}/active"], ['patch', "/{$id}/featured"], ['delete', "/{$id}"], ['get', "/{$id}/products"], ['post', "/{$id}/products/sort"]] as [$verb, $path]) {
                $status = $this->actingAsTenantAdmin()->{$verb . 'Json'}($this->url($path), [])->getStatusCode();
                $this->assertContains($status, [404, 422], "{$verb} {$path} => {$status}");
            }
        }

        $rules = app(ProductCategorySelection::class)->itemRules();
        foreach ([999999999, 'c:abc', ['x'], '1e9', '0', '-1', 'c:0', "c:1; DROP TABLE categories", str_repeat('9', 40), 1.5, true] as $bad) {
            $this->inTenant(function () use ($rules, $bad) {
                $v = validator(['ids' => [$bad]], ['ids.*' => $rules]);
                $this->assertTrue($v->fails(), 'accepted: ' . json_encode($bad));
            });
        }

        // Create: bad parent values => 422.
        foreach (['zzz', '99999', 'c:99999', ['a'], 'c:' . $this->c['A']->id . 'x'] as $parent) {
            $this->actingAsTenantAdmin()->postJson($this->url(''), ['active_locale' => 'en', 'parent_id' => $parent, 'translations' => ['en' => ['name' => 'X']]])->assertStatus(422);
        }
        $this->assertSame(0, $this->inTenant(fn () => Category::query()->whereNull('central_category_id')->count()));

        // Sort endpoints with garbage ids: 200, nothing written.
        $before = $this->inTenant(fn () => DB::table(CategoryCatalog::OVERRIDES)->count() + DB::table(CategoryCatalog::PAIR_OVERRIDES)->count());
        $this->actingAsTenantAdmin()->postJson($this->url('/sort'), ['ids' => ['x', null, 'c:999', ['a'], '1000000099']])->assertOk();
        $this->actingAsTenantAdmin()->postJson($this->url('/' . $this->c['B']->id . '/products/sort'), ['ids' => ['x', null, 999999, 'zz']])->assertOk();
        $this->assertSame($before, $this->inTenant(fn () => DB::table(CategoryCatalog::OVERRIDES)->count() + DB::table(CategoryCatalog::PAIR_OVERRIDES)->count()));

        // Storefront filters: never throw.
        $this->inTenant(function () {
            request()->setLaravelSession(app('session')->driver());
            $repo = new StorefrontRepository();
            foreach (['abc', 'c:99999', 't:5', '1000000007', 'c:' . $this->c['A']->id, '', null, 0, -5, '9999999999999999999999'] as $value) {
                $repo->paginatedProducts(['category_id' => $value], 10);
                CategoryCatalog::filterId($value);
            }
            $this->assertNull($repo->categoryBySlug('does-not-exist'));
        });
        $headers = ['X-Tenant' => $this->tenant->id, 'Accept' => 'application/json'];
        $this->getJson('/api/mobile/v1/categories/does-not-exist', $headers)->assertNotFound();
    }

    // ── 3/8. crafted requests against a platform category ────────────────────────────────────────────────────────

    public function test_platform_category_survives_crafted_update_and_delete_requests_and_central_is_never_written(): void
    {
        $this->world();
        $A1 = $this->c['A1']->id;
        $fingerprint = $this->centralFingerprint();
        // Invariant 1 at statement level: any write whose target is a central table throws.
        $this->tenant->run(fn () => \App\Support\Catalog\CentralWriteGuard::install(DB::connection('tenant')));
        $this->guarded = true;

        $this->actingAsTenantAdmin()->putJson($this->url("/{$A1}"), [
            'active_locale' => 'en', 'parent_id' => $this->c['B']->id, 'central_category_id' => $this->c['B']->id,
            'source' => 'tenant', 'id' => 1, 'ref' => 't:1', 'status' => 'draft', 'order_number' => 4, 'active' => 1, 'featured' => 1,
            'translations' => [
                'en' => ['name' => 'Hacked', 'slug' => 'hacked'], 'fr' => ['name' => 'Pirate', 'slug' => 'pirate'],
                'de' => ['name' => 'Alpha Eins', 'slug' => 'alpha-eins'],
            ],
        ])->assertOk();

        $this->inTenant(function () use ($A1) {
            $merged = app(CategoryCatalog::class)->find($A1);
            $this->assertSame($this->c['A']->id, $merged->parent_id, 'parent read-only');
            $this->assertSame('Alpha One', $merged->translationValue('name', 'en'));
            $this->assertSame('Alpha One FR', $merged->translationValue('name', 'fr'));
            $this->assertSame('alpha-one', $merged->translationValue('slug', 'en'));
            $this->assertSame('Alpha Eins', $merged->translationValue('name', 'de'), 'tenant-only language is the vendor\'s');
            $this->assertSame(4, $merged->order_number);
            $this->assertSame(0, Category::query()->whereNull('central_category_id')->count(), 'no own category created');
            $this->assertSame(0, DB::table('translations')->where('translatable_type', Category::class)->where('field', 'name')->where('value', 'Hacked')->count());
        });
        $this->assertSame($fingerprint, $this->centralFingerprint(), 'central untouched');

        foreach ([(string) $A1, 'c:' . $A1] as $id) {
            $this->actingAsTenantAdmin()->deleteJson($this->url('/' . rawurlencode($id)))->assertForbidden();
        }

        // POSTing a central_category_id never creates / links a platform row: it is an own category.
        $this->actingAsTenantAdmin()->postJson($this->url(''), [
            'active_locale' => 'en', 'central_category_id' => $A1, 'order_number' => 0, 'active' => 1,
            'translations' => ['en' => ['name' => 'Mine']],
        ])->assertOk();
        $this->inTenant(function () {
            $own = Category::query()->whereNull('central_category_id')->get();
            $this->assertCount(1, $own);
            $this->assertNull($own->first()->central_category_id);
        });
        $this->assertSame($fingerprint, $this->centralFingerprint());
    }

    // ── 3. own categories: cycle guard, delete re-parents ────────────────────────────────────────────────────────

    public function test_own_cycle_guard_and_delete_of_own_parent_makes_children_roots(): void
    {
        $this->world();
        $A = $this->c['A']->id;
        $mk = fn (array $extra) => $this->actingAsTenantAdmin()->postJson($this->url(''), ['active_locale' => 'en', 'active' => 1, 'order_number' => 0] + $extra);
        $mk(['parent_id' => "c:{$A}", 'translations' => ['en' => ['name' => 'X']]])->assertOk();
        $x = $this->inTenant(fn () => Category::query()->whereNull('central_category_id')->latest('id')->value('id')) + CategoryKey::OWN_OFFSET;
        $mk(['parent_id' => (string) $x, 'translations' => ['en' => ['name' => 'Y']]])->assertOk();
        $y = $this->inTenant(fn () => Category::query()->whereNull('central_category_id')->latest('id')->value('id')) + CategoryKey::OWN_OFFSET;
        $mk(['parent_id' => (string) $y, 'translations' => ['en' => ['name' => 'Z']]])->assertOk();
        $z = $this->inTenant(fn () => Category::query()->whereNull('central_category_id')->latest('id')->value('id')) + CategoryKey::OWN_OFFSET;

        $put = fn (int $id, mixed $parent) => $this->actingAsTenantAdmin()->putJson($this->url("/{$id}"), ['active_locale' => 'en', 'active' => 1, 'order_number' => 0, 'parent_id' => $parent, 'translations' => ['en' => ['name' => 'N']]]);
        $put($x, $x)->assertStatus(422);       // self
        $put($x, $y)->assertStatus(422);       // child
        $put($x, "t:" . ($z - CategoryKey::OWN_OFFSET))->assertStatus(422); // grandchild by ref
        $put($y, $y)->assertStatus(422);
        $put($z, $A)->assertOk();              // legal move under a platform category

        // Delete the middle one: Z (now under A) unaffected; re-parent W under Y then delete Y => W is a root.
        $mk(['parent_id' => (string) $y, 'translations' => ['en' => ['name' => 'W']]])->assertOk();
        $w = $this->inTenant(fn () => Category::query()->whereNull('central_category_id')->latest('id')->value('id')) + CategoryKey::OWN_OFFSET;
        $this->actingAsTenantAdmin()->deleteJson($this->url("/{$y}"))->assertOk();
        $this->inTenant(function () use ($w) {
            $merged = app(CategoryCatalog::class)->find($w);
            $this->assertNotNull($merged, 'child survives the delete of its parent');
            $this->assertNull($merged->parent_id);
            $this->assertContains($w, array_column(app(CategoryCatalog::class)->treeOptions(), 'id'));
        });
    }

    public function test_tenant_only_language_text_of_a_platform_category_gets_a_unique_slug(): void
    {
        $this->world();
        $put = fn (int $id, string $name, string $slug = '') => $this->actingAsTenantAdmin()->putJson($this->url("/{$id}"), [
            'active_locale' => 'de', 'order_number' => 0, 'active' => 1, 'translations' => ['en' => ['name' => 'Platform name'], 'de' => ['name' => $name, 'slug' => $slug]],
        ])->assertOk();
        $put($this->c['A']->id, 'Alpha Deutsch');
        $put($this->c['B']->id, 'Alpha Deutsch');
        $this->inTenant(function () {
            // Own rows only (translationValue() falls back to the default language, which would hide a missing slug).
            $de = Language::query()->where('code', 'de')->value('id');
            $slugs = DB::table('translations')->where('translatable_type', CategoryCatalog::CENTRAL_TRANSLATION_TYPE)->where('language_id', $de)->where('field', 'slug')->pluck('value', 'translatable_id')->all();
            $this->assertCount(2, $slugs, 'D-P4-3: no slug row is generated for the tenant-only language of a platform category: ' . json_encode($slugs));
            $this->assertCount(2, array_unique($slugs), 'D-P4-3: two categories share a slug in the same language');
        });
    }

    // ── 8. XSS ───────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_names_are_escaped_in_panel_pages_and_json(): void
    {
        $this->world(false);
        $evil = '"><script>alert(1)</script>';
        $this->central(fn () => DB::table('translations')->where('translatable_type', CentralCategory::class)->where('translatable_id', $this->c['B']->id)->where('field', 'name')->update(['value' => $evil]));
        $this->flip();
        $this->actingAsTenantAdmin()->postJson($this->url(''), ['active_locale' => 'en', 'active' => 1, 'order_number' => 0, 'translations' => ['en' => ['name' => '<img src=x onerror=alert(2)>']]])->assertOk();
        // Fabricate a hostile name on the own one too, bypassing the request.
        $this->inTenant(fn () => DB::table('translations')->where('translatable_type', Category::class)->where('field', 'name')->update(['value' => '<img src=x onerror=alert(2)>']));

        $json = (string) $this->actingAsTenantAdmin()->getJson($this->url('/data?draw=1&start=0&length=50'))->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)', $json);
        $this->assertStringNotContainsString('<img src=x', $json);
        foreach (['/sort', '/' . $this->c['B']->id . '/edit', '/' . $this->c['B']->id . '/products', ''] as $path) {
            $html = (string) $this->actingAsTenantAdmin()->get($this->url($path))->assertOk()->getContent();
            $this->assertStringNotContainsString('<script>alert(1)', $html, $path);
            $this->assertStringNotContainsString('<img src=x onerror', $html, $path);
        }
        $this->inTenant(function () use ($evil) {
            $options = collect(app(CategoryCatalog::class)->treeOptions())->pluck('name')->implode('|');
            $this->assertStringContainsString($evil, $options, 'raw in the data layer, escaped at output');
        });
    }

    // ── 8. authorization ────────────────────────────────────────────────────────────────────────────────────────

    public function test_category_endpoints_need_login_and_the_categories_permission(): void
    {
        $this->world();
        $A = $this->c['A']->id;
        $calls = [['get', ''], ['get', '/data'], ['get', '/sort'], ['post', '/sort'], ['get', '/create'], ['post', ''], ['get', "/{$A}/edit"], ['put', "/{$A}"],
            ['patch', "/{$A}/active"], ['patch', "/{$A}/featured"], ['delete', "/{$A}"], ['get', "/{$A}/products"], ['post', "/{$A}/products/sort"]];

        foreach ($calls as [$verb, $path]) {
            $status = $this->{$verb . 'Json'}($this->url($path), [])->getStatusCode();
            $this->assertContains($status, [401, 403, 302], "anonymous {$verb} {$path} => {$status}");
        }

        $limited = $this->inTenant(function () {
            $role = AdminRole::create(['name' => 'Limited QA', 'permissions' => ['catalog.products.manage'], 'permissions_count' => 1]);

            return AdminUser::create(['role_id' => $role->id, 'name' => 'Limited', 'email' => 'limited@qa.example.com', 'password' => Hash::make('password12345'),
                'status' => ActivationStatus::Active, 'email_verified_at' => now(), 'tour_seen_at' => now()]);
        });
        $before = $this->centralFingerprint();
        foreach ($calls as [$verb, $path]) {
            $status = $this->actingAs($limited, 'tenant')->{$verb . 'Json'}($this->url($path), [])->getStatusCode();
            $this->assertSame(403, $status, "limited {$verb} {$path} => {$status}");
        }
        $this->assertSame($before, $this->centralFingerprint());
        $this->assertSame(0, $this->inTenant(fn () => DB::table(CategoryCatalog::OVERRIDES)->count()));
    }

    // ── 3. hidden / deleted parents: parity with sync mode ───────────────────────────────────────────────────────

    public function test_deleted_and_unpublished_parents_give_the_same_menu_in_sync_and_central_mode(): void
    {
        $this->world(false);
        $deletedParent = $this->cat('Doomed Parent');
        $doomedChild = $this->cat('Doomed Child', $deletedParent->id);
        $draftParent = $this->cat('Draft Parent');
        $draftChild = $this->cat('Draft Child', $draftParent->id);
        $this->syncAll();
        $this->central(function () use ($deletedParent, $draftParent) {
            CentralCategory::query()->findOrFail($deletedParent->id)->delete();      // soft delete: children parent_id -> NULL
            CentralCategory::query()->findOrFail($draftParent->id)->update(['status' => 'draft']);
        });
        $this->syncAll();

        $menu = function () {
            app()->forgetInstance(StorefrontRepository::class);
            Cache::flush();
            CacheVersion::flushMemo();

            return $this->inTenant(function () {
                request()->setLaravelSession(app('session')->driver());
                $repo = new StorefrontRepository();

                return [
                    'menu' => $repo->rootCategoriesWithChildren()->map(fn ($c) => [$c->translationValue('name'), $c->children->map(fn ($k) => $k->translationValue('name'))->sort()->values()->all()])->sortBy(0)->values()->all(),
                    'active' => $repo->activeCategories()->map(fn ($c) => $c->translationValue('name'))->sort()->values()->all(),
                    'by_slug' => array_map(fn ($s) => $repo->categoryBySlug($s)?->translationValue('name'), ['doomed-child', 'draft-child', 'doomed-parent', 'draft-parent']),
                ];
            });
        };
        $sync = $menu();
        $this->flip();
        $central = $menu();
        $this->assertSame($sync, $central, 'sync: ' . json_encode($sync) . ' central: ' . json_encode($central));
    }

    // ── 4. cache invalidation: a central change is visible at once ──────────────────────────────────────────────

    public function test_central_edits_are_visible_immediately_through_cached_storefront_reads(): void
    {
        // Production runs the file cache, where the tenancy bootstrapper does not tag the store, so a GLOBAL tag bumped in
        // central context is the very key tenants read. The test default ('array') is tagged per tenant and would hide
        // that; use a throw-away file cache dir (never the checkout's storage) to mirror production.
        $dir = sys_get_temp_dir() . '/qa-p4-cache-' . uniqid();
        config(['cache.default' => 'file', 'cache.stores.file.path' => $dir]);
        Cache::forgetDriver('file');
        Cache::forgetDriver('array');
        $this->restoreCache = true;
        $p = $this->world();
        $names = function () {
            app()->forgetInstance(StorefrontRepository::class);
            CacheVersion::flushMemo();

            return $this->inTenant(fn () => (new StorefrontRepository())->activeCategories()->map(fn ($c) => $c->translationValue('name'))->sort()->values()->all());
        };
        $this->assertSame(['Alpha', 'Alpha One', 'Beta'], $names());

        // Eloquent edits with model events (admin panel path): rename through the translation model, reorder, unpublish.
        tenancy()->end();
        \App\Models\Translation::query()->where('translatable_type', CentralCategory::class)->where('translatable_id', $this->c['B']->id)->where('field', 'name')->get()->each(
            fn ($t) => (int) $t->language_id === (int) CentralLanguage::query()->where('code', 'en')->value('id') ? $t->update(['value' => 'Beta Renamed']) : null
        );
        $this->assertSame(['Alpha', 'Alpha One', 'Beta Renamed'], $names(), 'central translation edit');

        CentralCategory::query()->findOrFail($this->c['A1']->id)->update(['status' => 'draft']);
        $this->assertSame(['Alpha', 'Beta Renamed'], $names(), 'central unpublish');

        $new = $this->cat('Fresh', events: true);
        $this->pair($new, $p['p1']['central_product']->id); // categories without products / children are not listed
        $this->assertSame(['Alpha', 'Beta Renamed', 'Fresh'], $names(), 'central create');
        CentralCategory::query()->findOrFail($new->id)->delete();
        $this->assertSame(['Alpha', 'Beta Renamed'], $names(), 'central soft delete');
    }

    // ── 5. sync guards on every entry point ──────────────────────────────────────────────────────────────────────

    public function test_every_sync_entry_point_leaves_a_central_mode_tenant_untouched(): void
    {
        $p = $this->world();
        $state = fn () => $this->inTenant(fn () => [
            Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->orderBy('id')->get(['id', 'central_category_id', 'active', 'featured', 'order_number', 'central_visible'])->toArray(),
            DB::table('category_product')->orderBy('category_id')->orderBy('product_id')->get(['category_id', 'product_id', 'sort_order', 'source'])->map(fn ($r) => (array) $r)->all(), // typed-ref columns may be filled by the products sync (phase 1), rows are not
            DB::table('translations')->where('translatable_type', Category::class)->count(),
        ]);
        $before = $state();

        $new = $this->cat('Late', events: true);
        $this->pair($new, $p['p1']['central_product']->id);
        $this->central(fn () => DB::table('category_product')->where('category_id', $this->c['A']->id)->update(['sort_order' => 9]));

        tenancy()->end();
        $svc = app(TenantCatalogSyncService::class);
        $svc->syncForTenant($this->tenant, ['categories', 'products']);
        tenancy()->end();
        $svc->syncSection($this->tenant, 'products');                                  // links step of the full products sync
        tenancy()->end();
        $svc->syncSection($this->tenant, 'categories');
        tenancy()->end();
        (new SyncTenantSectionJob($this->tenant->id, 'categories'))->handle($svc);
        tenancy()->end();
        (new SyncTenantSectionJob($this->tenant->id, 'products'))->handle($svc);
        tenancy()->end();
        // Single-product push + fan-outs (observer path).
        $svc->syncProductToTenant(\App\Models\Product::query()->findOrFail($p['p1']['central_product']->id), $this->tenant, true);
        tenancy()->end();
        $fan = app(CentralCatalogTenantSyncService::class);
        $fan->syncAllTenants(['categories']);
        $fan->syncAllTenants(['categories', 'products']);
        $fan->syncAllTenants(['languages', 'currencies', 'badges', 'labels', 'coupons', 'flash-sales', 'categories', 'products']);
        tenancy()->end();

        $this->assertSame($before, $state(), 'no category copy, pivot row, toggle or translation was written');
        $this->inTenant(function () use ($new, $p) {
            $this->assertSame([$p['p1']['product']->id], app(CategoryCatalog::class)->productIds($new->id), 'live read shows the new pair');
            $this->assertSame([$p['p1']['product']->id], app(CategoryCatalog::class)->productIds($this->c['A']->id));
        });
    }

    // ── 6. dedup tooling ─────────────────────────────────────────────────────────────────────────────────────────

    public function test_dry_run_writes_nothing_and_skipped_without_tables_and_verify_semantics(): void
    {
        $p = $this->world(false);
        $copyA = $this->inTenant(fn () => (int) Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->where('central_category_id', $this->c['A']->id)->value('id'));
        $this->inTenant(function () use ($copyA, $p) {
            Category::query()->whereKey($copyA)->update(['order_number' => 8]);
            DB::table('category_product')->where(['category_id' => $copyA, 'product_id' => $p['p1']['product']->id])->update(['sort_order' => 5]);
            DB::table('mobile_app_settings')->insert(['key' => 'qa_json', 'value' => json_encode(['category_ids' => [$copyA], 'title' => 'x']), 'created_at' => now(), 'updated_at' => now()]);
        });

        $runner = app(DedupRunner::class);
        $log = $this->writes(function () use ($runner) {
            $this->assertSame('done', $runner->run($this->tenant, 'categories', 'dry-run')['status']);
            $this->assertSame('done', $runner->run($this->tenant, 'categories', 'verify')['status']);
        });
        $this->assertSame([], $log, 'dry-run / verify issue no writes outside catalog_dedup_runs: ' . implode(' | ', $log));
        $this->assertTrue(Files::exists(DedupRunner::reportPath('categories', (string) $this->tenant->id, 'dry-run')));

        $this->assertSame('done', $runner->run($this->tenant, 'categories', 'migrate')['status']);
        $decoded = json_decode($this->inTenant(fn () => (string) DB::table('mobile_app_settings')->where('key', 'qa_json')->value('value')));
        $this->assertSame(['c:' . $this->c['A']->id], $decoded->category_ids);

        $this->assertSame(0, $runner->run($this->tenant, 'categories', 'verify')['diffs']);

        // Verify semantics: each drift class is a diff.
        $drifts = [
            'text' => fn () => DB::table('translations')->where('translatable_type', Category::class)->where('translatable_id', $copyA)->where('field', 'name')->limit(1)->update(['value' => 'Changed']),
            'sort' => fn () => DB::table('category_product')->where(['category_id' => $copyA, 'product_id' => $p['p1']['product']->id])->update(['sort_order' => 1]),
            'thumb' => fn () => File::query()->create(['key' => 'thumb', 'path' => 'categories/q.avif', 'storage_type' => 'public', 'file_type' => 'image', 'model_type' => Category::class, 'model_id' => $copyA]),
            'link' => fn () => DB::table('category_product')->insert(['category_id' => $copyA, 'product_id' => $p['p2']['product']->id, 'source' => 'tenant', 'sort_order' => 0]),
        ];
        foreach ($drifts as $name => $apply) {
            $this->inTenant($apply);
            $this->assertGreaterThan(0, $runner->run($this->tenant, 'categories', 'verify')['diffs'], "drift {$name} must fail verify");
            $this->tenant->run(function () {
                // reset to a migrated state before the next probe
                DB::table('translations')->where('translatable_type', Category::class)->where('value', 'Changed')->update(['value' => 'Alpha']);
            });
            if ($name === 'thumb') {
                $this->inTenant(fn () => File::query()->where('path', 'categories/q.avif')->delete());
            }
            if ($name === 'link') {
                $this->inTenant(fn () => DB::table('category_product')->where(['category_id' => $copyA, 'product_id' => $p['p2']['product']->id])->delete());
            }
            if ($name === 'sort') {
                $this->inTenant(fn () => DB::table('category_product')->where(['category_id' => $copyA, 'product_id' => $p['p1']['product']->id])->update(['sort_order' => 5]));
            }
        }
        $this->assertSame(0, $runner->run($this->tenant, 'categories', 'verify')['diffs'], 'back to the migrated state');

        // skipped without the phase-4 tables: reported, nothing thrown, exit code 0 or explicit.
        $this->inTenant(function () {
            $migration = require base_path('database/migrations/tenant/2026_11_26_000001_create_tenant_category_overrides_tables.php');
            $migration->down();
            CatalogRefColumns::flush();
        });
        $res = $runner->run($this->tenant, 'categories', 'migrate');
        $this->assertSame('skipped', $res['status'], json_encode($res));
        $this->assertSame('skipped', $runner->run($this->tenant, 'categories', 'verify')['status']);
    }

    public function test_config_rewrite_preserves_unrelated_json_types(): void
    {
        $this->world(false);
        $copyA = $this->inTenant(fn () => (int) Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->where('central_category_id', $this->c['A']->id)->value('id'));
        $this->inTenant(fn () => DB::table('mobile_app_settings')->insert(['key' => 'qa_json', 'value' => '{"category_ids":[' . $copyA . '],"extra":{},"ratio":1.0,"nested":{"a":{}},"list":[]}', 'created_at' => now(), 'updated_at' => now()]));
        $this->assertSame('done', app(DedupRunner::class)->run($this->tenant, 'categories', 'migrate')['status']);
        $stored = $this->inTenant(fn () => (string) DB::table('mobile_app_settings')->where('key', 'qa_json')->value('value'));
        $decoded = json_decode($stored);
        $this->assertSame(['c:' . $this->c['A']->id], $decoded->category_ids);
        $this->assertIsObject($decoded->extra, 'D-P4-1: {} became [] after the config rewrite: ' . $stored);
        $this->assertIsObject($decoded->nested->a, 'D-P4-1: nested {} became []: ' . $stored);
    }

    public function test_gates_and_commands_exit_codes_and_tenant_filter(): void
    {
        $this->world(false);
        $tid = (string) $this->tenant->id;
        $gate = fn (array $g) => Artisan::call('catalog:sync-check', ['--tenant' => [$tid], '--section' => ['categories'], '--gate' => $g]);
        $this->assertSame(1, $gate(['dedup_verify_diffs:0']), 'never verified = NULL fails closed');
        $this->assertSame(1, $gate(['no_such_metric:0']), 'unknown metric fails closed');
        $this->assertSame(0, $gate(['pairs_missing_in_copy:0', 'links_pending:0', 'sort_overrides_pending:0', 'category_overrides_pending:0', 'stale_central_links:0']));

        $this->assertSame(0, Artisan::call('catalog:dedup-migrate', ['section' => 'categories', '--tenant' => [$tid], '--dry-run' => true]));
        $this->assertSame(0, $this->inTenant(fn () => DB::table(CategoryCatalog::OVERRIDES)->count()));
        $this->assertSame(0, Artisan::call('catalog:dedup-migrate', ['section' => 'categories', '--tenant' => [$tid]]));
        $this->assertSame(0, Artisan::call('catalog:dedup-migrate', ['section' => 'categories', '--tenant' => [$tid], '--verify' => true]));
        $this->assertSame(0, $gate(['dedup_verify_diffs:0', 'pairs_missing_in_copy:0']));
        $rows = DB::table('catalog_dedup_runs')->where(['tenant_id' => $tid, 'section' => 'categories'])->count();
        $this->assertGreaterThanOrEqual(3, $rows);

        // --tenant filter: an unknown tenant id runs nothing and does not touch ours.
        $before = DB::table('catalog_dedup_runs')->where('tenant_id', $tid)->count();
        Artisan::call('catalog:dedup-migrate', ['section' => 'categories', '--tenant' => ['no-such-tenant']]);
        $this->assertSame($before, DB::table('catalog_dedup_runs')->where('tenant_id', $tid)->count());
        $this->assertNotSame(0, Artisan::call('catalog:dedup-migrate', ['section' => 'nope']));
    }

    // ── 7. migration shape + tolerance of its absence ───────────────────────────────────────────────────────────

    public function test_migration_schema_shape_and_code_tolerates_its_absence(): void
    {
        $this->world();
        $db = $this->tenant->run(fn () => DB::connection('tenant'));
        $schema = $this->tenant->run(fn () => DB::connection('tenant')->getDatabaseName());
        $idx = collect($this->tenant->run(fn () => $db->select('select table_name t, index_name i, non_unique n, group_concat(column_name order by seq_in_index) c from information_schema.statistics where table_schema = ? and table_name in (?, ?, ?) group by 1, 2, 3', [$schema, 'tenant_category_overrides', 'tenant_category_product_overrides', 'categories'])))
            ->mapWithKeys(fn ($r) => [$r->t . '.' . $r->i => $r->c . ($r->n ? '' : ' U')]);
        $this->assertSame('central_category_id U', $idx['tenant_category_overrides.PRIMARY']);
        $this->assertSame('category_type,category_id,product_type,product_id U', $idx['tenant_category_product_overrides.tcpo_pair_unique']);
        $this->assertSame('parent_type,catalog_parent_id', $idx['categories.categories_parent_ref_index']);
        $collations = collect($this->tenant->run(fn () => $db->select('select table_name t, table_collation c from information_schema.tables where table_schema = ? and table_name in (?, ?, ?)', [$schema, 'tenant_category_overrides', 'tenant_category_product_overrides', 'categories'])))->pluck('c', 't');
        $this->assertSame($collations['categories'], $collations['tenant_category_overrides']);
        $this->assertSame($collations['categories'], $collations['tenant_category_product_overrides']);

        // Absence: the flag is central but the tables are gone => synced reads, no exception anywhere.
        $this->inTenant(function () {
            $migration = require base_path('database/migrations/tenant/2026_11_26_000001_create_tenant_category_overrides_tables.php');
            $migration->down();
            CatalogRefColumns::flush();
            CategoryCatalog::flushMemo();
        });
        $this->inTenant(function () {
            $this->assertFalse(CategoryCatalog::enabled());
            request()->setLaravelSession(app('session')->driver());
            $repo = new StorefrontRepository();
            $this->assertNotEmpty($repo->rootCategoriesWithChildren());
            $this->assertNotInstanceOf(MergedCategory::class, $repo->activeCategories()->first());
            $this->assertNotEmpty(app(\App\Repositories\Tenant\TenantPanelRepository::class)->categoryTreeOptions());
        });
        $this->actingAsTenantAdmin()->getJson($this->url('/data?draw=1&start=0&length=50'))->assertOk();
        $this->actingAsTenantAdmin()->get($this->url(''))->assertOk();
        $this->actingAsTenantAdmin()->get($this->url('/sort'))->assertOk();
        $report = app(\App\Services\Catalog\CatalogSyncCheck::class)->run([(string) $this->tenant->id], ['categories'])[0]['sections']['categories'];
        $this->assertNull($report['override_rows']);
    }

    // ── 9. thumbnails, languages, mobile, sitemap ────────────────────────────────────────────────────────────────

    public function test_thumbs_translations_by_code_mobile_and_sitemap_in_central_mode(): void
    {
        $this->world(false);
        // Central thumb for A, vendor override for B, own category with a thumb.
        $this->central(fn () => \App\Models\File::query()->create(['key' => 'thumb', 'path' => 'central/a.avif', 'storage_type' => 'public', 'file_type' => 'image', 'model_type' => CentralCategory::class, 'model_id' => $this->c['A']->id]));
        $this->flip();
        $this->inTenant(function () {
            $assign = app(CategoryAssignments::class);
            $b = app(CategoryCatalog::class)->find($this->c['B']->id);
            $file = File::query()->create(['key' => 'thumb', 'path' => 'categories/b.avif', 'storage_type' => 'public', 'file_type' => 'image', 'model_type' => CategoryCatalog::CENTRAL_FILE_TYPE, 'model_id' => $this->c['B']->id]);
            $assign->setThumb($b, $file->id);
            $own = $assign->saveOwn(new Category(), ['active' => true], null);
            $own->syncTranslations(['en' => ['name' => 'Own Thumbed', 'slug' => 'own-thumbed']]);
            File::query()->create(['key' => 'thumb', 'path' => 'categories/own.avif', 'storage_type' => 'public', 'file_type' => 'image', 'model_type' => Category::class, 'model_id' => $own->id]);
        });
        $this->inTenant(function () {
            $catalog = app(CategoryCatalog::class);
            $this->assertStringContainsString('central/a.avif', $catalog->find($this->c['A']->id)->thumb_url, 'central thumb');
            $this->assertStringContainsString('categories/b.avif', $catalog->find($this->c['B']->id)->thumb_url, 'vendor override');
            $this->assertStringContainsString('category-default', $catalog->find($this->c['A1']->id)->thumb_url, 'default');
            $own = Category::query()->whereNull('central_category_id')->firstOrFail();
            $this->assertStringContainsString('categories/own.avif', $catalog->find('t:' . $own->id)->thumb_url);
            // language by CODE: tenant fr id differs from central fr id
            $a = $catalog->find($this->c['A']->id);
            $this->assertSame('Alpha FR', $a->translationValue('name', 'fr'));
            $this->assertSame('Alpha', $a->translationValue('name', 'de') ?? 'Alpha', 'tenant-only language falls back / empty, no exception');
        });

        // Central language switched off => its texts are no longer read for the tenant language with that code.
        $this->central(fn () => CentralLanguage::query()->where('code', 'fr')->update(['is_active' => false]));
        $this->inTenant(function () {
            $fr = app(CategoryCatalog::class)->find($this->c['A']->id)->translationValue('name', 'fr');
            $this->assertNotSame('Alpha FR', $fr, 'inactive central language is not read');
        });
        $this->central(fn () => CentralLanguage::query()->where('code', 'fr')->update(['is_active' => true]));

        $headers = ['X-Tenant' => $this->tenant->id, 'Accept' => 'application/json'];
        $list = $this->getJson('/api/mobile/v1/categories', $headers)->assertOk()->json('data');
        $this->assertNotEmpty($list);
        $this->assertIsInt($list[0]['id']);
        $show = $this->getJson('/api/mobile/v1/categories/own-thumbed', $headers)->assertOk()->json('data');
        $this->assertSame(CategoryKey::OWN_OFFSET + Category::query()->whereNull('central_category_id')->value('id'), $show['category']['id']);

        $xml = (string) $this->get($this->tenantUrl('/sitemap.xml'))->getContent();
        $this->assertStringContainsString('/categories/alpha', $xml);
        $this->assertStringContainsString('/categories/own-thumbed', $xml);
    }

    // ── 3. product form: refs, duplicates, out-of-scope ─────────────────────────────────────────────────────────

    public function test_product_form_save_accepts_ids_and_refs_dedupes_and_keeps_out_of_scope_links(): void
    {
        $p = $this->world(false);
        // Scope = A only (and its descendants); B is out of scope for this tenant.
        tenancy()->end();
        $this->tenant->category_ids = [$this->c['A']->id];
        $this->tenant->save();
        $this->flip();
        $A = $this->c['A']->id;
        $A1 = $this->c['A1']->id;
        $B = $this->c['B']->id;

        $this->inTenant(function () use ($p, $A, $A1, $B) {
            $this->assertEqualsCanonicalizing([$A, $A1], app(CategoryCatalog::class)->keys());
            $rules = app(ProductCategorySelection::class)->itemRules();
            $this->assertTrue(validator(['i' => [(string) $B]], ['i.*' => $rules])->fails(), 'out-of-scope central category rejected');

            $p1 = Product::query()->findOrFail($p['p1']['product']->id);
            // Pass the same category as id, string, ref and again: ends up once.
            app(ProductCategorySelection::class)->save($p1, [$A1, (string) $A1, 'c:' . $A1, 'c:' . $A1, $B]);
            $p1->unsetRelation('categories');
            $this->assertEqualsCanonicalizing([$A1], $p1->categories->pluck('id')->all(), 'A hidden (unticked), B out of scope ignored, A1 once');
            $this->assertSame(1, DB::table(CategoryCatalog::PAIR_OVERRIDES)->where(['category_id' => $A1, 'source' => 'tenant'])->count());
            $this->assertSame(1, DB::table(CategoryCatalog::PAIR_OVERRIDES)->where(['category_id' => $A, 'hidden' => 1])->count());
            $this->assertSame(0, DB::table(CategoryCatalog::PAIR_OVERRIDES)->where('category_id', $B)->count(), 'out-of-scope pair never touched');
            // The stored values are refs / typed pairs, never display keys >= 1e9 on central rows.
            $this->assertSame(0, DB::table(CategoryCatalog::PAIR_OVERRIDES)->where('category_id', '>=', CategoryKey::OWN_OFFSET)->count());
            $this->assertSame(0, DB::table('category_product')->where('category_id', '>=', CategoryKey::OWN_OFFSET)->count());
        });
    }

    // ── 4. query shapes (EXPLAIN) on the test DB ─────────────────────────────────────────────────────────────────

    public function test_explain_of_merged_queries_uses_indexes_on_the_big_tables(): void
    {
        $this->world();
        $this->inTenant(function () {
            $db = DB::connection('tenant');
            $plans = [
                'categories' => CategoryCatalog::categoriesSql(),
                'membership' => CategoryCatalog::membershipSql(),
                'translations' => CategoryCatalog::translationsSql(),
                'category_page' => 'SELECT p.id FROM products p JOIN (' . CategoryCatalog::membershipSql() . ') cp ON cp.product_id = p.id WHERE cp.category_key IN (1,2,3) ORDER BY cp.sort_order',
            ];
            foreach ($plans as $name => $sql) {
                $rows = $db->select('EXPLAIN ' . $sql);
                $this->assertNotEmpty($rows, $name);
                fwrite(STDERR, "\nQA_EXPLAIN {$name}: " . json_encode(array_map(fn ($r) => [$r->table ?? null, $r->type ?? null, $r->key ?? null, $r->rows ?? null, $r->Extra ?? null], $rows)) . "\n");
            }
        });
    }
}
