<?php

namespace App\Jobs;

use App\Models\FixedShippingCost;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the `fixed_shipping_costs` JSON column on every central product.
 *
 * Triggered whenever a FixedShippingCost rule is created, updated, or deleted.
 * The column stores a pre-computed map of { "country_id": cost } where
 * cost = weight_grams × price_per_gram for that product.
 *
 * Resolution on tenant products still follows the chain:
 *   tenant product JSON → central product JSON (populated here) → null
 */
class SyncProductFixedShippingCosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** How many rows to update per CASE WHEN statement */
    private const CHUNK_SIZE = 500;

    /**
     * @param int|null $productId  Recompute a single central product instead of the whole catalog.
     * @param int|null $variantId  Recompute a single central product variant instead of the whole catalog.
     */
    public function __construct(
        protected ?int $productId = null,
        protected ?int $variantId = null,
    ) {
    }

    public function handle(): void
    {
        // Load all active rules indexed by country_id (one record per country).
        $rules = FixedShippingCost::active()
            ->get()
            ->keyBy('country_id');

        if ($this->productId !== null || $this->variantId !== null) {
            $this->syncSingle($rules);

            return;
        }

        if ($rules->isEmpty()) {
            info('Empty Rules');
            // No active rules → wipe the cached column on every product and variant.
            Product::query()->whereNotNull('fixed_shipping_costs')->update(['fixed_shipping_costs' => null]);
            ProductVariant::query()->whereNotNull('fixed_shipping_costs')->update(['fixed_shipping_costs' => null]);
            SyncFixedShippingCostsToTenantsJob::dispatch();
            return;
        }


        // Process products with a valid weight in memory-safe chunks.
        Product::query()
            ->whereNotNull('weight_grams')
            ->where('weight_grams', '>', 0)
            ->select(['id', 'weight_grams'])
            ->chunkById(self::CHUNK_SIZE, function ($products) use ($rules) {
                $updates = [];
                foreach ($products as $product) {
                    $updates[$product->id] = $this->buildCostMap($rules, (int) $product->weight_grams);
                }
                $this->flushBatchedUpdate('products', $updates);
            });

        // Products with no/zero weight have no weight-based cost — clear the column.
        Product::query()
            ->where(fn($q) => $q->whereNull('weight_grams')->orWhere('weight_grams', '<=', 0))
            ->whereNotNull('fixed_shipping_costs')
            ->update(['fixed_shipping_costs' => null]);

        // Process variants with a valid weight of their own (independent of the product's weight).
        ProductVariant::query()
            ->whereNotNull('weight_grams')
            ->where('weight_grams', '>', 0)
            ->select(['id', 'weight_grams'])
            ->chunkById(self::CHUNK_SIZE, function ($variants) use ($rules) {
                $updates = [];
                foreach ($variants as $variant) {
                    $updates[$variant->id] = $this->buildCostMap($rules, (int) $variant->weight_grams);
                }
                $this->flushBatchedUpdate('product_variants', $updates);
            });

        // Variants with no/zero weight have no weight-based cost — clear the column.
        ProductVariant::query()
            ->where(fn($q) => $q->whereNull('weight_grams')->orWhere('weight_grams', '<=', 0))
            ->whereNotNull('fixed_shipping_costs')
            ->update(['fixed_shipping_costs' => null]);

        info('Before SyncFixedShippingCostsToTenantsJob');

        // Push the freshly-computed central values down to every tenant.
        SyncFixedShippingCostsToTenantsJob::dispatch();
    }

    /**
     * Recompute fixed_shipping_costs for a single product or variant, then push
     * only that row down to the tenants that carry it.
     *
     * @param \Illuminate\Support\Collection<int, FixedShippingCost> $rules
     */
    private function syncSingle($rules): void
    {
        if ($this->variantId !== null) {
            $variant = ProductVariant::query()->find($this->variantId);

            if ($variant) {
                $weight = (int) ($variant->weight_grams ?? 0);
                $variant->fixed_shipping_costs = $weight > 0 ? $this->buildCostMap($rules, $weight) : null;
                $variant->saveQuietly();
            }

            SyncFixedShippingCostsToTenantsJob::dispatch(centralVariantId: $this->variantId);

            return;
        }

        $product = Product::query()->find($this->productId);

        if ($product) {
            $weight = (int) ($product->weight_grams ?? 0);
            $product->fixed_shipping_costs = $weight > 0 ? $this->buildCostMap($rules, $weight) : null;
            $product->saveQuietly();
        }

        SyncFixedShippingCostsToTenantsJob::dispatch(centralProductId: $this->productId);
    }

    /**
     * @param \Illuminate\Support\Collection<int, FixedShippingCost> $rules
     * @return array<string, float>|null
     */
    private function buildCostMap($rules, int $weight): ?array
    {
        $map = [];

        /** @var FixedShippingCost $rule */
        foreach ($rules as $countryId => $rule) {
            $map[(string) $countryId] = round((float) $rule->price_per_gram * $weight, 2);
        }

        return empty($map) ? null : $map;
    }

    /**
     * Apply fixed_shipping_costs updates as a single chunked CASE WHEN UPDATE
     * instead of one UPDATE query per row.
     *
     * @param array<int, array<string, float>|null> $updates id => cost map
     */
    private function flushBatchedUpdate(string $table, array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        $cases = [];
        $bindings = [];
        $ids = [];

        foreach ($updates as $id => $costs) {
            $cases[] = 'WHEN id = ? THEN ?';
            $bindings[] = $id;
            $bindings[] = $costs !== null ? json_encode($costs, JSON_THROW_ON_ERROR) : null;
            $ids[] = $id;
        }

        $caseClause = implode(' ', $cases);
        $placeholders = implode(',', array_fill(0, \count($ids), '?'));

        DB::statement(
            "UPDATE {$table}
             SET fixed_shipping_costs = CASE {$caseClause} ELSE fixed_shipping_costs END
             WHERE id IN ({$placeholders})",
            array_merge($bindings, $ids)
        );
    }
}
