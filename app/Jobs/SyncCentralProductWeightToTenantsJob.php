<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product as CentralProduct;
use App\Models\ProductVariant as CentralProductVariant;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Propagates a central product/variant weight_grams change to every tenant
 * that carries it, touching only the `weight_grams` column. Product/variant
 * details, stock, and price are left untouched.
 *
 * Shipping-cost/price recalculation triggered by a weight change is handled
 * separately by SyncProductFixedShippingCosts -> SyncFixedShippingCostsToTenantsJob.
 *
 * Queue: tenant-sync
 */
class SyncCentralProductWeightToTenantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    private const CHUNK_SIZE = 500;

    /**
     * @param int|null $centralProductId  Push a single central product's weight instead of the whole catalog.
     * @param int|null $centralVariantId  Push a single central product variant's weight instead of the whole catalog.
     */
    public function __construct(
        protected ?int $centralProductId = null,
        protected ?int $centralVariantId = null,
    ) {
        $this->onQueue('tenant-sync');
    }

    public function handle(): void
    {
        if ($this->centralVariantId !== null) {
            $weight = CentralProductVariant::query()->whereKey($this->centralVariantId)->value('weight_grams');
            $weight = $weight !== null ? (int) $weight : null;

            $this->pushToTenants(function () use ($weight) {
                DB::connection('tenant')->table('product_variants')
                    ->where('central_product_variant_id', $this->centralVariantId)
                    ->update(['weight_grams' => $weight]);
            });

            return;
        }

        if ($this->centralProductId !== null) {
            $weight = CentralProduct::withTrashed()->whereKey($this->centralProductId)->value('weight_grams');
            $weight = $weight !== null ? (int) $weight : null;

            $this->pushToTenants(function () use ($weight) {
                DB::connection('tenant')->table('products')
                    ->where('central_product_id', $this->centralProductId)
                    ->update(['weight_grams' => $weight]);
            });

            return;
        }

        // Whole-catalog resync: batch every product then every variant.
        $products = CentralProduct::withTrashed()->select(['id', 'weight_grams'])->get();
        $variants = CentralProductVariant::query()->select(['id', 'weight_grams'])->get();

        $this->pushToTenants(function () use ($products, $variants) {
            foreach ($products->chunk(self::CHUNK_SIZE) as $chunk) {
                $this->flushBatchedUpdate(
                    'products',
                    'central_product_id',
                    $chunk->mapWithKeys(fn($p) => [$p->id => $p->weight_grams !== null ? (int) $p->weight_grams : null])->all()
                );
            }

            foreach ($variants->chunk(self::CHUNK_SIZE) as $chunk) {
                $this->flushBatchedUpdate(
                    'product_variants',
                    'central_product_variant_id',
                    $chunk->mapWithKeys(fn($v) => [$v->id => $v->weight_grams !== null ? (int) $v->weight_grams : null])->all()
                );
            }
        });
    }

    private function pushToTenants(callable $callback): void
    {
        foreach (Tenant::all() as $tenant) {
            tenancy()->initialize($tenant);
            $callback();
        }

        tenancy()->end();
    }

    /**
     * @param array<int, int|null> $updates central_id => weight_grams
     */
    private function flushBatchedUpdate(string $table, string $keyColumn, array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        $cases = [];
        $bindings = [];
        $ids = [];

        foreach ($updates as $id => $weight) {
            $cases[] = "WHEN {$keyColumn} = ? THEN ?";
            $bindings[] = $id;
            $bindings[] = $weight;
            $ids[] = $id;
        }

        $caseClause = implode(' ', $cases);
        $placeholders = implode(',', array_fill(0, \count($ids), '?'));

        DB::connection('tenant')->statement(
            "UPDATE {$table} SET weight_grams = CASE {$caseClause} ELSE weight_grams END WHERE {$keyColumn} IN ({$placeholders})",
            array_merge($bindings, $ids)
        );
    }
}
