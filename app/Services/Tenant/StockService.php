<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Jobs\SyncCentralProductStockToTenantsJob;
use App\Models\Product as CentralProduct;
use App\Models\ProductVariant as CentralVariant;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ProductVariant as TenantVariant;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StockService
 *
 * Moves stock on all levels for an order line:
 *
 *  1. Tenant ProductVariant.stock  (if the item has a variant)
 *  2. Tenant Product.stock         (always — either the sole stock field or aggregate)
 *  3. Central ProductVariant.stock (when central product has manage_stock = true)
 *  4. Central Product.stock        (when manage_stock = true)
 *  5. Central Product.sold_count   (+qty on a sale, −qty when a sale is reversed)
 *
 * Decrements never go below 0 (GREATEST(0, stock - qty) semantics) — and
 * sold_count reversals are floored at 0 too. A NULL stock (unlimited) stays NULL.
 *
 * Order-level operations are idempotent: decrementForOrder() stamps
 * orders.stock_deducted_at and restoreForOrder() stamps orders.stock_restored_at,
 * both checked under a row lock, so a double call never moves stock twice.
 *
 * Tenant-side updates run inside one tenant-DB transaction; the central DB operations
 * use CentralConnection models which always target the central database (and commit
 * immediately). One SyncCentralProductStockToTenantsJob is dispatched per affected
 * central product AFTER the tenant transaction commits.
 */
class StockService
{
    private const DECREMENT = -1;

    private const INCREMENT = 1;

    /**
     * Decrement stock for every item in a fully-paid order and stamp stock_deducted_at.
     * No-op (returns false) when the order's stock was already deducted.
     * Safe to call from any tenant context.
     */
    public function decrementForOrder(Order $order): bool
    {
        return $this->moveOrderStock($order, self::DECREMENT);
    }

    /**
     * Exact mirror of decrementForOrder(): gives the stock back (tenant variant + product,
     * central variant + product when manage_stock, sold_count −qty floored at 0) and stamps
     * stock_restored_at. No-op (returns false) when the stock was never deducted
     * (unpaid / COD orders) or was already restored.
     */
    public function restoreForOrder(Order $order): bool
    {
        return $this->moveOrderStock($order, self::INCREMENT);
    }

    /**
     * Put returned goods back into stock (tenant + central when manage_stock).
     *
     * - OrderItem target: skipped (returns false) when the order's stock was never deducted
     *   (COD) or was already restored by a cancellation — restocking would then double count.
     * - $reverseSale: also decrement central sold_count (floored at 0). Pass false for an
     *   exchange, where the replacement unit keeps the sale alive.
     *
     * Callers are responsible for their own once-only guard (e.g. return_requests.restocked_at).
     */
    public function restockItem(OrderItem|TenantVariant|TenantProduct $target, int $qty, bool $reverseSale = true): bool
    {
        if ($qty < 1) {
            return false;
        }

        [$variant, $product] = $this->resolveTarget($target);

        if ($target instanceof OrderItem) {
            $order = $target->order()->first(['id', 'stock_deducted_at', 'stock_restored_at']);

            if (! $order || $order->stock_deducted_at === null || $order->stock_restored_at !== null) {
                return false;
            }
        }

        $centralProductId = null;

        DB::transaction(function () use ($variant, $product, $qty, $reverseSale, &$centralProductId): void {
            $centralProductId = $this->moveLineStock($variant, $product, $qty, self::INCREMENT, $reverseSale);
        });

        $this->dispatchSync([$centralProductId]);

        return $variant !== null || $product !== null;
    }

    /**
     * Reserve units of an exchange replacement variant: decrements tenant variant + product
     * and the central variant + product (manage_stock). sold_count is NOT touched.
     * Availability must be checked by the caller beforehand (ChecksCartStock rules).
     */
    public function reserveVariant(TenantVariant $variant, int $qty): void
    {
        $this->moveVariantStock($variant, $qty, self::DECREMENT);
    }

