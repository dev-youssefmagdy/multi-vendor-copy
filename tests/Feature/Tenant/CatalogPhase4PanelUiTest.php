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
 * Dedup plan phase 4, tenant panel UI (§11 "Frontend — Phase 4"): categories pages in sync vs central mode, read-only
 * platform fields, override save, own category CRUD, product assignment and sort.
 */
class CatalogPhase4PanelUiTest extends TestCase
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


    // ── panel UI ────────────────────────────────────────────────────────────────────────────────────────────────

    private function page(string $path)
    {
        // HTML pages sit behind the onboarding-tour middleware.
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());

        return $this->actingAsTenantAdmin()->get($this->tenantUrl($path));
    }

    public function test_sync_mode_pages_unchanged(): void
    {
        $this->catalogue();
        $copy = $this->copyId($this->c['A']);

        $this->page('/admin/categories')->assertOk();
        $this->page("/admin/categories/{$copy}/edit")->assertOk()
            ->assertDontSee('Managed by the platform in this language', false)
            ->assertDontSee('This category is managed by the platform', false);
        $this->page('/admin/categories/sort')->assertOk();
        $this->page("/admin/categories/{$copy}/products")->assertOk();
        $rows = $this->actingAsTenantAdmin()->getJson($this->tenantUrl('/admin/categories/data?draw=1&start=0&length=50'))->assertOk()->json('data');
        $this->assertStringNotContainsString('Platform category', json_encode($rows));
    }

    public function test_central_mode_platform_category_form_is_read_only_and_labelled(): void
    {
        $this->catalogue();
        $this->flip();
        $A = $this->c['A']->id;

        $html = $this->page("/admin/categories/{$A}/edit")->assertOk()
            ->assertSee('This category is managed by the platform', false)
            ->assertSee('Managed by the platform in this language', false)
            ->getContent();
        // Platform-language (en, fr) texts are readonly, the tenant-only language (de) stays editable.
        $this->assertMatchesRegularExpression('/name="translations\[en\]\[name\]"[^>]*readonly/', $html);
        $this->assertMatchesRegularExpression('/name="translations\[fr\]\[slug\]"[^>]*readonly/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="translations\[de\]\[name\]"[^>]*readonly/', $html);
        $this->assertMatchesRegularExpression('/<select[^>]*name="parent_id"[^>]*disabled/', $html);

        $rows = $this->actingAsTenantAdmin()->getJson($this->tenantUrl('/admin/categories/data?draw=1&start=0&length=50'))->assertOk()->json('data');
        $this->assertStringContainsString('Platform category', json_encode($rows));
        // Platform rows never offer Delete.
        foreach ($rows as $row) {
            $this->assertStringNotContainsString('categories/' . $A . '"', str_contains($row['actions'], 'data-action-method="DELETE"') ? $row['actions'] : '');
        }
    }

    public function test_central_mode_override_save_keeps_platform_fields(): void
    {
        $this->catalogue();
        $this->flip();
        $A = $this->c['A']->id;

        $this->actingAsTenantAdmin()->putJson($this->tenantUrl("/admin/categories/{$A}"), [
            'active_locale' => 'en', 'central_category_id' => $A, 'parent_id' => 'c:' . $this->c['B']->id, 'order_number' => 9, 'active' => 0, 'featured' => 1,
            'translations' => ['en' => ['name' => 'Hacked', 'slug' => 'hacked'], 'de' => ['name' => 'Alpha DE', 'slug' => 'alpha-de']],
        ])->assertOk();

        $this->inTenant(function () use ($A) {
            $row = DB::table(CategoryCatalog::OVERRIDES)->where('central_category_id', $A)->first();
            $this->assertSame([9, 0], [(int) $row->order_number, (int) $row->active]);
            $merged = app(CategoryCatalog::class)->find((string) $A);
            $this->assertNull($merged->parent_id, 'platform parent not changeable');
            $this->assertSame('Alpha', $merged->translationValue('name', 'en'));
            $this->assertSame(0, Category::query()->whereNull('central_category_id')->count(), 'no own row created');
        });
    }

    public function test_central_mode_own_category_crud_under_platform_parent_and_cycle_guard(): void
    {
        $this->catalogue();
        $this->flip();
        $A = $this->c['A']->id;

        $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/categories'), [
            'active_locale' => 'en', 'parent_id' => "c:{$A}", 'order_number' => 1, 'active' => 1,
            'translations' => ['en' => ['name' => 'Mine']],
        ])->assertOk();
        $ownId = $this->inTenant(fn () => (int) Category::query()->whereNull('central_category_id')->value('id'));
        $key = $ownId + CategoryKey::OWN_OFFSET;

        $html = $this->page("/admin/categories/{$key}/edit")->assertOk()->assertDontSee('This category is managed by the platform', false)->getContent();
        $this->assertDoesNotMatchRegularExpression('/name="translations\[en\]\[name\]"[^>]*readonly/', $html);
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="parent_id"[^>]*disabled/', $html);
        $this->assertStringNotContainsString('value="' . $key . '"', preg_replace('/<select[^>]*name="parent_id".*?<\/select>/s', '$0', $html) ?: '', 'own category is not offered as its own parent');

        // Cycle: own category as its own parent is rejected.
        $this->actingAsTenantAdmin()->postJson($this->tenantUrl("/admin/categories/{$key}/validate"), [
            'active_locale' => 'en', 'parent_id' => "t:{$ownId}", 'translations' => ['en' => ['name' => 'Mine']],
        ])->assertStatus(422)->assertJsonValidationErrors('parent_id');

        $this->actingAsTenantAdmin()->putJson($this->tenantUrl("/admin/categories/{$key}"), [
            'active_locale' => 'en', 'parent_id' => '', 'order_number' => 2, 'active' => 1, 'translations' => ['en' => ['name' => 'Mine Renamed']],
        ])->assertOk();
        $this->inTenant(fn () => $this->assertSame('Mine Renamed', app(CategoryCatalog::class)->find("t:{$ownId}")->translationValue('name', 'en')));

        $this->actingAsTenantAdmin()->deleteJson($this->tenantUrl("/admin/categories/{$key}"))->assertOk();
        $this->assertSame(0, $this->inTenant(fn () => Category::query()->whereNull('central_category_id')->count()));
        $this->actingAsTenantAdmin()->deleteJson($this->tenantUrl("/admin/categories/{$A}"))->assertForbidden();
    }

    public function test_central_mode_sort_pages_and_product_assignment(): void
    {
        $p = $this->catalogue();
        $this->flip();
        $A = $this->c['A']->id;

        $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/categories'), [
            'active_locale' => 'en', 'order_number' => 0, 'active' => 1, 'translations' => ['en' => ['name' => 'Own Root']],
        ])->assertOk();
        $ownId = $this->inTenant(fn () => (int) Category::query()->whereNull('central_category_id')->value('id'));
        $key = $ownId + CategoryKey::OWN_OFFSET;

        $sort = $this->page('/admin/categories/sort')->assertOk()->assertSee('data-id="' . $A . '"', false)->assertSee('data-id="' . $key . '"', false)->assertSee('Platform category', false);
        $this->assertNotNull($sort);
        $this->actingAsTenantAdmin()->postJson($this->tenantUrl('/admin/categories/sort'), ['ids' => [$key, $A]])->assertOk();
        $this->inTenant(function () use ($A, $ownId) {
            $this->assertSame(1, (int) DB::table(CategoryCatalog::OVERRIDES)->where('central_category_id', $A)->value('order_number'));
            $this->assertSame(0, (int) Category::query()->whereKey($ownId)->value('order_number'));
        });

        // Product assignment through the product-form save path (keys), platform + own category.
        $product = $p['p1']['product'];
        $this->inTenant(fn () => app(ProductCategorySelection::class)->save(Product::query()->findOrFail($product->id), [(string) $A, (string) $key]));
        $this->page("/admin/categories/{$key}/products")->assertOk()->assertSee('data-id="' . $product->id . '"', false);
        $this->page("/admin/categories/{$A}/products")->assertOk()->assertSee('data-id="' . $product->id . '"', false);
        $this->actingAsTenantAdmin()->postJson($this->tenantUrl("/admin/categories/{$A}/products/sort"), ['ids' => [$product->id]])->assertOk();
        $this->actingAsTenantAdmin()->postJson($this->tenantUrl("/admin/categories/{$key}/products/sort"), ['ids' => [$product->id]])->assertOk();
    }
}
