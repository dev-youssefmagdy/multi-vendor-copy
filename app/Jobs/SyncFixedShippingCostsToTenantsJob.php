<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product as CentralProduct;
use App\Models\ProductVariant as CentralProductVariant;
use App\Models\Tenant;
use App\Models\Tenant\ProductVariant;
use App\Services\Tenant\TenantPricingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SyncFixedShippingCostsToTenantsJob
 *
 * After SyncProductFixedShippingCosts has rebuilt the `fixed_shipping_costs`
 * JSON column on every central product, this job fans the values out to every
 * tenant database.
 *
 * Strategy:
 *  - Loads only (id, fixed_shipping_costs) for all central products.
 *  - Iterates all tenants and, per tenant, runs a single CASE WHEN UPDATE
 *    (chunked at 500 rows) so each tenant requires at most a handful of
 *    queries regardless of catalogue size.
 *  - Tenant-level fixed_shipping_costs overrides are preserved: only products
 *    whose tenant column is NULL (no custom override) are updated.
 *
 * Queue: tenant-sync
 */
class SyncFixedShippingCostsToTenantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** How many central products to handle per SQL CASE statement */
    private const CHUNK_SIZE = 500;

    private TenantPricingService $pricing;

    /**
     * @param int|null $centralProductId  Push a single central product (and its variants) instead of the whole catalog.
     * @param int|null $centralVariantId  Push a single central product variant (and its parent product) instead of the whole catalog.
     */
    public function __construct(
        protected ?int $centralProductId = null,
        protected ?int $centralVariantId = null,
    ) {
        $this->onQueue('tenant-sync');
    }

    public function handle(): void
    {
        $this->pricing = new TenantPricingService();
        $targetProductId = $this->centralProductId;

        if ($this->centralVariantId !== null) {
            $targetProductId ??= CentralProductVariant::query()
                ->whereKey($this->centralVariantId)
                ->value('product_id');

            if ($targetProductId === null) {
                return;
            }
        }

        // Load the columns needed: costs for shipping sync + sale_price for price recalculation.
        $costMap = CentralProduct::query()
            ->select(['id', 'fixed_shipping_costs', 'sale_price', 'base_price'])
            ->when($targetProductId !== null, fn($q) => $q->whereKey($targetProductId))
            ->get()
            ->mapWithKeys(fn($p) => [
                $p->id => [
                    'costs' => $p->fixed_shipping_costs,
                    'sale_price' => (new TenantPricingService())->centralBasePrice($p),
                ]
            ])
            ->all();

        if (empty($costMap)) {
            return;
        }

        // Load per-variant shipping costs so variant prices use the variant's
        // own fixed_shipping_costs instead of falling back to the product's.
        $variantCostMap = CentralProductVariant::query()
            ->select(['id', 'fixed_shipping_costs'])
            ->when($targetProductId !== null, fn($q) => $q->where('product_id', $targetProductId))
            ->get()
            ->mapWithKeys(fn($v) => [$v->id => $v->fixed_shipping_costs])
            ->all();

        $tenants = Tenant::all();


        foreach ($tenants as $tenant) {
            // try {
                tenancy()->initialize($tenant);
                $this->syncForTenant($costMap, $variantCostMap, $targetProductId);
            // } catch (\Throwable $e) {
            //     Log::error(
            //         "SyncFixedShippingCostsToTenantsJob: Failed for tenant [{$tenant->id}]: " .
            //         $e->getMessage()
            //     );
            //     // Continue with remaining tenants even if one fails.
            // }
        }

        tenancy()->end();
    }

    /**
     * Push the cost map to the currently-initialized tenant DB in chunks.
     * Uses a single CASE WHEN UPDATE per chunk to minimise round-trips.
     *
     * All matching rows are updated so central admin changes propagate immediately.
     * After syncing shipping costs, also recalculates product/variant prices for
     * any tenant rows that have a stored profit configuration.
     *
     * @param array<int, array{costs: array<string,float>|null, sale_price: float}> $costMap
     * @param array<int, array<string,float>|null> $variantCostMap
     */
    private function syncForTenant(array $costMap, array $variantCostMap, ?int $targetProductId): void
    {
        // Single read of the rows we need to touch, instead of a blind
        // UPDATE over every central_product_id followed by a re-SELECT.
        $tenantProducts = DB::connection('tenant')->table('products')
            ->whereIn('central_product_id', array_keys($costMap))
            ->when($targetProductId !== null, fn($q) => $q->where('central_product_id', $targetProductId))
            ->select(['id', 'central_product_id', 'profit'])
            ->get();

        $productUpdates = [];
        $productIds = [];
        $productCostEntries = [];

        foreach ($tenantProducts as $tenantProduct) {
            $entry = $costMap[$tenantProduct->central_product_id];

            $salePrice = $entry['sale_price'];
            $centralCosts = (array) ($entry['costs'] ?? []);

            $existingProfit = $tenantProduct->profit !== null ? json_decode($tenantProduct->profit, true) : null;

            $result = $this->pricing->forCatalog(
                $salePrice,
                $centralCosts,
                is_array($existingProfit) ? $existingProfit : null,
                (float) (tenant('profit_percentage') ?? 0),
            );
            $prices = $result['prices'];
            $newProfit = $result['profit'];

            $productUpdates[$tenantProduct->id] = [
                'fixed_shipping_costs' => $entry['costs'] !== null ? json_encode($entry['costs'], JSON_THROW_ON_ERROR) : null,
                'price' => json_encode($prices, JSON_THROW_ON_ERROR),
                'default_price' => $prices['default'] ?? 0,
                'profit' => json_encode($newProfit, JSON_THROW_ON_ERROR),
            ];
            $productIds[] = $tenantProduct->id;
            $productCostEntries[$tenantProduct->id] = $entry;
        }

        $this->flushBatchedUpdate('products', $productUpdates, ['fixed_shipping_costs', 'price', 'default_price', 'profit']);

        if (empty($productIds)) {
            return;
        }

        // Recalculate variant prices for all variants belonging to the touched products.
        $variantUpdates = [];

        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $productIdChunk) {
            $variants = ProductVariant::query()
                ->whereIn('product_id', $productIdChunk)
                ->select(['id', 'profit', 'real_price', 'product_id', 'weight_grams', 'central_product_variant_id'])
                ->get();

            foreach ($variants as $variant) {
                $tenantProductEntry = $productCostEntries[$variant->product_id] ?? null;

                if ($tenantProductEntry === null) {
                    continue;
                }
                $centralCosts = (array) ($tenantProductEntry['costs'] ?? []);

                // Prefer the variant's own shipping costs; fall back to the
                // product's costs when the variant has none of its own.
                $variantCosts = $variantCostMap[$variant->central_product_variant_id] ?? null;
                $costsForVariant = $variantCosts !== null ? (array) $variantCosts : $centralCosts;

                $result = $this->pricing->forCatalog(
                    (float) ($variant->real_price ?? 0),
                    $costsForVariant,
                    is_array($variant->profit) ? $variant->profit : null,
                    (float) (tenant('profit_percentage') ?? 0),
                );
                $vPrices = $result['prices'];
                $vNewProfit = $result['profit'];

                $variantUpdates[$variant->id] = [
                    'sell_price' => json_encode($vPrices, JSON_THROW_ON_ERROR),
                    'default_sell_price' => $vPrices['default'] ?? 0,
                    'profit' => json_encode($vNewProfit, JSON_THROW_ON_ERROR),
                ];
            }
        }

        $this->flushBatchedUpdate('product_variants', $variantUpdates, ['sell_price', 'default_sell_price', 'profit']);
    }

    /**
     * Apply a set of per-row column updates as chunked CASE WHEN UPDATE
     * statements, instead of one UPDATE query per row.
     *
     * @param array<int, array<string, mixed>> $updates id => [column => value]
     * @param list<string> $columns
     */
    private function flushBatchedUpdate(string $table, array $updates, array $columns): void
    {
        if (empty($updates)) {
            return;
        }

        foreach (array_chunk($updates, self::CHUNK_SIZE, true) as $chunk) {
            $ids = array_keys($chunk);
            $placeholders = implode(',', array_fill(0, \count($ids), '?'));

            $setClauses = [];
            $bindings = [];

            foreach ($columns as $column) {
                $cases = [];
                foreach ($chunk as $id => $row) {
                    $cases[] = 'WHEN id = ? THEN ?';
                    $bindings[] = $id;
                    $bindings[] = $row[$column];
                }
                $caseClause = implode(' ', $cases);
                $setClauses[] = "{$column} = CASE {$caseClause} ELSE {$column} END";
            }

            $bindings = array_merge($bindings, $ids);
            $setClause = implode(', ', $setClauses);

            DB::connection('tenant')->statement(
                "UPDATE {$table} SET {$setClause} WHERE id IN ({$placeholders})",
                $bindings
            );
        }
    }
}
