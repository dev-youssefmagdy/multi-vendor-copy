<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Models\Product as CentralProduct;
use App\Models\ProductVariant as CentralVariant;
use App\Models\Refund;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Services\Orders\OrderPolicyService;
use App\Services\Tenant\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Phase 1 foundation: StockService decrement → restore round-trips, idempotency guards,
 * restock / reserve helpers, the new order columns + backfill migration, Order helpers and
 * OrderPolicyService. Scenarios are consolidated because each tenant boot is expensive.
 */
class StockRestoreTest extends TestCase
{
    use BuildsOrders;
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private const ORDERS_MIGRATION = 'database/migrations/tenant/2026_10_08_000001_add_cancellation_and_refund_columns_to_orders_table.php';

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

    #[Test]
    public function stock_round_trips_exactly_and_idempotently_for_own_and_central_products(): void
    {
        Bus::fake([SyncCentralProductStockToTenantsJob::class]);

        $this->tenant->run(function (): void {
            /** @var StockService $stock */
            $stock = app(StockService::class);
            $customer = $this->createCustomer();

            // ── Own product: variant + product aggregate ─────────────────────
            $own = $this->createOwnProductVariant(stock: 10);
            $unlimited = $this->createOwnProductVariant(stock: null);
            $order = $this->createOrder($customer, [[$own, 3], [$unlimited, 2]], OrderStatus::Processing, paid: true);

            $this->assertTrue($stock->decrementForOrder($order));
            $this->assertNotNull($order->stock_deducted_at);
            $this->assertNotNull($order->fresh()->stock_deducted_at);
            $this->assertOwnStock($own, 7, 7);
            $this->assertOwnStock($unlimited, null, null);

            // Second decrement (e.g. webhook + success callback) is a no-op.
            $this->assertFalse($stock->decrementForOrder($order->fresh()));
            $this->assertOwnStock($own, 7, 7);
            $this->assertTrue($order->fresh()->needsStockRestore());

            $this->assertTrue($stock->restoreForOrder($order));
            $this->assertNotNull($order->fresh()->stock_restored_at);
            $this->assertOwnStock($own, 10, 10);
            $this->assertOwnStock($unlimited, null, null);

            // Restore twice → still exact; decrement after restore → no-op.
            $this->assertFalse($stock->restoreForOrder($order->fresh()));
            $this->assertFalse($stock->decrementForOrder($order->fresh()));
            $this->assertOwnStock($own, 10, 10);
            $this->assertFalse($order->fresh()->needsStockRestore());

            // ── Unpaid COD order: stock never deducted → nothing restored ───
            $cod = $this->createOrder($customer, [[$own, 4]], OrderStatus::Pending, paid: false);
            $this->assertFalse($stock->restoreForOrder($cod));
            $this->assertNull($cod->fresh()->stock_restored_at);
            $this->assertOwnStock($own, 10, 10);

            // ── Floors: decrement never goes below 0 ────────────────────────
            $scarce = $this->createOwnProductVariant(stock: 1);
            $oversold = $this->createOrder($customer, [[$scarce, 3]], OrderStatus::Processing, paid: true);
            $stock->decrementForOrder($oversold);
            $this->assertOwnStock($scarce, 0, 0);

            // ── Central catalog variant (manage_stock) ──────────────────────
            $central = $this->createCentralCatalogVariant(stock: 20, manageStock: true, soldCount: 5);
            $centralOrder = $this->createOrder($customer, [[$central['variant'], 2]], OrderStatus::Processing, paid: true);

            $this->assertTrue($stock->decrementForOrder($centralOrder));
            $this->assertCentralStock($central, tenantVariant: 18, centralVariant: 18, centralProduct: 18, sold: 7);

            $this->assertTrue($stock->restoreForOrder($centralOrder));
            $this->assertFalse($stock->restoreForOrder($centralOrder));
            $this->assertCentralStock($central, tenantVariant: 20, centralVariant: 20, centralProduct: 20, sold: 5);

            // sold_count reversal is floored at 0 (order stamped as deducted, but sold_count was never bumped).
            $legacy = $this->createCentralCatalogVariant(stock: 5, manageStock: false, soldCount: 0);
            $legacyOrder = $this->createOrder($customer, [[$legacy['variant'], 3]], OrderStatus::Processing, paid: true, stockDeducted: true);
            $this->assertTrue($stock->restoreForOrder($legacyOrder));
            // manage_stock = false → central stock untouched, tenant stock still restored.
            $this->assertCentralStock($legacy, tenantVariant: 8, centralVariant: 5, centralProduct: 5, sold: 0);

            Bus::assertDispatched(
                SyncCentralProductStockToTenantsJob::class,
                fn (SyncCentralProductStockToTenantsJob $job) => $job->centralProductId === $central['central_product']->id,
            );
            Bus::assertDispatchedTimes(SyncCentralProductStockToTenantsJob::class, 3); // central decrement + restore, legacy restore

            // ── restockItem (returns) ───────────────────────────────────────
            $delivered = $this->createOrder($customer, [[$own, 2]], OrderStatus::Delivered, paid: true);
            $stock->decrementForOrder($delivered);
            $this->assertOwnStock($own, 8, 8);
            $this->assertTrue($stock->restockItem($delivered->items->first(), 1));
            $this->assertOwnStock($own, 9, 9);

            $centralDelivered = $this->createOrder($customer, [[$central['variant'], 4]], OrderStatus::Delivered, paid: true);
            $stock->decrementForOrder($centralDelivered);
            $this->assertCentralStock($central, tenantVariant: 16, centralVariant: 16, centralProduct: 16, sold: 9);
            $this->assertTrue($stock->restockItem($centralDelivered->items->first(), 4));
            $this->assertCentralStock($central, tenantVariant: 20, centralVariant: 20, centralProduct: 20, sold: 5);
            // Exchange restock keeps the sale.
            $stock->restockItem($central['variant'], 1, reverseSale: false);
            $this->assertCentralStock($central, tenantVariant: 21, centralVariant: 21, centralProduct: 21, sold: 5);
            $stock->reserveVariant($central['variant'], 1);

            // COD delivered order: stock was never deducted, so a return must not inflate stock.
            $codDelivered = $this->createOrder($customer, [[$own, 1]], OrderStatus::Delivered, paid: false);
            $this->assertFalse($stock->restockItem($codDelivered->items->first(), 1));
            $this->assertOwnStock($own, 9, 9);

            // ── reserve / release (exchanges) ───────────────────────────────
            $stock->reserveVariant($own, 2);
            $this->assertOwnStock($own, 7, 7);
            $stock->releaseVariant($own, 2);
            $this->assertOwnStock($own, 9, 9);

            $stock->reserveVariant($central['variant'], 3);
            $this->assertCentralStock($central, tenantVariant: 17, centralVariant: 17, centralProduct: 17, sold: 5);
            $stock->releaseVariant($central['variant'], 3);
            $this->assertCentralStock($central, tenantVariant: 20, centralVariant: 20, centralProduct: 20, sold: 5);
        });
    }

