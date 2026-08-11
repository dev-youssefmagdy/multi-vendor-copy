<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateCurrencyRatesCommand extends Command
{
    protected $signature = 'currencies:update-rates
        {--base=USD : Base currency code to convert from}
        {--code=* : Limit updates to one or more target currency codes}
        {--dry-run : Fetch and display rates without saving}';

    protected $description = 'Update central currency conversion rates from the chosen base currency to all configured currencies';

    public function handle(CentralCatalogTenantSyncService $tenantSyncService): int
    {
        $base = strtoupper((string) $this->option('base'));
        $selectedCodes = collect($this->option('code'))->filter()->map(fn($code) => strtoupper((string) $code))->values();
        $dryRun = (bool) $this->option('dry-run');

        $response = Http::timeout(20)->acceptJson()->get(rtrim((string) config('services.currency_rates.url', 'https://open.er-api.com/v6/latest'), '/') . '/' . $base);

        if (!$response->successful()) {
            $this->error('Failed to fetch currency rates from the configured provider.');

            return self::FAILURE;
        }

        $payload = $response->json();
        $rates = collect($payload['rates'] ?? []);

        if ($rates->isEmpty()) {
            $this->error('The rate provider returned no rates.');

            return self::FAILURE;
        }

        $currencies = Currency::query()
            ->when($selectedCodes->isNotEmpty(), fn($query) => $query->whereIn('code', $selectedCodes->all()))
            ->orderBy('code')
            ->get();

        if ($currencies->isEmpty()) {
            $this->warn('No matching currencies found to update.');

            return self::SUCCESS;
        }

        $rows = [];
        $updated = 0;

        foreach ($currencies as $currency) {
            /** @var Currency $currency */
            $code = strtoupper((string) $currency->code);
            $rate = $code === $base ? 1.0 : $rates->get($code);

            if ($rate === null) {
                $rows[] = [$code, $currency->name, 'missing'];
                continue;
            }

            if (!$dryRun) {
                Currency::query()->whereKey($currency->getKey())->update([
                    'conversion_rate' => number_format((float) $rate, 6, '.', ''),
                ]);
            }

            $rows[] = [$code, $currency->name, number_format((float) $rate, 6, '.', '')];
            $updated++;
        }

        $this->table(['Code', 'Name', 'Rate'], $rows);

        if (!$dryRun && $updated > 0) {
            $tenantSyncService->syncAllTenants(['currencies']);
        }

        $this->info(($dryRun ? 'Prepared' : 'Updated') . " {$updated} currency rate(s) using base {$base}.");

        return self::SUCCESS;
    }
}
