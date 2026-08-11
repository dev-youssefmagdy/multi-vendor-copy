<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product as CentralProduct;
use App\Models\Tenant;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ProductVariant as TenantVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SyncCentralProductStockToTenantsJob
 *
 * After a central product's stock is decremented (due to a sale on any tenant),
 * this job propagates the updated stock figures to every tenant that carries
 * the same product in their database.
 *
 * Strategy:
 *  - Re-reads the freshest stock value from the central DB when the job runs.
 *  - Iterates ALL tenants; the UPDATE is a no-op for tenants that do not have
 *    the product (WHERE clause matches 0 rows), so there is no need to pre-filter.
 *  - Only runs when the central product has manage_stock = true.
 *
 * Queue: tenant-sync (same queue used by the rest of the tenancy sync pipeline).
 */
class SyncCentralProductStockToTenantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum attempts before Laravel marks the job as failed */
    public int $tries = 3;

    public function __construct(
        public readonly int $centralProductId,
    ) {
        $this->onQueue('tenant-sync');
    }

    public function handle(): void
    {
        // Re-read the current (post-decrement) stock from central DB.
        // CentralProduct uses CentralConnection — always hits the central database.
        /** @var CentralProduct|null $centralProduct */
        $centralProduct = CentralProduct::with('variants')->find($this->centralProductId);

        if (! $centralProduct) {
            Log::warning("SyncCentralProductStockToTenantsJob: Central product #{$this->centralProductId} not found ��� job skipped.");
            return;
        }

        if (! $centralProduct->manage_stock) {
            // Stock is not managed centrally for this product — nothing to sync.
            return;
        }

        $productStock   = max(0, (int) $centralProduct->stock);
        $variantStocks  = $centralProduct->variants
            ->mapWithKeys(fn($v) => [$v->id => max(0, (int) $v->stock)])
            ->all();

        // Iterate every tenant. The UPDATE is a no-op for tenants that do not
        // carry this central product, so no pre-filtering is necessary.
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                // ── Tenant product stock ───────────────────────────────────
                TenantProduct::where('central_product_id', $this->centralProductId)
                    ->update(['stock' => $productStock]);

                // ── Tenant variant stocks ──────────────────────────────────
                foreach ($variantStocks as $centralVariantId => $variantStock) {
                    TenantVariant::where('central_product_variant_id', $centralVariantId)
                        ->update(['stock' => $variantStock]);
                }
            } catch (\Throwable $e) {
                Log::error(
                    "SyncCentralProductStockToTenantsJob: Failed to sync stock to tenant [{$tenant->id}] " .
                    "for central product #{$this->centralProductId}: " . $e->getMessage()
                );
                // Continue with the remaining tenants even if one fails
            }
        }

        // End tenancy context so the queue worker is back to a clean state
        tenancy()->end();
    }
}

