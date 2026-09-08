<?php

namespace App\Services\Payments;

use App\Models\PaymentGateway;
use App\Models\Tenant;
use App\PaymentGateway\PaymentManager;
use Illuminate\Support\Str;

/**
 * Scores every registered gateway driver against a tenant's active target
 * countries (from TenantCountry, Prompt 13) to surface the gateways most
 * likely to let the tenant actually get paid by their intended customers.
 */
class PaymentGatewayRecommendationService
{
    public function __construct(
        private readonly PaymentManager $manager,
    ) {
    }

    /**
     * @return array<int, array{code:string,name:string,score:int,meta:array}>
     */
    public function recommend(Tenant $tenant, int $limit = 5): array
    {
        $targetCountries = $tenant->tenantCountries()
            ->where('is_active', true)
            ->with('country')
            ->get()
            ->pluck('country.iso2')
            ->filter()
            ->map(fn(string $code) => strtoupper($code))
            ->unique()
            ->values()
            ->all();

        if (empty($targetCountries)) {
            return [];
        }

        $drivers = config('payment-gateways.drivers', []);
        $names = PaymentGateway::query()->pluck('name', 'code');

        $scored = [];

        foreach (array_keys($drivers) as $code) {
            $meta = $this->manager->meta($code);
            $customerCountries = array_map('strtoupper', $meta['customer_countries'] ?? []);

            $overlap = array_values(array_intersect($targetCountries, $customerCountries));
            $score = count($overlap);

            if ($score === 0) {
                continue;
            }

            $scored[] = [
                'code' => $code,
                'name' => $names[$code] ?? Str::headline($code),
                'score' => $score,
                'meta' => $meta,
            ];
        }

        usort($scored, fn(array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }
}
