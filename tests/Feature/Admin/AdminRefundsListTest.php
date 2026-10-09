<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\ActivationStatus;
use App\Enums\CancellationActor;
use App\Enums\RefundMethod;
use App\Enums\RefundSource;
use App\Enums\RefundStatus;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Helpers\AdminNavigation;
use App\Livewire\Admin\Order\RefundsList;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Refund;
use App\Models\ReturnRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Phase 7: Orders → Refunds (central refunds list: route, permissions, filters, stats, CSV export). */
class AdminRefundsListTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions): AdminUser
    {
        $role = AdminRole::query()->create([
            'name' => 'Role '.uniqid(),
            'permissions' => $permissions,
            'permissions_count' => count($permissions),
        ]);

        return AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => 'Admin '.uniqid(),
            'email' => uniqid('admin').'@example.com',
            'password' => Hash::make('password12345'),
            'status' => ActivationStatus::Active,
        ]);
    }

    private function refund(string $tenantId, string $order, RefundStatus $status, RefundSource $source, float $amount, array $extra = []): Refund
    {
        return Refund::query()->create(array_merge([
            'tenant_id' => $tenantId,
            'order_number' => $order,
            'source' => $source,
            'reason' => 'Test reason',
            'currency' => 'USD',
            'items_amount' => $amount,
            'amount' => $amount,
            'payment_method' => 'stripe',
            'gateway' => 'stripe',
            'refund_method' => RefundMethod::OriginalPayment,
            'status' => $status,
            'requested_by_type' => CancellationActor::Admin,
            'requested_at' => now(),
        ], $extra));
    }

    #[Test]
    public function refunds_list_renders_filters_stats_and_exports(): void
    {
        $manager = $this->admin(['sales.orders.manage', 'sales.orders.view']);
        $viewer = $this->admin(['sales.orders.view']);
        $outsider = $this->admin(['content.faqs.manage']);

        $return = ReturnRequest::query()->create([
            'tenant_id' => 'tenant-a',
            'order_number' => 'order-bbb',
            'customer_id' => 1,
            'reason' => ReturnReason::ChangedMind,
            'status' => ReturnStatus::Inspected,
        ]);

        $a = $this->refund('tenant-a', 'order-aaa', RefundStatus::Completed, RefundSource::Cancellation, 100.0, ['requested_at' => now()->subDays(10), 'processed_at' => now()->subDays(10)]);
        $b = $this->refund('tenant-a', 'order-bbb', RefundStatus::Failed, RefundSource::Return, 40.0, ['return_request_id' => $return->id, 'failure_reason' => 'Card declined']);
        $c = $this->refund('tenant-b', 'order-ccc', RefundStatus::Pending, RefundSource::Manual, 25.5, ['refund_method' => RefundMethod::Manual]);
        $d = $this->refund('tenant-b', 'order-ddd', RefundStatus::Completed, RefundSource::Manual, 10.0);

        // ── Route + permissions (registered before the /{tenantId}/{orderNumber} catch-all) ──
        $this->assertSame('admin/orders/refunds', ltrim(route('admin.orders.refunds.index', [], false), '/'));
        $this->actingAs($manager, 'admin')->get('http://localhost/admin/orders/refunds')->assertOk()->assertSee($a->reference)->assertSee('Card declined');
        $this->actingAs($viewer, 'admin')->get('http://localhost/admin/orders/refunds')->assertOk();
        $this->actingAs($outsider, 'admin')->get('http://localhost/admin/orders/refunds')->assertRedirect(); // the permission middleware sends it away

        // ── Table: links to the order and the return, tenant names via the join ──
        $this->actingAs($manager, 'admin');
        $page = Livewire::test(RefundsList::class)
            ->assertSee($a->reference)->assertSee($b->reference)->assertSee($c->reference)->assertSee($d->reference)
            ->assertSeeHtml(route('admin.orders.show', ['tenant-a', 'order-aaa']))
            ->assertSeeHtml(route('admin.orders.returns.show', $return->id))
            ->assertSee('Total Refunded')
            ->assertSee('$110.00');   // completed: 100 + 10

        $stats = (fn () => $this->stats())->call($page->instance());
        $this->assertSame(['refunded_amount' => 110.0, 'pending' => 1, 'failed' => 1, 'completed' => 2], $stats);

        // ── Filters ──
        $page->set('statusFilter', 'failed')->assertSee($b->reference)->assertDontSee($a->reference)->assertDontSee($c->reference)
            ->set('statusFilter', '')->set('sourceFilter', 'manual')->assertSee($c->reference)->assertSee($d->reference)->assertDontSee($a->reference)
            ->set('sourceFilter', '')->set('tenantFilter', 'tenant-b')->assertSee($c->reference)->assertDontSee($b->reference)
            ->set('tenantFilter', '')->set('search', 'order-bbb')->assertSee($b->reference)->assertDontSee($a->reference)
            ->set('search', $c->reference)->assertSee($c->reference)->assertDontSee($d->reference)
            ->set('search', '')->set('dateFrom', now()->subDays(2)->toDateString())->assertDontSee($a->reference)->assertSee($b->reference)
            ->set('dateFrom', '')->set('dateTo', now()->subDays(5)->toDateString())->assertSee($a->reference)->assertDontSee($b->reference)
            ->call('clearFilters')->assertSet('statusFilter', '')->assertSee($b->reference);

        // ── CSV export honours the filters ──
        $csv = null;
        $export = Livewire::test(RefundsList::class)->set('statusFilter', 'completed')->call('export');
        $export->assertDispatched('csv-download', function (string $event, array $params) use (&$csv) {
            $csv = base64_decode($params['content']);

            return str_starts_with($params['filename'], 'refunds-');
        });
        $this->assertStringContainsString('Reference,Store,"Order #"', $csv);
        $this->assertStringContainsString($a->reference, $csv);
        $this->assertStringContainsString($d->reference, $csv);
        $this->assertStringNotContainsString($b->reference, $csv);
        $this->assertStringNotContainsString($c->reference, $csv);

        // ── Sidebar entry sits next to Returns and uses the existing permission ──
        $orders = collect(AdminNavigation::sections())->pluck('items')->flatten(1)->firstWhere('label', 'Orders');
        $children = collect($orders['children'])->pluck('permission', 'route');
        $this->assertSame('sales.orders.view', $children['admin.orders.refunds.index']);
        $routes = $children->keys()->values();
        $this->assertSame(1, $routes->search('admin.orders.refunds.index') - $routes->search('admin.orders.returns.index'));
    }
}
