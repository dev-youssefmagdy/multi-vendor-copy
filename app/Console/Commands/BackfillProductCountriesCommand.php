<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\Tenant\Product as TenantProduct;
use Illuminate\Console\Command;

/**
 * One-time backfill of allowed_country_ids on tenant products from the central
 * product_country pivot. Only needed for products that existed before country
 * targeting shipped — from then on TenantCatalogSyncService keeps the column current.
 */
class BackfillProductCountriesCommand extends Command
{
    protected $signature = 'products:backfill-country-ids';

    protected $description = 'Backfill allowed_country_ids on tenant products from the central product_country pivot.';

    public function handle(): int
    {
        $payloads = Product::query()
            ->with('countries:id')
            ->get()
            ->mapWithKeys(fn (Product $product) => [
                $product->getKey() => $product->countries->isEmpty()
                    ? null
                    : json_encode($product->countries->pluck('id')->map(fn ($id) => (int) $id)->values()->all()),
            ]);

        $tenants = Tenant::query()->orderBy('id')->get();

        $this->info("Backfilling {$payloads->count()} products across {$tenants->count()} tenants...");

        $failed = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                foreach ($payloads as $centralId => $payload) {
                    TenantProduct::withoutGlobalScope('centralVisible')
                        ->where('central_product_id', $centralId)
                        ->update(['allowed_country_ids' => $payload]);
                }

                $this->line("  [{$tenant->id}] synced.");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  [{$tenant->id}] {$e->getMessage()}");
                report($e);
            } finally {
                tenancy()->end();
            }
        }

        if ($failed > 0) {
            $this->warn("Completed with {$failed} tenant(s) failing.");

            return self::FAILURE;
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
