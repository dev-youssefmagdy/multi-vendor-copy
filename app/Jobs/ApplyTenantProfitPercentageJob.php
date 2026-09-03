<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\Tenant\ProductPriceCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplyTenantProfitPercentageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 0;

    public function __construct(
        public string $tenantId,
    ) {
        $this->onQueue('tenant-profit');
    }

    public function handle(ProductPriceCalculationService $service): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (!$tenant) {
            return;
        }

        $service->applyProfitPercentage($tenant);
    }
}