    #[Test]
    public function order_columns_helpers_refund_model_and_order_policy(): void
    {
        $this->tenant->run(function (): void {
            // ── Migration: reversible + backfills stock_deducted_at for paid orders ──
            $migration = require base_path(self::ORDERS_MIGRATION);
            $migration->down();
            $this->assertFalse(Schema::hasColumn('orders', 'stock_deducted_at'));

            $customer = $this->createCustomer();
            $paidId = DB::table('orders')->insertGetId([
                'uuid' => 'paid-legacy', 'customer_id' => $customer->id, 'status' => 'delivered', 'paid' => true,
                'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-02 11:00:00',
            ]);
            $unpaidId = DB::table('orders')->insertGetId([
                'uuid' => 'unpaid-legacy', 'customer_id' => $customer->id, 'status' => 'pending', 'paid' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $migration->up();
            foreach (['cancelled_at', 'cancellation_reason', 'cancellation_note', 'cancelled_by_type', 'cancelled_by_id', 'stock_deducted_at', 'stock_restored_at', 'refunded_amount', 'refunded_at'] as $column) {
                $this->assertTrue(Schema::hasColumn('orders', $column), "orders.{$column} missing");
            }
            $this->assertSame('2026-01-02 11:00:00', (string) DB::table('orders')->where('id', $paidId)->value('stock_deducted_at'));
            $this->assertNull(DB::table('orders')->where('id', $unpaidId)->value('stock_deducted_at'));
            $this->assertEquals(0, DB::table('orders')->where('id', $unpaidId)->value('refunded_amount'));

            // ── Order casts + helpers ─────────────────────────────────────────
            $variant = $this->createOwnProductVariant(stock: 5, price: 40.0);
            $order = $this->createOrder($customer, [[$variant, 2]], OrderStatus::Processing, paid: true, attributes: [
                'shipping_charge' => 20,
                'order_group_uuid' => 'group-1',
            ]);

            $this->assertSame('group-1', $order->fresh()->order_group_uuid);
            $this->assertSame(100.0, $order->grand_total);
            $this->assertSame(OrderPaymentStatus::Paid, $order->paymentState());
            $this->assertSame(100.0, $order->remainingRefundable());
            $this->assertFalse($order->isCancelled());

            $order->update(['refunded_amount' => 30]);
            $this->assertSame(OrderPaymentStatus::PartiallyRefunded, $order->paymentState());
            $this->assertSame(70.0, $order->remainingRefundable());

            $order->update([
                'refunded_amount' => 100,
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => CancellationReason::ChangedMind,
                'cancellation_note' => 'No longer needed',
                'cancelled_by_type' => CancellationActor::Customer,
                'cancelled_by_id' => $customer->id,
            ]);
            $fresh = $order->fresh(['items']);
            $this->assertSame(OrderPaymentStatus::Refunded, $fresh->paymentState());
            $this->assertSame(0.0, $fresh->remainingRefundable());
            $this->assertTrue($fresh->isCancelled());
            $this->assertSame(CancellationReason::ChangedMind, $fresh->cancellation_reason);
            $this->assertSame(CancellationActor::Customer, $fresh->cancelled_by_type);

            $unpaid = $this->createOrder($customer, [[$variant, 1]]);
            $this->assertSame(OrderPaymentStatus::Unpaid, $unpaid->paymentState());
            $this->assertSame(0.0, $unpaid->remainingRefundable());

            // ── Central refunds table + model ────────────────────────────────
            $refund = Refund::create([
                'tenant_id' => $this->tenant->id,
                'order_number' => $order->uuid,
                'source' => 'cancellation',
                'reason' => 'Changed my mind',
                'items_amount' => 80,
                'shipping_amount' => 20,
                'amount' => 100,
                'payment_method' => 'stripe',
                'refund_method' => 'original_payment',
                'requested_by_type' => 'customer',
                'requested_by_id' => $customer->id,
            ]);
            $this->assertMatchesRegularExpression('/^RF-\d{8}-[A-Z0-9]{6}$/', $refund->reference);
            $this->assertSame(RefundStatus::Pending, $refund->fresh()->status);
            $this->assertNotNull($refund->requested_at);
            $this->assertSame(1, $order->refundsQuery()->reserving()->count());
            $this->assertSame(0, Refund::query()->forOrder($this->tenant->id, 'other-order')->count());

            // ── OrderPolicyService ───────────────────────────────────────────
            $policy = new OrderPolicyService;
            $this->assertSame(OrderPolicyService::DEFAULTS, $policy->all());
            $this->assertTrue($policy->cancellationAllowProcessing());
            $this->assertSame(0, $policy->cancellationWindowHours());

            $policy->update([
                'cancellation_allow_processing' => false,
                'cancellation_window_hours' => '12',
                'auto_refund_on_cancel' => '0',
                'unknown_key' => 'ignored',
            ]);

            $reloaded = new OrderPolicyService;
            $this->assertFalse($reloaded->cancellationAllowProcessing());
            $this->assertSame(12, $reloaded->cancellationWindowHours());
            $this->assertFalse($reloaded->autoRefundOnCancel());
            $this->assertTrue($reloaded->exchangeEnabled());
            $this->assertTrue($reloaded->restockReturnedItems());
        });

        // Read from the central context through $tenant->run(), which restores the context.
        $this->assertNull(tenant());
        $this->assertFalse(app(OrderPolicyService::class)->cancellationAllowProcessing($this->tenant));
        $this->assertNull(tenant());
        $this->assertTrue(app(OrderPolicyService::class)->cancellationAllowProcessing(), 'No tenant → defaults.');
    }

    private function assertOwnStock(ProductVariant $variant, ?int $variantStock, ?int $productStock): void
    {
        $this->assertSame($variantStock, ProductVariant::query()->whereKey($variant->id)->value('stock'), 'tenant variant stock');
        $this->assertSame(
            $productStock,
            Product::query()->withoutGlobalScopes()->whereKey($variant->product_id)->value('stock'),
            'tenant product stock',
        );
    }

    /** @param array{central_product: CentralProduct, central_variant: CentralVariant, product: Product, variant: ProductVariant} $central */
    private function assertCentralStock(array $central, int $tenantVariant, int $centralVariant, int $centralProduct, int $sold): void
    {
        $this->assertSame($tenantVariant, ProductVariant::query()->whereKey($central['variant']->id)->value('stock'), 'tenant variant stock');
        $this->assertSame($tenantVariant, Product::query()->withoutGlobalScopes()->whereKey($central['product']->id)->value('stock'), 'tenant product stock');
        $this->assertSame($centralVariant, CentralVariant::query()->whereKey($central['central_variant']->id)->value('stock'), 'central variant stock');
        $this->assertSame($centralProduct, CentralProduct::query()->withoutGlobalScopes()->whereKey($central['central_product']->id)->value('stock'), 'central product stock');
        $this->assertSame($sold, CentralProduct::query()->withoutGlobalScopes()->whereKey($central['central_product']->id)->value('sold_count'), 'central sold_count');
    }
}
