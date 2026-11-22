<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\ActivationStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Language as CentralLanguage;
use App\Models\Product as CentralProduct;
use App\Models\ProductEditRequest;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\Catalog\FieldOverrideStore;
use App\Services\Catalog\CatalogSyncCheck;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Catalog\ProductEditRequestReview;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\Catalog\CatalogRefColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan §4.2a (ships with phase 2): an approved edit request stores ONLY its changed (field, locale) pairs in
 * `tenant_field_overrides`; every other field/locale keeps following central through the products sync; reset puts
 * the LIVE central value back; legacy `has_custom_translations` products keep their freeze until
 * `catalog:dedup-migrate field_overrides` (dry-run / idempotent / verify) converted them; the rebuilt admin screen.
 */
class CatalogPhase2FieldOverridesTest extends TestCase
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
            if (Schema::hasTable('catalog_dedup_runs')) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->delete();
            }
            foreach (['migrate', 'dry-run', 'verify'] as $step) {
                File::delete(DedupRunner::reportPath('field_overrides', (string) $this->tenant->id, $step));
            }
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

    private function centralTranslations(CentralProduct $product, array $values): void
    {
        tenancy()->end();
        Model::withoutEvents(fn () => $product->syncTranslations($values));
    }

    /** Central product (en/ar name+description) and its tenant copy, synced the normal way. */
    private function fixture(): array
    {
        $fixture = $this->inTenant(fn () => $this->createCentralCatalogVariant(price: 30.0));
        $central = $fixture['central_product'];
        $this->centralTranslations($central, [
            'en' => ['name' => 'Central Name', 'description' => 'Central Desc', 'meta_description' => 'Meta 1'],
            'ar' => ['name' => 'اسم مركزي', 'description' => 'وصف'],
        ]);
        $this->sync()->syncProductToTenant(CentralProduct::query()->findOrFail($central->id), $this->tenant);
        tenancy()->end();

        return [$central, $fixture['product']];
    }

    private function sync(): TenantCatalogSyncService
    {
        return app(TenantCatalogSyncService::class);
    }

    /** @return array<string, array<string, string>> locale => field => value of the tenant product */
    private function tenantValues(int $productId): array
    {
        return $this->inTenant(fn () => Product::withoutGlobalScope('centralVisible')->findOrFail($productId)
            ->translationsByLocale(['name', 'description', 'meta_description']));
    }

    private function request(Product $product, array $requested, array $current, string $status = 'pending'): ProductEditRequest
    {
        tenancy()->end();

        return ProductEditRequest::query()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'requested_translations' => $requested,
            'current_translations' => $current,
            'status' => $status,
        ]);
    }

    public function test_approve_stores_only_changed_pairs_central_edits_show_through_and_reset_restores_live_value(): void
    {
        [$central, $product] = $this->fixture();
        $this->assertSame('Central Name', $this->tenantValues($product->id)['en']['name']);

        $request = $this->request($product, ['en' => ['name' => 'Vendor Name', 'description' => 'Central Desc']], ['en' => ['name' => 'Central Name', 'description' => 'Central Desc']]);
        app(ProductEditRequestReview::class)->approve($request, null);

        $rows = $this->inTenant(fn () => DB::table(FieldOverrideStore::TABLE)->get());
        $this->assertCount(1, $rows, 'only the changed pair');
        $this->assertSame(['central_product', $central->id, 'name', 'en', 'Vendor Name', 'Central Name', $request->id],
            [$rows[0]->entity_type, (int) $rows[0]->entity_id, $rows[0]->field, $rows[0]->locale, $rows[0]->value, $rows[0]->central_value_at_approval, (int) $rows[0]->edit_request_id]);
        $values = $this->tenantValues($product->id);
        $this->assertSame(['Vendor Name', 'Central Desc', 'اسم مركزي'], [$values['en']['name'], $values['en']['description'], $values['ar']['name']]);
        $this->assertSame('approved', $request->fresh()->status->value);

        // Central edits other fields/locales AND the overridden one: only the override is kept.
        $this->centralTranslations(CentralProduct::query()->findOrFail($central->id), [
            'en' => ['name' => 'Central Name v2', 'description' => 'Central Desc v2', 'meta_description' => 'Meta 2'],
            'ar' => ['name' => 'اسم ٢', 'description' => 'وصف ٢'],
        ]);
        $this->sync()->syncProductToTenant(CentralProduct::query()->findOrFail($central->id), $this->tenant);
        tenancy()->end();
        $values = $this->tenantValues($product->id);
        $this->assertSame(['Vendor Name', 'Central Desc v2', 'Meta 2', 'اسم ٢'], [$values['en']['name'], $values['en']['description'], $values['en']['meta_description'], $values['ar']['name']]);

        // The batch products sync keeps the same rule.
        $this->sync()->syncSection($this->tenant, 'products');
        tenancy()->end();
        $values = $this->tenantValues($product->id);
        $this->assertSame(['Vendor Name', 'Central Desc v2', 'اسم ٢'], [$values['en']['name'], $values['en']['description'], $values['ar']['name']]);

        $metrics = app(CatalogSyncCheck::class)->run([(string) $this->tenant->id], ['products'])[0]['sections']['products'];
        $this->assertSame([1, 0, 1], [$metrics['field_override_rows'], $metrics['custom_without_override'], $metrics['field_overrides_central_changed']]);

        // Reset (this request) → the LIVE central value, incl. the edit made after the request.
        app(ProductEditRequestReview::class)->reset($request->fresh());
        $this->assertSame('Central Name v2', $this->tenantValues($product->id)['en']['name']);
        $this->assertSame(0, $this->inTenant(fn () => DB::table(FieldOverrideStore::TABLE)->count()));
        $this->assertFalse($this->inTenant(fn () => (bool) Product::withoutGlobalScope('centralVisible')->findOrFail($product->id)->has_custom_translations));
    }

    public function test_reset_per_field_and_all(): void
    {
        [$central, $product] = $this->fixture();
        $request = $this->request($product, ['en' => ['name' => 'V-en', 'description' => 'D-en'], 'ar' => ['name' => 'V-ar']],
            ['en' => ['name' => 'Central Name', 'description' => 'Central Desc'], 'ar' => ['name' => 'اسم مركزي']]);
        $review = app(ProductEditRequestReview::class);
        $review->approve($request, null);
        $this->assertSame(3, $this->inTenant(fn () => DB::table(FieldOverrideStore::TABLE)->count()));

        $review->reset($request->fresh(), ProductEditRequestReview::SCOPE_FIELD, 'name', 'ar');
        $values = $this->tenantValues($product->id);
        $this->assertSame(['V-en', 'D-en', 'اسم مركزي'], [$values['en']['name'], $values['en']['description'], $values['ar']['name']]);

        $review->reset($request->fresh(), ProductEditRequestReview::SCOPE_ALL);
        $values = $this->tenantValues($product->id);
        $this->assertSame(['Central Name', 'Central Desc'], [$values['en']['name'], $values['en']['description']]);
        $this->assertSame(0, $this->inTenant(fn () => DB::table(FieldOverrideStore::TABLE)->count()));
    }

    public function test_legacy_custom_product_keeps_freeze_until_dedup_migrate_then_only_differing_pairs_survive(): void
    {
        [$central, $product] = $this->fixture();

        // Legacy state: an approved request applied the old way (full freeze), tenant name differs from central.
        $request = $this->request($product, ['en' => ['name' => 'Legacy Vendor']], ['en' => ['name' => 'Central Name']], 'approved');
        $this->inTenant(function () use ($product) {
            $p = Product::withoutGlobalScope('centralVisible')->findOrFail($product->id);
            app(FieldOverrideStore::class)->putTenantTranslation($p->getMorphClass(), $p->id, 'en', 'name', 'Legacy Vendor');
            $p->forceFill(['has_custom_translations' => true])->saveQuietly();
        });

        $this->centralTranslations(CentralProduct::query()->findOrFail($central->id), [
            'en' => ['name' => 'Central Name', 'description' => 'Desc v2', 'meta_description' => 'Meta 2'],
            'ar' => ['name' => 'اسم مركزي', 'description' => 'وصف'],
        ]);
        $this->sync()->syncProductToTenant(CentralProduct::query()->findOrFail($central->id), $this->tenant);
        tenancy()->end();
        $this->assertSame('Central Desc', $this->tenantValues($product->id)['en']['description'], 'still frozen before the migration');

        $this->artisan('catalog:dedup-migrate', ['section' => 'field_overrides', '--tenant' => [$this->tenant->id], '--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, $this->inTenant(fn () => DB::table(FieldOverrideStore::TABLE)->count()), 'dry run writes nothing');
        $dry = json_decode(File::get(DedupRunner::reportPath('field_overrides', (string) $this->tenant->id, 'dry-run')), true);
        // QA D-P2-2: the request row covers only `name`; the differing description may be an older approval whose
        // request row was overwritten, so it is kept as a `tenant_diff` override (never silently reverted).
        $this->assertSame(2, $dry['counts']['override_rows'] ?? null, json_encode($dry));
        $this->assertSame([1, 1, 0], [$dry['counts']['from_approved_request'], $dry['counts']['from_tenant_diff'], $dry['counts']['stale_pairs']]);

        $this->artisan('catalog:dedup-migrate', ['section' => 'field_overrides', '--tenant' => [$this->tenant->id]])->assertSuccessful();
        $this->artisan('catalog:dedup-migrate', ['section' => 'field_overrides', '--tenant' => [$this->tenant->id]])->assertSuccessful();
        $rows = $this->inTenant(fn () => DB::table(FieldOverrideStore::TABLE)->orderBy('field')->get());
        $this->assertCount(2, $rows, 'idempotent, only the differing pairs');
        $this->assertSame(['description', 'en', 'Central Desc', 0], [$rows[0]->field, $rows[0]->locale, $rows[0]->value, (int) $rows[0]->edit_request_id]);
        $this->assertSame(['name', 'en', 'Legacy Vendor', $request->id], [$rows[1]->field, $rows[1]->locale, $rows[1]->value, (int) $rows[1]->edit_request_id]);

        $this->artisan('catalog:dedup-migrate', ['section' => 'field_overrides', '--tenant' => [$this->tenant->id], '--verify' => true])->assertSuccessful();
        $this->assertSame(0, json_decode(File::get(DedupRunner::reportPath('field_overrides', (string) $this->tenant->id, 'verify')), true)['diffs']);

        // Freeze lifted: the non-overridden fields follow central now, both kept pairs stay.
        $this->sync()->syncProductToTenant(CentralProduct::query()->findOrFail($central->id), $this->tenant);
        tenancy()->end();
        $values = $this->tenantValues($product->id);
        $this->assertSame(['Legacy Vendor', 'Central Desc', 'Meta 2'], [$values['en']['name'], $values['en']['description'], $values['en']['meta_description']]);
    }

    /**
     * QA D-P2-2: a pair the approved request covers whose tenant value is back to the PRE-approval value is "stale".
     * It is never reverted silently: without --accept-stale the run fails, writes nothing for the product (legacy
     * freeze kept) and verify flags it; with --accept-stale it follows central and verify counts it as accepted.
     */
    public function test_stale_pairs_block_the_migration_until_accepted(): void
    {
        [$central, $product] = $this->fixture();
        $this->request($product, ['en' => ['name' => 'Vendor X']], ['en' => ['name' => 'Old Name']], 'approved');
        $this->inTenant(function () use ($product) {
            $p = Product::withoutGlobalScope('centralVisible')->findOrFail($product->id);
            $store = app(FieldOverrideStore::class);
            $store->putTenantTranslation($p->getMorphClass(), $p->id, 'en', 'name', 'Old Name');          // stale
            $store->putTenantTranslation($p->getMorphClass(), $p->id, 'en', 'description', 'Vendor Desc'); // tenant_diff
            $p->forceFill(['has_custom_translations' => true])->saveQuietly();
        });
        $tenantId = (string) $this->tenant->id;
        $sync = function () use ($central, $product) {
            $this->sync()->syncProductToTenant(CentralProduct::query()->findOrFail($central->id), $this->tenant);
            tenancy()->end();

            return $this->tenantValues($product->id)['en'];
        };

        $this->artisan('catalog:dedup-migrate', ['section' => 'field_overrides', '--tenant' => [$tenantId]])->assertFailed();
        $report = json_decode(File::get(DedupRunner::reportPath('field_overrides', $tenantId, 'migrate')), true);
        $this->assertSame('failed', $report['status']);
        $this->assertSame([1, 1], [$report['counts']['stale_pairs'], $report['counts']['stale_blocked_products']]);
        $this->assertSame([['product_id' => $product->id, 'field' => 'name', 'locale' => 'en']], array_map(fn ($p) => ['product_id' => $p['product_id'], 'field' => $p['field'], 'locale' => $p['locale']], $report['stale_pairs']));
        $this->assertSame(0, $this->inTenant(fn () => DB::table(FieldOverrideStore::TABLE)->count()), 'blocked product: nothing written');
        $this->assertSame(['Old Name', 'Vendor Desc'], array_values(array_intersect_key($sync(), ['name' => 1, 'description' => 1])), 'freeze kept');

        $this->artisan('catalog:dedup-migrate', ['section' => 'field_overrides', '--tenant' => [$tenantId], '--verify' => true])->assertFailed();
        $verify = json_decode(File::get(DedupRunner::reportPath('field_overrides', $tenantId, 'verify')), true);
        $this->assertSame(1, $verify['counts']['stale_pairs']);
        $this->assertSame(2, $verify['diffs'], 'stale name + uncaptured description');

        $this->artisan('catalog:dedup-migrate', ['section' => 'badges', '--accept-stale' => true])->assertExitCode(2);
        $this->artisan('catalog:dedup-migrate', ['section' => 'field_overrides', '--tenant' => [$tenantId], '--accept-stale' => true])->assertSuccessful();
        $rows = $this->inTenant(fn () => DB::table(FieldOverrideStore::TABLE)->get());
        $this->assertSame([['description', 'Vendor Desc']], $rows->map(fn ($r) => [$r->field, $r->value])->all());
        $this->artisan('catalog:dedup-migrate', ['section' => 'field_overrides', '--tenant' => [$tenantId], '--verify' => true])->assertSuccessful();
        $verify = json_decode(File::get(DedupRunner::reportPath('field_overrides', $tenantId, 'verify')), true);
        $this->assertSame([0, 1], [$verify['diffs'], $verify['counts']['stale_accepted']]);

        $values = $sync();
        $this->assertSame(['Central Name', 'Vendor Desc'], [$values['name'], $values['description']]);
    }

    /** QA D-P2-1: vendor text in the review screen is parsed with DOMParser (inert), never assigned via innerHTML. */
    public function test_admin_review_screen_never_injects_vendor_html(): void
    {
        [, $product] = $this->fixture();
        $this->request($product, ['en' => ['description' => '<img src=x onerror=alert(1)>']], ['en' => ['description' => 'Central Desc']]);

        $role = AdminRole::query()->create(['name' => 'Role ' . uniqid(), 'permissions' => ['catalog.product-edit-requests.manage'], 'permissions_count' => 1]);
        $admin = AdminUser::query()->create(['role_id' => $role->id, 'name' => 'Admin', 'email' => uniqid('admin') . '@example.com', 'password' => Hash::make('password12345'), 'status' => ActivationStatus::Active]);
        $this->actingAs($admin, 'admin');

        $html = $this->get('http://localhost/admin/products/edit-requests')->assertOk()->getContent();
        $this->assertStringContainsString('new DOMParser().parseFromString(', $html);
        $this->assertDoesNotMatchRegularExpression('/\.innerHTML\s*=|insertAdjacentHTML|outerHTML\s*=|document\.write/', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html, 'vendor HTML never printed raw');
        $source = File::get(resource_path('views/admin/product/edit-requests/index.blade.php'));
        $this->assertStringNotContainsString('innerHTML', $source);
    }

    public function test_admin_review_screen_is_blade_and_json(): void
    {
        [, $product] = $this->fixture();
        $pending = $this->request($product, ['en' => ['name' => 'Screen Name']], ['en' => ['name' => 'Central Name']]);

        $role = AdminRole::query()->create(['name' => 'Role ' . uniqid(), 'permissions' => ['catalog.product-edit-requests.manage'], 'permissions_count' => 1]);
        $admin = AdminUser::query()->create(['role_id' => $role->id, 'name' => 'Admin', 'email' => uniqid('admin') . '@example.com', 'password' => Hash::make('password12345'), 'status' => ActivationStatus::Active]);
        $this->actingAs($admin, 'admin');

        $this->get('http://localhost/admin/products/edit-requests')->assertOk()->assertSee($product->slug)->assertDontSee('wire:click');
        $this->getJson("http://localhost/admin/products/edit-requests/{$pending->id}")->assertOk()
            ->assertJsonPath('changes.en.name.requested', 'Screen Name');
        $this->postJson("http://localhost/admin/products/edit-requests/{$pending->id}/approve")->assertOk()->assertJson(['success' => true]);
        $this->postJson("http://localhost/admin/products/edit-requests/{$pending->id}/approve")->assertStatus(422);
        tenancy()->end();
        $this->getJson("http://localhost/admin/products/edit-requests/{$pending->id}")->assertOk()->assertJsonPath('overrides.0.field', 'name');
        tenancy()->end();
        $this->assertSame('Screen Name', $this->tenantValues($product->id)['en']['name']);
        $this->postJson("http://localhost/admin/products/edit-requests/{$pending->id}/reset", ['scope' => 'request'])->assertOk()->assertJson(['success' => true]);
        tenancy()->end();
        $this->assertSame('Central Name', $this->tenantValues($product->id)['en']['name']);
    }
}
