<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product as CentralProduct;
use App\Models\ProductVariant as CentralProductVariant;
use App\Models\Tenant;
use App\Models\Tenant\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Propagates a central product/variant price change to every tenant that
 * carries it, touching only price-derived columns (price, default_price,
 * sell_price, default_sell_price, profit). Product/variant details, stock,
 * and weight are left untouched.
 *
 * Recalculation reuses each tenant's stored fixed_shipping_costs (weight
 * component) and profit configuration, since only the central sale/base
 * price itself changed here.
 *
 * Queue: tenant-sync
 */
class SyncCentralProductPriceToTenantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    private const CHUNK_SIZE = 500;

    /**
     * @param int|null $centralProductId  Push a single central product (and its variants) instead of the whole catalog.
     * @param int|null $centralVariantId  Push a single central product variant (and its parent product's price) instead of the whole catalog.
     */
    public function __construct(
        protected ?int $centralProductId = null,
        protected ?int $centralVariantId = null,
    ) {
        $this->onQueue('tenant-sync');
    }

    public function handle(): void
    {
        $jobStart = microtime(true);
        info('[SyncCentralProductPriceToTenantsJob] handle: start', [
            'centralProductId' => $this->centralProductId,
            'centralVariantId' => $this->centralVariantId,
        ]);

        $targetProductId = $this->centralProductId;

        if ($this->centralVariantId !== null) {
            $step = microtime(true);
            $targetProductId ??= CentralProductVariant::query()
                ->whereKey($this->centralVariantId)
                ->value('product_id');

            info('[SyncCentralProductPriceToTenantsJob] resolved targetProductId from centralVariantId', [
                'targetProductId' => $targetProductId,
                'duration_ms' => round((microtime(true) - $step) * 1000, 2),
            ]);

            if ($targetProductId === null) {
                info('[SyncCentralProductPriceToTenantsJob] handle: end (no target product found)', [
                    'duration_ms' => round((microtime(true) - $jobStart) * 1000, 2),
                ]);

                return;
            }
        }

        $step = microtime(true);
        $priceMap = CentralProduct::query()
            ->select(['id', 'sale_price', 'base_price'])
            ->when($targetProductId !== null, fn($q) => $q->whereKey($targetProductId))
            ->get()
            ->mapWithKeys(fn($p) => [
                $p->id => (float) ($p->sale_price ?? $p->base_price ?? 0),
            ])
            ->all();

        info('[SyncCentralProductPriceToTenantsJob] built priceMap', [
            'count' => \count($priceMap),
            'duration_ms' => round((microtime(true) - $step) * 1000, 2),
        ]);

        if (empty($priceMap)) {
            info('[SyncCentralProductPriceToTenantsJob] handle: end (empty priceMap)', [
                'duration_ms' => round((microtime(true) - $jobStart) * 1000, 2),
            ]);

            return;
        }

        $step = microtime(true);
        $tenants = Tenant::all();
        info('[SyncCentralProductPriceToTenantsJob] fetched tenants', [
            'count' => $tenants->count(),
            'duration_ms' => round((microtime(true) - $step) * 1000, 2),
        ]);

        foreach ($tenants as $tenant) {
            $tenantStep = microtime(true);
            tenancy()->initialize($tenant);
            $this->syncForTenant($priceMap, $targetProductId);
            info('[SyncCentralProductPriceToTenantsJob] synced tenant', [
                'tenant_id' => $tenant->id,
                'duration_ms' => round((microtime(true) - $tenantStep) * 1000, 2),
            ]);
        }

        tenancy()->end();

        info('[SyncCentralProductPriceToTenantsJob] handle: end', [
            'duration_ms' => round((microtime(true) - $jobStart) * 1000, 2),
        ]);
    }

    /**
     * @param array<int, float> $priceMap central_product_id => sale/base price
     */
    private function syncForTenant(array $priceMap, ?int $targetProductId): void
    {
        $step = microtime(true);
        $tenantProducts = DB::connection('tenant')->table('products')
            ->whereIn('central_product_id', array_keys($priceMap))
            ->when($targetProductId !== null, fn($q) => $q->where('central_product_id', $targetProductId))
            ->select(['id', 'central_product_id', 'profit', 'fixed_shipping_costs'])
            ->get();

        info('[SyncCentralProductPriceToTenantsJob] syncForTenant: fetched tenant products', [
            'count' => $tenantProducts->count(),
            'duration_ms' => round((microtime(true) - $step) * 1000, 2),
        ]);

        $productUpdates = [];
        $productIds = [];
        $salePriceByTenantProductId = [];

        $step = microtime(true);

        foreach ($tenantProducts as $tenantProduct) {
            $salePrice = $priceMap[$tenantProduct->central_product_id];
            $centralCosts = (array) (json_decode((string) $tenantProduct->fixed_shipping_costs, true) ?? []);

            [$prices, $newProfit] = $this->recalculate($salePrice, $centralCosts, $tenantProduct->profit);

            $productUpdates[$tenantProduct->id] = [
                'price' => json_encode($prices, JSON_THROW_ON_ERROR),
                'default_price' => $prices['default'] ?? 0,
                'profit' => json_encode($newProfit, JSON_THROW_ON_ERROR),
            ];
            $productIds[] = $tenantProduct->id;
            $salePriceByTenantProductId[$tenantProduct->id] = $tenantProduct->central_product_id;
        }

        info('[SyncCentralProductPriceToTenantsJob] syncForTenant: recalculated product prices', [
            'count' => \count($productUpdates),
            'duration_ms' => round((microtime(true) - $step) * 1000, 2),
        ]);

        $step = microtime(true);
        $this->flushBatchedUpdate('products', $productUpdates, ['price', 'default_price', 'profit']);
        info('[SyncCentralProductPriceToTenantsJob] syncForTenant: flushed product updates', [
            'duration_ms' => round((microtime(true) - $step) * 1000, 2),
        ]);

        if (empty($productIds)) {
            return;
        }

        $variantUpdates = [];

        $step = microtime(true);

        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $productIdChunk) {
            $variants = ProductVariant::query()
                ->whereIn('product_id', $productIdChunk)
                ->when(
                    $this->centralVariantId !== null,
                    fn($q) => $q->where('central_product_variant_id', $this->centralVariantId)
                )
                ->select(['id', 'profit', 'real_price', 'product_id', 'central_product_variant_id'])
                ->get();

            $centralCostsByVariantId = CentralProductVariant::query()
                ->whereIn('id', $variants->pluck('central_product_variant_id')->filter()->all())
                ->pluck('fixed_shipping_costs', 'id');

            foreach ($variants as $variant) {
                $centralProductId = $salePriceByTenantProductId[$variant->product_id] ?? null;

                if ($centralProductId === null) {
                    continue;
                }

                $rawCentralCosts = $centralCostsByVariantId[$variant->central_product_variant_id] ?? null;
                $costsForVariant = (array) (json_decode((string) $rawCentralCosts, true) ?? []);
                $realPrice = (float) ($variant->real_price ?? 0);

                [$vPrices, $vNewProfit] = $this->recalculate($realPrice, $costsForVariant, $variant->profit);

                $variantUpdates[$variant->id] = [
                    'sell_price' => json_encode($vPrices, JSON_THROW_ON_ERROR),
                    'default_sell_price' => $vPrices['default'] ?? 0,
                    'profit' => json_encode($vNewProfit, JSON_THROW_ON_ERROR),
                ];
            }
        }

        info('[SyncCentralProductPriceToTenantsJob] syncForTenant: recalculated variant prices', [
            'count' => \count($variantUpdates),
            'duration_ms' => round((microtime(true) - $step) * 1000, 2),
        ]);

        $step = microtime(true);
        $this->flushBatchedUpdate('product_variants', $variantUpdates, ['sell_price', 'default_sell_price', 'profit']);
        info('[SyncCentralProductPriceToTenantsJob] syncForTenant: flushed variant updates', [
            'duration_ms' => round((microtime(true) - $step) * 1000, 2),
        ]);
    }

    /**
     * @param array<string,float> $costs
     * @return array{0: array<string,float>, 1: array<string,array>}
     */
    private function recalculate(float $basePrice, array $costs, mixed $existingProfit): array
    {
        if ($existingProfit !== null) {
            $profitData = is_string($existingProfit) ? (json_decode($existingProfit, true) ?? []) : ($existingProfit ?? []);
        } else {
            $tenantPct = (float) (tenant('profit_percentage') ?? 0);
            $profitData = [];
            foreach (['default', ...array_keys($costs)] as $k) {
                $profitData[(string) $k] = ['profit_type' => 'percentage', 'profit_value' => $tenantPct, 'total_profit' => 0];
            }
        }

        $prices = [];
        $newProfit = [];

        foreach ($profitData as $key => $row) {
            $type = ($row['profit_type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage';
            $value = (float) ($row['profit_value'] ?? 0);
            $shipping = (float) ($costs[(string) $key] ?? 0);
            $profitAmt = $type === 'percentage' ? round($basePrice * $value / 100, 2) : round($value, 2);
            $prices[(string) $key] = round($basePrice + $profitAmt + $shipping, 2);
            $newProfit[(string) $key] = ['profit_type' => $type, 'profit_value' => $value, 'total_profit' => $profitAmt];
        }

        return [$prices, $newProfit];
    }

    /**
     * @param array<int, array<string, mixed>> $updates id => [column => value]
     * @param list<string> $columns
     */
    private function flushBatchedUpdate(string $table, array $updates, array $columns): void
    {
        if (empty($updates)) {
            return;
        }

        $chunkIndex = 0;

        foreach (array_chunk($updates, self::CHUNK_SIZE, true) as $chunk) {
            $chunkStep = microtime(true);
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

            info('[SyncCentralProductPriceToTenantsJob] flushBatchedUpdate: flushed chunk', [
                'table' => $table,
                'chunk_index' => $chunkIndex,
                'rows' => \count($ids),
                'duration_ms' => round((microtime(true) - $chunkStep) * 1000, 2),
            ]);

            $chunkIndex++;
        }
    }
}