    /**
     * Units that can still be sold right now — null means unlimited. The single source of the
     * storefront stock rules (ChecksCartStock) and of the exchange availability checks:
     *  - own products: the variant's stock, falling back to the product's; NULL = unlimited;
     *  - central-catalog products: unlimited unless the central product has manage_stock, then the
     *    variant's stock (falling back to the central variant's) or the product's stock.
     */
    public function availableStock(TenantProduct $product, ?TenantVariant $variant = null): ?int
    {
        $central = $product->centralProduct ?? $product->load('centralProduct')->centralProduct;

        if (! $central) {
            $ownStock = $variant ? ($variant->stock ?? $product->stock ?? null) : $product->stock;

            return $ownStock === null ? null : (int) $ownStock;
        }

        if (! ($central->manage_stock ?? false)) {
            return null;
        }

        return $variant
            ? (int) ($variant->stock ?? $variant->centralVariant?->stock ?? 0)
            : (int) ($product->stock ?? 0);
    }

    /** Release a reservation made by reserveVariant() (exact mirror). */
    public function releaseVariant(TenantVariant $variant, int $qty): void
    {
        $this->moveVariantStock($variant, $qty, self::INCREMENT);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Decrement (direction −1) or restore (+1) every line of an order, guarded by the
     * order's stock_deducted_at / stock_restored_at stamps under a row lock.
     */
    private function moveOrderStock(Order $order, int $direction): bool
    {
        $order->loadMissing('items');
        $order->items->loadMissing([
            'variant',
            'product' => fn ($query) => $query->withoutGlobalScope('centralVisible'),
        ]);

        $affectedCentralProductIds = [];
        $stampColumn = $direction === self::DECREMENT ? 'stock_deducted_at' : 'stock_restored_at';
        $stampedAt = now();

        $applied = DB::transaction(function () use ($order, $direction, $stampColumn, $stampedAt, &$affectedCentralProductIds): bool {
            $locked = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->first(['id', 'stock_deducted_at', 'stock_restored_at']);

            if (! $locked) {
                return false;
            }

            $eligible = $direction === self::DECREMENT
                ? $locked->stock_deducted_at === null
                : $locked->stock_deducted_at !== null && $locked->stock_restored_at === null;

            if (! $eligible) {
                return false;
            }

            foreach ($order->items as $item) {
                if (! $item->variant && ! $item->product) {
                    Log::warning("StockService: OrderItem #{$item->id} has no product or variant — skipped.");

                    continue;
                }

                $affectedCentralProductIds[] = $this->moveLineStock(
                    variant: $item->variant,
                    product: $item->product,
                    qty: max(1, (int) $item->qty),
                    direction: $direction,
                    adjustSoldCount: true,
                );
            }

            Order::query()->whereKey($order->getKey())->update([$stampColumn => $stampedAt]);

            return true;
        });

        if ($applied) {
            $order->setAttribute($stampColumn, $stampedAt);
            $order->syncOriginalAttribute($stampColumn);
        }

        // Dispatch AFTER the transaction has committed so the job sees fresh central stock.
        $this->dispatchSync($affectedCentralProductIds);

        return $applied;
    }

    private function moveVariantStock(TenantVariant $variant, int $qty, int $direction): void
    {
        if ($qty < 1) {
            return;
        }

        $centralProductId = null;

        DB::transaction(function () use ($variant, $qty, $direction, &$centralProductId): void {
            $centralProductId = $this->moveLineStock($variant, null, $qty, $direction, adjustSoldCount: false);
        });

        $this->dispatchSync([$centralProductId]);
    }

    /**
     * Move stock for one line. A variant line touches the tenant variant, its tenant product
     * and the linked central variant/product; a simple line touches the tenant product and the
     * linked central product.
     *
     * @return int|null The central product id whose stock was affected, or null if none.
     */
    private function moveLineStock(?TenantVariant $variant, ?TenantProduct $product, int $qty, int $direction, bool $adjustSoldCount): ?int
    {
        if ($variant) {
            TenantVariant::query()->whereKey($variant->id)->update(['stock' => $this->stockExpression('stock', $qty, $direction)]);

            if ($variant->product_id) {
                $this->moveTenantProductStock((int) $variant->product_id, $qty, $direction);
            }

            if (! $variant->central_product_variant_id) {
                return null;
            }

            $centralVariant = CentralVariant::query()->find($variant->central_product_variant_id, ['id', 'product_id']);

            if (! $centralVariant) {
                Log::warning("StockService: Central variant #{$variant->central_product_variant_id} not found — skipped.");

                return null;
            }

            $this->moveCentralStock((int) $centralVariant->product_id, $qty, $direction, $adjustSoldCount, (int) $centralVariant->id);

            return (int) $centralVariant->product_id;
        }

        if (! $product) {
            return null;
        }

        $this->moveTenantProductStock((int) $product->id, $qty, $direction);

        if (! $product->central_product_id) {
            return null;
        }

        $this->moveCentralStock((int) $product->central_product_id, $qty, $direction, $adjustSoldCount);

        return (int) $product->central_product_id;
    }

    private function moveTenantProductStock(int $productId, int $qty, int $direction): void
    {
        TenantProduct::query()
            ->withoutGlobalScope('centralVisible')
            ->whereKey($productId)
            ->update(['stock' => $this->stockExpression('stock', $qty, $direction)]);
    }

    /**
     * Central Product (and optionally one of its Variants): sold_count always moves when
     * $adjustSoldCount; stock only moves when manage_stock is true on the product.
     */
    private function moveCentralStock(int $centralProductId, int $qty, int $direction, bool $adjustSoldCount, ?int $centralVariantId = null): void
    {
        // CentralProduct uses CentralConnection — always hits the central DB
        $centralProduct = CentralProduct::query()->find($centralProductId, ['id', 'manage_stock']);

        if (! $centralProduct) {
            Log::warning("StockService: Central product #{$centralProductId} not found — skipped.");

            return;
        }

        $updates = [];

        if ($adjustSoldCount) {
            // A sale adds to sold_count; reversing a sale removes from it (floored at 0).
            $updates['sold_count'] = $this->stockExpression('sold_count', $qty, -$direction);
        }

        if ($centralProduct->manage_stock) {
            $updates['stock'] = $this->stockExpression('stock', $qty, $direction);
        }

        if ($updates !== []) {
            CentralProduct::query()->whereKey($centralProductId)->update($updates);
        }

        if ($centralProduct->manage_stock && $centralVariantId) {
            CentralVariant::query()
                ->whereKey($centralVariantId)
                ->update(['stock' => $this->stockExpression('stock', $qty, $direction)]);
        }
    }

    /**
     * `col - qty` floored at 0 for a decrement (same result as GREATEST(0, col - qty), but
     * written so it never underflows UNSIGNED columns such as sold_count), `col + qty` for an
     * increment. A NULL (unlimited) value stays NULL either way.
     */
    private function stockExpression(string $column, int $qty, int $direction): Expression
    {
        $qty = max(0, $qty);

        return $direction === self::DECREMENT
            ? DB::raw("GREATEST(`{$column}`, {$qty}) - {$qty}")
            : DB::raw("`{$column}` + {$qty}");
    }

    /** @return array{0: ?TenantVariant, 1: ?TenantProduct} */
    private function resolveTarget(OrderItem|TenantVariant|TenantProduct $target): array
    {
        if ($target instanceof TenantVariant) {
            return [$target, null];
        }

        if ($target instanceof TenantProduct) {
            return [null, $target];
        }

        $variant = $target->variant()->first();
        $product = $variant ? null : $target->product()->withoutGlobalScope('centralVisible')->first();

        return [$variant, $product];
    }

    /** @param array<int|null> $centralProductIds */
    private function dispatchSync(array $centralProductIds): void
    {
        foreach (array_unique(array_filter($centralProductIds)) as $centralProductId) {
            SyncCentralProductStockToTenantsJob::dispatch($centralProductId);
        }
    }
}
