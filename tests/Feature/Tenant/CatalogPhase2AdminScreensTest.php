<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\ActivationStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Country;
use App\Models\Language as CentralLanguage;
use App\Models\Product as CentralProduct;
use App\Models\ProductBadge as CentralBadge;
use App\Models\ProductEditRequest;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Models\TenantCountry;
use App\Repositories\Tenant\Catalog\BadgeCatalog;
use App\Repositories\Tenant\Catalog\FieldOverrideStore;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 2, frontend (§7 no-Livewire rule): the rebuilt central admin screens and the tenant badge pages.
 *
 *  - `admin.products.edit-requests*` (Blade + JSON): list filters / pagination / status badges, details JSON, approve
 *    stores only the changed (field, locale) pairs, reject (+ note validation), reset of one field, permission gate;
 *  - `admin.badges.sort` (Blade + JSON, was Livewire SortBadgeProducts): per-country order, global
 *    `CentralProductBadge` cache tag bumped (F1), central-mode tenants read the new order live;
 *  - tenant badge pages: country-aware "Sort order" link / sort tabs, read-only platform notice only in central mode.
 */
class CatalogPhase2AdminScreensTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        CatalogRefColumns::flush();
        CacheVersion::flushMemo();
        $this->setUpTenantPanel();
        $this->tenant->run(fn () => Language::query()->create([
            'code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_active' => true, 'is_default' => false, 'sort_order' => 2,
        ]));
        tenancy()->end();
        foreach (['en' => 'English', 'ar' => 'Arabic'] as $code => $name) {
            CentralLanguage::query()->firstOrCreate(['code' => $code], ['name' => $name, 'native_name' => $name, 'direction' => $code === 'ar' ? 'rtl' : 'ltr', 'is_active' => true, 'is_default' => $code === 'en']);
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
            DB::table('product_edit_requests')->where('tenant_id', $this->tenant->id)->delete();
        }
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function inTenant(callable $callback): mixed
    {
        $result = $this->tenant->run($callback);
        tenancy()->end();

        return $result;
    }

    private function actingAsCentralAdmin(array $permissions): AdminUser
    {
        tenancy()->end();
        $role = AdminRole::query()->create(['name' => 'Role ' . uniqid(), 'permissions' => $permissions, 'permissions_count' => count($permissions)]);
        $admin = AdminUser::query()->create(['role_id' => $role->id, 'name' => 'Admin', 'email' => uniqid('admin') . '@example.com', 'password' => Hash::make('password12345'), 'status' => ActivationStatus::Active]);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    private function request(int $productId, string $slug, array $requested, array $current, string $status = 'pending', ?string $note = null): ProductEditRequest
    {
        tenancy()->end();

        return ProductEditRequest::query()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $productId,
            'product_slug' => $slug,
            'requested_translations' => $requested,
            'current_translations' => $current,
            'status' => $status,
            'admin_note' => $note,
        ]);
    }

    /** Central product (en/ar name + description) and its tenant copy, synced the normal way. */
    private function syncedProduct(): array
    {
        $fixture = $this->inTenant(fn () => $this->createCentralCatalogVariant(price: 30.0));
        $central = $fixture['central_product'];
        tenancy()->end();
        Model::withoutEvents(fn () => $central->syncTranslations([
            'en' => ['name' => 'Central Name', 'description' => 'Central Desc'],
            'ar' => ['name' => 'اسم مركزي', 'description' => 'وصف'],
        ]));
        app(TenantCatalogSyncService::class)->syncProductToTenant(CentralProduct::query()->findOrFail($central->id), $this->tenant);
        tenancy()->end();

        return [$central, $fixture['product']];
    }

    // ── edit requests ───────────────────────────────────────────────────────────────────────────────────────────

    public function test_edit_request_list_filters_paginates_and_renders_without_livewire(): void
    {
        foreach (range(1, 22) as $i) {
            $this->request(900000 + $i, "er-list-{$i}", ['en' => ['name' => "New {$i}"]], ['en' => ['name' => "Old {$i}"]]);
        }
        $this->request(900100, 'er-list-rejected', ['en' => ['name' => 'X']], ['en' => ['name' => 'Y']], 'rejected', 'Not a fit for the catalog');
        $this->actingAsCentralAdmin(['catalog.product-edit-requests.manage']);

        $page = $this->get('http://localhost/admin/products/edit-requests')->assertOk()
            ->assertDontSee('wire:', false)
            ->assertSee('data-edit-requests', false)
            ->assertSee('badge-amber', false)
            ->assertDontSee('er-list-rejected');
        $this->assertSame(20, substr_count($page->getContent(), 'data-request-row='), 'pending is the default filter, 20 per page');
        $this->assertStringContainsString('page=2', $page->getContent(), 'link pagination');

        $this->get('http://localhost/admin/products/edit-requests?page=2')->assertOk()->assertSee('pagination-chip', false);

        $this->get('http://localhost/admin/products/edit-requests?status=rejected')->assertOk()
            ->assertSee('er-list-rejected')->assertSee('badge-red', false)->assertSee('Not a fit for the catalog')
            ->assertDontSee('data-er-approve', false);

        $search = $this->get('http://localhost/admin/products/edit-requests?status=all&search=er-list-17')->assertOk();
        $this->assertSame(1, substr_count($search->getContent(), 'data-request-row='));
        $this->get('http://localhost/admin/products/edit-requests?status=bogus')->assertOk()->assertSee('value="pending" selected', false);
    }

    public function test_edit_request_endpoints_approve_only_changed_pairs_reject_and_reset_one_field(): void
    {
        [, $product] = $this->syncedProduct();
        $approve = $this->request($product->id, $product->slug,
            ['en' => ['name' => 'Vendor EN', 'description' => 'Central Desc'], 'ar' => ['name' => 'اسم البائع', 'description' => 'وصف']],
            ['en' => ['name' => 'Central Name', 'description' => 'Central Desc'], 'ar' => ['name' => 'اسم مركزي', 'description' => 'وصف']]);
        $reject = $this->request(900200, 'er-reject', ['en' => ['name' => 'Nope']], ['en' => ['name' => 'Old']]);
        $this->actingAsCentralAdmin(['catalog.product-edit-requests.manage']);

        $this->getJson("http://localhost/admin/products/edit-requests/{$approve->id}")->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('request.status', 'pending')
            ->assertJsonPath('changes.en.name.requested', 'Vendor EN')
            ->assertJsonPath('changes.ar.name.requested', 'اسم البائع')
            ->assertJsonMissingPath('changes.en.description')
            ->assertJsonPath('overrides', []);
        tenancy()->end();

        $this->postJson("http://localhost/admin/products/edit-requests/{$approve->id}/approve")->assertOk()
            ->assertJsonPath('success', true)->assertSessionHas('status');
        tenancy()->end();

        $rows = $this->inTenant(fn () => DB::table(FieldOverrideStore::TABLE)->orderBy('locale')->get(['field', 'locale', 'value']));
        $this->assertSame([['name', 'ar', 'اسم البائع'], ['name', 'en', 'Vendor EN']],
            $rows->map(fn ($r) => [$r->field, $r->locale, $r->value])->all(), 'only the changed (field, locale) pairs');

        $this->getJson("http://localhost/admin/products/edit-requests/{$approve->id}")->assertOk()
            ->assertJsonPath('request.status', 'approved')
            ->assertJsonCount(2, 'overrides')
            ->assertJsonPath('overrides.0.this_request', true);
        tenancy()->end();

        // Reset one field + locale: only that pair goes back to the live central value.
        $this->postJson("http://localhost/admin/products/edit-requests/{$approve->id}/reset", ['scope' => 'field', 'field' => 'name', 'locale' => 'ar'])
            ->assertOk()->assertJsonPath('success', true);
        tenancy()->end();
        $this->assertSame([['name', 'en']], $this->inTenant(fn () => DB::table(FieldOverrideStore::TABLE)->get(['field', 'locale']))
            ->map(fn ($r) => [$r->field, $r->locale])->all());
        $values = $this->inTenant(fn () => Product::withoutGlobalScope('centralVisible')->findOrFail($product->id)->translationsByLocale(['name']));
        $this->assertSame(['Vendor EN', 'اسم مركزي'], [$values['en']['name'], $values['ar']['name']]);

        // Validation / state errors come back as JSON the page shows in place.
        $this->postJson("http://localhost/admin/products/edit-requests/{$approve->id}/reset", ['scope' => 'field', 'field' => 'price'])->assertStatus(422);
        $this->postJson("http://localhost/admin/products/edit-requests/{$approve->id}/approve")->assertStatus(422)->assertJsonPath('success', false);
        $this->postJson("http://localhost/admin/products/edit-requests/{$reject->id}/reject", ['admin_note' => str_repeat('x', 501)])->assertStatus(422);

        $this->postJson("http://localhost/admin/products/edit-requests/{$reject->id}/reject", ['admin_note' => 'Wrong product'])->assertOk()->assertJsonPath('success', true);
        $reject->refresh();
        $this->assertSame(['rejected', 'Wrong product'], [$reject->status->value, $reject->admin_note]);
        $this->postJson("http://localhost/admin/products/edit-requests/{$reject->id}/reject")->assertStatus(422);
        $this->postJson("http://localhost/admin/products/edit-requests/{$reject->id}/reset")->assertStatus(422);
        $this->getJson("http://localhost/admin/products/edit-requests/{$reject->id}")->assertOk()->assertJsonPath('request.admin_note', 'Wrong product');
        $this->getJson('http://localhost/admin/products/edit-requests/99999999')->assertNotFound();
    }

    public function test_edit_request_and_badge_sort_screens_require_their_permission(): void
    {
        $request = $this->request(900300, 'er-perm', ['en' => ['name' => 'A']], ['en' => ['name' => 'B']]);
        $badge = Model::withoutEvents(fn () => CentralBadge::query()->create(['text' => 'perm-sort-p2', 'active' => true]));
        $this->actingAsCentralAdmin(['catalog.products.manage']);

        // AdminPermission: 403, or a redirect to the dashboard when the role may see it — never the action.
        foreach ([
            $this->getJson('http://localhost/admin/products/edit-requests'),
            $this->postJson("http://localhost/admin/products/edit-requests/{$request->id}/approve"),
            $this->postJson("http://localhost/admin/products/edit-requests/{$request->id}/reject"),
            $this->postJson('http://localhost/admin/badges/perm-sort-p2/sort', ['ids' => []]),
            $this->get('http://localhost/admin/badges/perm-sort-p2/sort'),
        ] as $response) {
            $this->assertContains($response->status(), [302, 403]);
        }
        $this->assertSame('pending', $request->refresh()->status->value);
        $this->assertNotNull($badge->id);
    }

    // ── central badge sort ──────────────────────────────────────────────────────────────────────────────────────

    public function test_admin_badge_sort_is_blade_json_per_country_and_bumps_the_central_badge_tag(): void
    {
        // Livewire component removed (file check: an optimized composer classmap may still list the deleted class).
        $this->assertFileDoesNotExist(app_path('Livewire/Admin/Product/SortBadgeProducts.php'));

        $p = $this->inTenant(fn () => [$this->createCentralCatalogVariant(price: 30.0), $this->createCentralCatalogVariant(price: 40.0)]);
        [$a, $b] = [$p[0]['central_product']->id, $p[1]['central_product']->id];
        $country = Country::forceCreate(['name' => 'Egypt', 'iso2' => 'EG']);
        $badge = Model::withoutEvents(function () use ($a, $b, $country) {
            $badge = CentralBadge::query()->create(['text' => 'sort-p2', 'active' => true]);
            foreach ([[$a, null, 0], [$b, null, 1], [$a, $country->id, 0], [$b, $country->id, 1]] as [$product, $countryId, $sort]) {
                DB::table('product_badge_product')->insert(['product_badge_id' => $badge->id, 'product_id' => $product, 'country_id' => $countryId, 'sort_order' => $sort]);
            }

            return $badge;
        });
        CatalogSource::set($this->tenant, 'badges', CatalogSource::CENTRAL);
        $this->actingAsCentralAdmin(['catalog.badges.manage']);

        $this->get('http://localhost/admin/badges/sort-p2/sort')->assertOk()
            ->assertDontSee('wire:', false)->assertSee('data-badge-sort', false)->assertSee('Egypt')
            ->assertSee(route('admin.badges.sort.update', $badge), false);
        $this->get("http://localhost/admin/badges/sort-p2/sort?country_id={$country->id}")->assertOk()->assertSee('data-country-id="' . $country->id . '"', false);
        $this->get('http://localhost/admin/badges/sort-p2')->assertOk()->assertSee(route('admin.badges.sort', ['badge' => 'sort-p2', 'country_id' => null]), false);

        CacheVersion::flushMemo();
        $before = CacheVersion::get(BadgeCatalog::CACHE_TAG);

        $this->postJson('http://localhost/admin/badges/sort-p2/sort', ['ids' => [$b, $a], 'country_id' => $country->id])
            ->assertOk()->assertJson(['success' => true]);
        $this->postJson('http://localhost/admin/badges/sort-p2/sort', ['ids' => ['x']])->assertStatus(422);

        CacheVersion::flushMemo();
        $this->assertGreaterThan($before, CacheVersion::get(BadgeCatalog::CACHE_TAG), 'global CentralProductBadge tag bumped (F1)');

        $order = fn (?int $countryId) => DB::table('product_badge_product')->where('product_badge_id', $badge->id)
            ->where(fn ($q) => $countryId === null ? $q->whereNull('country_id') : $q->where('country_id', $countryId))
            ->orderBy('sort_order')->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        $this->assertSame([$b, $a], $order($country->id), 'country order saved');
        $this->assertSame([$a, $b], $order(null), 'default order untouched');

        // A central-mode tenant reads the new central order live (no sync).
        $this->inTenant(function () use ($badge, $p, $country) {
            $catalog = app(BadgeCatalog::class);
            $merged = $catalog->find('c:' . $badge->id);
            $this->assertSame([$p[1]['product']->id, $p[0]['product']->id], $catalog->productIds($merged, $country->id));
        });
    }

    // ── tenant badge pages ──────────────────────────────────────────────────────────────────────────────────────

    public function test_tenant_badge_pages_link_sort_per_country_and_show_platform_notice_only_in_central_mode(): void
    {
        $p = $this->inTenant(fn () => $this->createCentralCatalogVariant(price: 30.0));
        $country = Country::forceCreate(['name' => 'Morocco', 'iso2' => 'MA']);
        TenantCountry::create(['tenant_id' => $this->tenant->id, 'country_id' => $country->id, 'is_active' => true]);
        Model::withoutEvents(function () use ($p, $country) {
            $badge = CentralBadge::query()->create(['text' => 'tenant-p2', 'active' => true]);
            DB::table('product_badge_product')->insert(['product_badge_id' => $badge->id, 'product_id' => $p['central_product']->id, 'country_id' => $country->id, 'sort_order' => 0]);
        });
        app(TenantCatalogSyncService::class)->syncSection($this->tenant, 'badges');
        tenancy()->end();
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());
        tenancy()->end();
        $this->actingAsTenantAdmin();

        $sortLink = 'badges/tenant-p2/sort?country_id=' . $country->id;

        // Sync mode: country-aware links, no platform notice.
        $this->get($this->tenantUrl("/admin/badges/tenant-p2?country_id={$country->id}"))->assertOk()
            ->assertSee($sortLink, false)->assertDontSee('data-badge-platform-notice', false);
        tenancy()->end();
        $this->get($this->tenantUrl("/admin/badges/tenant-p2/sort?country_id={$country->id}"))->assertOk()
            ->assertSee('Morocco')->assertSee('t-badge-country-tab is-active', false)->assertSee($p['product']->slug);
        tenancy()->end();

        // Central mode: same links, platform notice, same product in the per-country sort list.
        CatalogSource::set($this->tenant, 'badges', CatalogSource::CENTRAL);
        $this->get($this->tenantUrl("/admin/badges/tenant-p2?country_id={$country->id}"))->assertOk()
            ->assertSee($sortLink, false)->assertSee('data-badge-platform-notice', false);
        tenancy()->end();
        $this->get($this->tenantUrl("/admin/badges/tenant-p2/sort?country_id={$country->id}"))->assertOk()
            ->assertSee('Morocco')->assertSee($p['product']->slug);
    }
}
