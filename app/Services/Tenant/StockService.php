<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Models\Product as CentralProduct;
use App\Models\ProductVariant as CentralVariant;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ProductVariant as TenantVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StockService
 *
 * Decrements stock on all levels when a payment is confirmed:
 *
 *  1. Tenant ProductVariant.stock  (if the item has a variant)
 *  2. Tenant Product.stock         (always — either the sole stock field or aggregate)
 *  3. Central ProductVariant.stock (when central product has manage_stock = true)
 *  4. Central Product.stock        (when manage_stock = true)
 *  5. Central Product.sold_count   incremented by qty
 *
 * Stock never goes below 0 — GREATEST(0, stock - qty) is used everywhere.
 * All operations for a single order run inside one DB transaction on the tenant
 * connection; the central DB operations use CentralConnection models which always
 * target the central database regardless of the active tenant context.
 */
class StockService
{
    /**
     * Decrement stock for every item in a fully-paid order.
     * Safe to call from any tenant context.
     */
    public function decrementForOrder(Order $order): void
    {
        // Ensure relations are loaded so we don't fire extra queries per item
        $order->loadMissing(['items.product', 'items.variant']);

        // Track which central products need their stock pushed to all tenants.
        $affectedCentralProductIds = [];

        // Tenant-side decrements run inside a single tenant-DB transaction.
        // Central-side operations use CentralConnection (separate DB) so they
        // commit immediately and are always visible before the job runs.
        DB::transaction(function () use ($order, &$affectedCentralProductIds): void {
            foreach ($order->items as $item) {
                $qty = max(1, (int) $item->qty);

                if ($item->variant) {
                    $centralId = $this->handleVariantItem($item->variant, $qty);
                } elseif ($item->product) {
                    $centralId = $this->handleSimpleProductItem($item->product, $qty);
                } else {
                    Log::warning("StockService: OrderItem #{$item->id} has no product or variant — skipped.");
                    $centralId = null;
                }

                if ($centralId !== null) {
                    $affectedCentralProductIds[] = $centralId;
                }
            }
        });

        // Dispatch one sync job per unique central product AFTER the transaction
        // has committed, so the job always sees the up-to-date central stock.
        foreach (array_unique($affectedCentralProductIds) as $centralProductId) {
            SyncCentralProductStockToTenantsJob::dispatch($centralProductId);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Handle an order item that is tied to a specific variant.
     *  - Decrements tenant variant stock
     *  - Decrements tenant product stock (the product-level aggregate)
     *  - Decrements central variant stock (if manage_stock)
     *  - Decrements central product stock (if manage_stock)
     *  - Increments central product sold_count
     *
     * @return int|null  The central product ID that was decremented, or null if none.
     */
    private function handleVariantItem(TenantVariant $tenantVariant, int $qty): ?int
    {
        // 1. Tenant variant stock
        TenantVariant::where('id', $tenantVariant->id)
            ->update(['stock' => DB::raw("GREATEST(0, `stock` - {$qty})")]);

        // 2. Tenant product stock (aggregate)
        if ($tenantVariant->product_id) {
            TenantProduct::where('id', $tenantVariant->product_id)
                ->update(['stock' => DB::raw("GREATEST(0, `stock` - {$qty})")]);
        }

        // 3+4+5. Central side
        if ($tenantVariant->central_product_variant_id) {
            return $this->decrementCentralVariant(
                centralVariantId: $tenantVariant->central_product_variant_id,
                qty: $qty,
            );
        }

        return null;
    }

    /**
     * Handle a simple (no-variant) order item.
     *  - Decrements tenant product stock
     *  - Decrements central product stock (if manage_stock)
     *  - Increments central product sold_count
     *
     * @return int|null  The central product ID that was decremented, or null if none.
     */
    private function handleSimpleProductItem(TenantProduct $tenantProduct, int $qty): ?int
    {
        // 1. Tenant product stock
        TenantProduct::where('id', $tenantProduct->id)
            ->update(['stock' => DB::raw("GREATEST(0, `stock` - {$qty})")]);

        // 2+3. Central side
        if ($tenantProduct->central_product_id) {
            $this->decrementCentralProduct(
                centralProductId: $tenantProduct->central_product_id,
                qty: $qty,
            );
            return $tenantProduct->central_product_id;
        }

        return null;
    }

    /**
     * Decrement a central ProductVariant (and its parent Product) when
     * manage_stock is enabled on the central product.
     *
     * @return int|null  The central product ID, or null if not found.
     */
    private function decrementCentralVariant(int $centralVariantId, int $qty): ?int
    {
        // CentralVariant uses CentralConnection — always hits the central DB
        $centralVariant = CentralVariant::find($centralVariantId);

        if (! $centralVariant) {
            Log::warning("StockService: Central variant #{$centralVariantId} not found — skipped.");
            return null;
        }

        $this->decrementCentralProduct($centralVariant->product_id, $qty, $centralVariant->id);

        return $centralVariant->product_id;
    }

    /**
     * Decrement stock on the central Product (and optionally one of its Variants)
     * only when manage_stock is true on that product.
     * Also always bumps sold_count regardless of manage_stock.
     */
    private function decrementCentralProduct(int $centralProductId, int $qty, ?int $centralVariantId = null): void
    {
        // CentralProduct uses CentralConnection — always hits the central DB
        $centralProduct = CentralProduct::find($centralProductId);

        if (! $centralProduct) {
            Log::warning("StockService: Central product #{$centralProductId} not found — skipped.");
            return;
        }

        // Always track sold units
        CentralProduct::where('id', $centralProductId)
            ->increment('sold_count', $qty);

        if (! $centralProduct->manage_stock) {
            return; // stock management disabled for this central product
        }

        // Decrement central product stock
        CentralProduct::where('id', $centralProductId)
            ->update(['stock' => DB::raw("GREATEST(0, `stock` - {$qty})")]);

        // Decrement central variant stock (if applicable)
        if ($centralVariantId) {
            CentralVariant::where('id', $centralVariantId)
                ->update(['stock' => DB::raw("GREATEST(0, `stock` - {$qty})")]);
        }
    }
}

