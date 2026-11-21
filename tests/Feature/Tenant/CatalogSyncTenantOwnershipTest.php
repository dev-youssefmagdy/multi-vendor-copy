<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\OrderStatus;
use App\Models\Category as CentralCategory;
use App\Models\Currency as CentralCurrency;
use App\Models\EmailTemplate as CentralEmailTemplate;
use App\Models\Language as CentralLanguage;
use App\Models\ProductBadge as CentralProductBadge;
use App\Models\StaticPage as CentralStaticPage;
use App\Models\Tenant\Category;
use App\Models\Tenant\Currency;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\Language;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Page;
use App\Models\Tenant\ProductBadge;
use App\Services\Tenant\TenantCatalogSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan Phase -1 (docs/TENANT_CENTRAL_DEDUP_PLAN.md §2a + PM review R1-R3): the central -> tenant catalog
 * sync must not destroy tenant-owned data. Central fixtures are written without model events so the
 * central -> tenant observers don't fan out.
 */
class CatalogSyncTenantOwnershipTest extends TestCase
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

    private function sync(string ...$sections): void
    {
        foreach ($sections as $section) {
            app(TenantCatalogSyncService::class)->syncSection($this->tenant, $section);
        }
        tenancy()->end();
    }

    private function inTenant(callable $callback): mixed
    {
        return $this->tenant->run($callback);
    }

    /** @param callable(): mixed $callback */
    private function central(callable $callback): mixed
    {
        tenancy()->end();

        return \Illuminate\Database\Eloquent\Model::withoutEvents($callback);
    }

    // ---- B1 -------------------------------------------------------------------------------------------------

    public function test_b1_deleting_a_variant_keeps_the_order_line_with_a_null_variant(): void
    {
        $this->inTenant(function () {
            $variant = $this->createOwnProductVariant(stock: 5);
            $order = $this->createOrder($this->createCustomer(), [[$variant, 1]], OrderStatus::Processing);
            $itemId = $order->items()->value('id');

            $variant->delete();

            $item = OrderItem::query()->find($itemId);
            $this->assertNotNull($item, 'order line must survive the variant delete');
            $this->assertNull($item->product_variant_id);
            $this->assertSame((int) $variant->product_id, (int) $item->product_id);
        });
    }

    public function test_b1_foreign_key_is_set_null(): void
    {
        $rule = $this->inTenant(fn () => collect(Schema::getForeignKeys('order_items'))
            ->firstWhere('columns', ['product_variant_id'])['on_delete'] ?? null);

        $this->assertSame('set null', strtolower((string) $rule));
    }

    // ---- B2 -------------------------------------------------------------------------------------------------

    public function test_b2_language_and_currency_toggles_survive_a_sync(): void
    {
        $this->central(function () {
            CentralLanguage::query()->create(['name' => 'French', 'code' => 'fr', 'native_name' => 'Français', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'is_free' => true, 'sort_order' => 2]);
            CentralCurrency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'sign' => '$', 'conversion_rate' => 1]);
            CentralCurrency::query()->create(['code' => 'EUR', 'name' => 'Euro', 'sign' => '€', 'conversion_rate' => 0.9]);
        });
        $this->sync('languages', 'currencies');

        $this->inTenant(function () {
            $this->assertTrue((bool) Language::query()->where('code', 'fr')->value('is_active'));
            Language::query()->where('code', 'fr')->update(['is_active' => false]);
            Currency::query()->where('code', 'EUR')->update(['is_active' => false]);
        });

        $this->sync('languages', 'currencies');

        $this->inTenant(function () {
            $this->assertFalse((bool) Language::query()->where('code', 'fr')->value('is_active'));
            $this->assertFalse((bool) Currency::query()->where('code', 'EUR')->value('is_active'));
            $this->assertSame(1, Currency::query()->where('is_default', true)->count(), 'exactly one default currency');
        });
    }

    public function test_b2_email_template_edits_survive_a_sync_and_r3_reset_only_touches_the_clicked_template(): void
    {
        [$a, $b] = $this->central(fn () => [
            CentralEmailTemplate::query()->create(['name' => 'Order placed', 'action' => 'order_placed', 'subject' => 'Central A', 'body' => 'Body A', 'type' => 'tenant', 'status' => 'active']),
            CentralEmailTemplate::query()->create(['name' => 'Order shipped', 'action' => 'order_shipped', 'subject' => 'Central B', 'body' => 'Body B', 'type' => 'tenant', 'status' => 'active']),
        ]);
        $this->sync('email-templates');

        $this->inTenant(function () use ($a, $b) {
            EmailTemplate::query()->where('central_email_template_id', $a->id)->update(['subject' => 'Vendor A']);
            EmailTemplate::query()->where('central_email_template_id', $b->id)->update(['subject' => 'Vendor B']);
        });
        $this->sync('email-templates');

        $this->inTenant(function () use ($a, $b) {
            $this->assertSame('Vendor A', EmailTemplate::query()->where('central_email_template_id', $a->id)->value('subject'));
            $this->assertSame('Vendor B', EmailTemplate::query()->where('central_email_template_id', $b->id)->value('subject'));
        });

        $templateA = $this->inTenant(fn () => EmailTemplate::query()->where('central_email_template_id', $a->id)->firstOrFail());
        app(TenantCatalogSyncService::class)->resetEmailTemplateFromCentral($this->tenant, $templateA);
        tenancy()->end();

        $this->inTenant(function () use ($a, $b) {
            $this->assertSame('Central A', EmailTemplate::query()->where('central_email_template_id', $a->id)->value('subject'));
            $this->assertSame('Vendor B', EmailTemplate::query()->where('central_email_template_id', $b->id)->value('subject'));
        });
    }

    public function test_b2_r1_category_toggles_survive_and_central_unpublish_republish_restores_visibility(): void
    {
        $central = $this->central(fn () => CentralCategory::query()->create(['status' => 'published', 'is_featured' => false, 'order_number' => 1]));
        $this->sync('categories');

        $this->inTenant(function () use ($central) {
            $row = Category::query()->where('central_category_id', $central->id)->firstOrFail();
            $this->assertTrue($row->active);
            $row->update(['featured' => true]);
        });

        // Central unpublishes: hidden for the tenant, the vendor's toggles untouched.
        $this->central(fn () => $central->forceFill(['status' => 'draft'])->save());
        $this->sync('categories');

        $this->inTenant(function () use ($central) {
            $this->assertNull(Category::query()->where('central_category_id', $central->id)->first(), 'hidden by the centralVisible scope');
            $row = Category::withoutGlobalScope(Category::CENTRAL_VISIBLE_SCOPE)->where('central_category_id', $central->id)->firstOrFail();
            $this->assertFalse($row->central_visible);
            $this->assertTrue($row->active);
            $this->assertTrue($row->featured);
        });

        // Central republishes: visible again (the old code left it disabled forever).
        $this->central(fn () => $central->forceFill(['status' => 'published'])->save());
        $this->sync('categories');

        $this->inTenant(function () use ($central) {
            $row = Category::query()->where('central_category_id', $central->id)->firstOrFail();
            $this->assertTrue($row->active);
            $this->assertTrue($row->featured);

            // A vendor "off" survives the next sync too.
            $row->update(['active' => false]);
        });
        $this->sync('categories');
        $this->assertFalse($this->inTenant(fn () => (bool) Category::query()->where('central_category_id', $central->id)->value('active')));
    }

    // ---- R2 -------------------------------------------------------------------------------------------------

    public function test_r2_only_central_category_links_are_detached(): void
    {
        $this->inTenant(function () {
            $product = $this->createOwnProductVariant()->product;
            $catCentralA = Category::query()->create(['central_category_id' => 9001, 'active' => true]);
            $catCentralB = Category::query()->create(['central_category_id' => 9002, 'active' => true]);
            $catVendor = Category::query()->create(['active' => true]);

            // Vendor form pick: default source = tenant.
            $product->categories()->attach($catVendor->id);
            $this->assertSame('tenant', DB::table('category_product')->where('category_id', $catVendor->id)->value('source'));

            $service = app(TenantCatalogSyncService::class);
            $link = fn (array $wanted) => (fn () => $this->syncCentralCategoryLinks($wanted))->call($service);

            // Central links the product to A and B.
            $link([$product->id => [$catCentralA->id, $catCentralB->id]]);
            $this->assertSame(['central', 'central'], DB::table('category_product')->where('product_id', $product->id)->whereIn('category_id', [$catCentralA->id, $catCentralB->id])->pluck('source')->all());

            // Central drops B: B detached, A and the vendor's link kept.
            $link([$product->id => [$catCentralA->id]]);
            $ids = DB::table('category_product')->where('product_id', $product->id)->pluck('category_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $this->assertSame(collect([$catCentralA->id, $catVendor->id])->sort()->values()->all(), $ids);

            // Central drops everything: the vendor's link still stays.
            $link([$product->id => []]);
            $this->assertSame([$catVendor->id], DB::table('category_product')->where('product_id', $product->id)->pluck('category_id')->map(fn ($id) => (int) $id)->all());
        });
    }

    // ---- B3 -------------------------------------------------------------------------------------------------

    public function test_b3_tenant_pages_survive_and_central_pages_central_dropped_are_removed(): void
    {
        $about = $this->central(fn () => CentralStaticPage::query()->create(['title' => 'About', 'slug' => 'about-us', 'content' => 'x', 'status' => 'published']));
        $this->central(fn () => CentralStaticPage::query()->create(['title' => 'Terms', 'slug' => 'terms', 'content' => 'x', 'status' => 'published']));
        $this->sync('pages');

        $this->inTenant(function () {
            Page::query()->create(['slug' => 'my-own-page', 'active' => true]);
            $this->assertSame('central', Page::query()->where('slug', 'about-us')->value('source'));
        });

        $this->central(fn () => $about->delete());
        $this->sync('pages');

        $this->inTenant(function () {
            $this->assertTrue(Page::query()->where('slug', 'my-own-page')->exists(), 'tenant page kept');
            $this->assertTrue(Page::query()->where('slug', 'terms')->exists());
            $this->assertFalse(Page::query()->where('slug', 'about-us')->exists(), 'central page central dropped is removed');
        });
    }

    // ---- B3 + B4 --------------------------------------------------------------------------------------------

    public function test_b3_b4_badges_seeded_and_tenant_badges_survive_rename_does_not_duplicate(): void
    {
        // Seeded by tenant migrations (featured / recommended / new-in / best-selling / trending-now); some may
        // already be linked to a same-text central badge by the B4 backfill.
        $seeded = $this->inTenant(fn () => ProductBadge::query()->pluck('text')->all());
        $this->assertContains('featured', $seeded);
        $this->assertContains('trending-now', $seeded);

        $sale = $this->central(fn () => CentralProductBadge::query()->create(['text' => 'hot-sale', 'active' => true]));
        $gone = $this->central(fn () => CentralProductBadge::query()->create(['text' => 'old-badge', 'active' => true]));
        $this->inTenant(fn () => ProductBadge::query()->create(['text' => 'vendor-badge', 'active' => true]));
        $this->sync('badges');

        $this->assertSame($sale->id, $this->inTenant(fn () => ProductBadge::query()->where('text', 'hot-sale')->value('central_product_badge_id')));

        // Rename + delete in central.
        $this->central(function () use ($sale, $gone) {
            $sale->forceFill(['text' => 'mega-sale'])->save();
            DB::table('product_badge_product')->where('product_badge_id', $gone->id)->delete();
            $gone->delete();
        });
        $this->sync('badges');

        $this->inTenant(function () use ($sale, $seeded) {
            $this->assertSame(0, ProductBadge::query()->where('text', 'hot-sale')->count());
            $this->assertSame(1, ProductBadge::query()->where('central_product_badge_id', $sale->id)->count());
            $this->assertSame('mega-sale', ProductBadge::query()->where('central_product_badge_id', $sale->id)->value('text'));
            $this->assertFalse(ProductBadge::query()->where('text', 'old-badge')->exists(), 'central-linked badge central dropped is removed');
            $this->assertTrue(ProductBadge::query()->where('text', 'vendor-badge')->exists(), 'tenant badge kept');
            foreach ($seeded as $text) {
                $this->assertTrue(ProductBadge::query()->where('text', $text)->exists(), "seeded badge {$text} kept");
            }
        });
    }
}
