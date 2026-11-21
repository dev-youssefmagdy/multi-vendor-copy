<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Http\Controllers\Tenant\Panel\Catalog\ProductsListController;
use App\Models\Tenant\Category;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\Page;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan Phase -1 (frontend review): panel screens after the sync hotfixes. R1: a category central hides is out of
 * the panel like a hidden product, but its visible children stay pickable and its parent link survives the category
 * edit form. R3: the email template reset copy names this template only. B3: central pages carry a "Platform" badge.
 */
class CatalogSyncPanelUiTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
        // HTML pages sit behind the onboarding-tour middleware.
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    /** @return array{0:int,1:int} [hidden parent id, visible child id] */
    private function hiddenParentWithVisibleChild(): array
    {
        return $this->tenant->run(function () {
            $parent = Category::query()->create(['active' => true, 'featured' => false, 'order_number' => 1, 'central_visible' => false]);
            $parent->syncTranslations(['en' => ['name' => 'Hidden Parent', 'slug' => 'hidden-parent']]);
            $child = Category::query()->create(['parent_id' => $parent->id, 'active' => true, 'featured' => false, 'order_number' => 2]);
            $child->syncTranslations(['en' => ['name' => 'Visible Child', 'slug' => 'visible-child']]);

            return [$parent->id, $child->id];
        });
    }

    public function test_pickers_keep_children_of_a_hidden_parent_and_the_edit_form_keeps_the_parent(): void
    {
        [$parentId, $childId] = $this->hiddenParentWithVisibleChild();

        $this->tenant->run(function () use ($parentId, $childId) {
            $repo = app(TenantPanelRepository::class);

            $tree = collect($repo->categoryTreeOptions())->keyBy('id');
            $this->assertFalse($tree->has($parentId));
            $this->assertSame(0, $tree[$childId]['depth'], 'orphaned child is listed as a root');

            $this->assertArrayNotHasKey($parentId, $repo->categoryOptions());
            $this->assertStringContainsString('Hidden Parent', $repo->categoryOptions($parentId)[$parentId]);

            $filter = (new \ReflectionMethod(ProductsListController::class, 'categoryOptions'))
                ->invoke(app(ProductsListController::class));
            $this->assertArrayHasKey((string) $childId, $filter);
            $this->assertArrayNotHasKey((string) $parentId, $filter);
        });
        tenancy()->end();

        $this->actingAsTenantAdmin()
            ->get($this->tenantUrl("/admin/categories/{$childId}/edit"))
            ->assertOk()
            ->assertSee('Hidden Parent (Hidden by platform)');

        $this->actingAsTenantAdmin()
            ->getJson($this->tenantUrl('/admin/categories/data'))
            ->assertOk()
            ->assertSee('Hidden by platform');

        $this->actingAsTenantAdmin()
            ->get($this->tenantUrl("/admin/categories/{$parentId}/edit"))
            ->assertNotFound();
    }

    public function test_email_template_reset_button_only_for_central_templates_with_per_template_copy(): void
    {
        [$linked, $own] = $this->tenant->run(fn () => [
            EmailTemplate::query()->create(['central_email_template_id' => 999, 'name' => 'Linked', 'action' => 'linked_action', 'subject' => 'S', 'body' => 'B', 'is_active' => true]),
            EmailTemplate::query()->create(['name' => 'Own', 'action' => 'own_action', 'subject' => 'S', 'body' => 'B', 'is_active' => true]),
        ]);
        tenancy()->end();

        $this->actingAsTenantAdmin()
            ->get($this->tenantUrl("/admin/settings/email-templates/{$linked->id}/edit"))
            ->assertOk()
            ->assertSee('Reset only this template', false)
            ->assertSee('other templates are not changed', false);

        $this->actingAsTenantAdmin()
            ->get($this->tenantUrl("/admin/settings/email-templates/{$own->id}/edit"))
            ->assertOk()
            ->assertDontSee('Re-sync from Central');
    }

    public function test_pages_list_marks_central_pages_as_platform(): void
    {
        $this->tenant->run(function () {
            Page::query()->create(['slug' => 'vendor-own', 'active' => true]);
            Page::query()->create(['slug' => 'central-terms', 'active' => true])->forceFill(['source' => 'central'])->save();
        });
        tenancy()->end();

        $rows = collect($this->actingAsTenantAdmin()->getJson($this->tenantUrl('/admin/store/pages/data'))->assertOk()->json('data'))->keyBy('slug');

        $this->assertStringContainsString('Platform', $rows['central-terms']['status']);
        $this->assertStringNotContainsString('Platform', $rows['vendor-own']['status']);
    }
}
