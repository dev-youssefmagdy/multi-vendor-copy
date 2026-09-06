<?php

namespace App\Services\Payments;

use App\Models\Tenant;
use App\Models\Tenant\PaymentGateway;
use App\PaymentGateway\PaymentManager;

/**
 * Computes a structured readiness report for a tenant's payment setup.
 * Used by the PaymentReadinessPage and the setup/onboarding widget.
 */
class PaymentReadinessService
{
    public function __construct(
        private readonly PaymentManager $manager,
        private readonly PaymentGatewayRecommendationService $recommendations,
    ) {
    }

    /**
     * @return array{
     *   has_gateway: bool,
     *   active_gateways: int,
     *   connected_gateways: int,
     *   supports_international: bool,
     *   supports_apple_pay: bool,
     *   supports_google_pay: bool,
     *   supports_card: bool,
     *   supports_wallets: bool,
     *   target_countries: string[],
     *   covered_countries: string[],
     *   uncovered_countries: string[],
     *   supported_currencies: string[],
     *   checks: array<int, array{id:string, label:string, passed:bool, detail:string}>,
     *   score: int,
     *   max_score: int,
     *   recommendations: array,
     * }
     */
    public function report(Tenant $tenant): array
    {
        $gateways = PaymentGateway::query()
            ->where('is_active', true)
            ->where('type', 'orders')
            ->get();

        $activeCount    = $gateways->count();
        $connectedCount = $gateways->where('connection_status', 'connected')->count();

        $allMethods = [];
        $allCurrencies = [];
        $allCustomerCountries = [];

        foreach ($gateways as $gw) {
            $meta = $this->manager->meta($gw->code);
            $allMethods = array_merge($allMethods, $meta['payment_methods'] ?? []);
            $allCurrencies = array_merge($allCurrencies, $meta['currencies'] ?? []);
            $allCustomerCountries = array_merge($allCustomerCountries, $meta['customer_countries'] ?? []);
        }

        $allMethods = array_values(array_unique($allMethods));
        $allCurrencies = array_values(array_unique($allCurrencies));
        $allCustomerCountries = array_map('strtoupper', array_values(array_unique($allCustomerCountries)));

        $targetCountries = $tenant->tenantCountries()
            ->where('is_active', true)
            ->with('country:id,iso2,name')
            ->get()
            ->map(fn ($tc) => strtoupper($tc->country?->iso2 ?? ''))
            ->filter()
            ->values()
            ->all();

        $coveredCountries = array_values(array_intersect($targetCountries, $allCustomerCountries));
        $uncoveredCountries = array_values(array_diff($targetCountries, $allCustomerCountries));

        $hasApplePay = in_array('apple_pay', $allMethods);
        $hasGooglePay = in_array('google_pay', $allMethods);
        $hasCard = in_array('card', $allMethods);
        $hasWallet = !empty(array_intersect(['wallet', 'wallets', 'ewallet'], $allMethods));
        $isInternational = count($allCustomerCountries) > 3;

        $checks = [
            [
                'id' => 'has_gateway',
                'label' => 'At least one payment gateway is active',
                'passed' => $activeCount > 0,
                'detail' => $activeCount > 0
                    ? "{$activeCount} gateway(s) active"
                    : 'Go to Payment Gateways → activate a gateway',
            ],
            [
                'id' => 'connection',
                'label' => 'Gateway credentials verified',
                'passed' => $connectedCount > 0,
                'detail' => $connectedCount > 0
                    ? "{$connectedCount}/{$activeCount} gateway(s) connected"
                    : 'Use the Recheck button to verify credentials',
            ],
            [
                'id' => 'card',
                'label' => 'Accepts card payments (Visa / Mastercard)',
                'passed' => $hasCard,
                'detail' => $hasCard ? 'Card payments supported' : 'Add a gateway that supports card payments',
            ],
            [
                'id' => 'apple_pay',
                'label' => 'Supports Apple Pay',
                'passed' => $hasApplePay,
                'detail' => $hasApplePay
                    ? 'Apple Pay available'
                    : 'Stripe, Moyasar, Tap, HyperPay, or Checkout.com support Apple Pay',
            ],
            [
                'id' => 'google_pay',
                'label' => 'Supports Google Pay',
                'passed' => $hasGooglePay,
                'detail' => $hasGooglePay
                    ? 'Google Pay available'
                    : 'Stripe, Mollie, Adyen, or Checkout.com support Google Pay',
            ],
            [
                'id' => 'international',
                'label' => 'Supports international customer payments',
                'passed' => $isInternational,
                'detail' => $isInternational
                    ? 'Customers from ' . count($allCustomerCountries) . ' countries can pay'
                    : 'Consider adding Stripe, PayPal, or Adyen for global reach',
            ],
            [
                'id' => 'target_coverage',
                'label' => 'All target countries are covered',
                'passed' => !empty($targetCountries) && empty($uncoveredCountries),
                'detail' => empty($targetCountries)
                    ? 'Set your target countries in Store → Countries'
                    : (empty($uncoveredCountries)
                        ? 'All ' . count($targetCountries) . ' target countries covered'
                        : count($uncoveredCountries) . ' uncovered: ' . implode(', ', array_slice($uncoveredCountries, 0, 5))),
            ],
        ];

        $passed = count(array_filter($checks, fn ($c) => $c['passed']));
        $maxScore = count($checks);

        return [
            'has_gateway' => $activeCount > 0,
            'active_gateways' => $activeCount,
            'connected_gateways' => $connectedCount,
            'supports_international' => $isInternational,
            'supports_apple_pay' => $hasApplePay,
            'supports_google_pay' => $hasGooglePay,
            'supports_card' => $hasCard,
            'supports_wallets' => $hasWallet,
            'target_countries' => $targetCountries,
            'covered_countries' => $coveredCountries,
            'uncovered_countries' => $uncoveredCountries,
            'supported_currencies' => $allCurrencies,
            'checks' => $checks,
            'score' => $passed,
            'max_score' => $maxScore,
            'recommendations' => $this->recommendations->recommend($tenant, 3),
        ];
    }
}
