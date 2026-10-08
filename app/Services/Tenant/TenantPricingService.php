<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\FixedShippingCost;

/**
 * Single formula for every path that writes tenant `price/sell_price`,
 * `default_*` and `profit`. See docs/product-pricing-sync-lifecycle.md.
 */
final class TenantPricingService
{
    /**
     * @param list<string>|null $activeCountryKeys pre-resolved keys (tests / batch jobs); null = query once on demand
     */
    public function __construct(private ?array $activeCountryKeys = null)
    {
    }

    /**
     * @return list<string> country ids that currently have an active shipping rule
     */
    public function activeCountryKeys(): array
    {
        return $this->activeCountryKeys ??= FixedShippingCost::query()
            ->where('is_active', true)
            ->pluck('country_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * @param array<string, mixed>|null $costs central `{country_id: cost}` JSON
     * @return array<string, float> always contains "default" => 0.0
     */
    public function shippingMap(?array $costs): array
    {
        $map = ['default' => 0.0];

        foreach ((array) $costs as $key => $cost) {
            if ($key !== 'default' && is_numeric($cost)) {
                $map[(string) $key] = (float) $cost;
            }
        }

        return $map;
    }

    /**
     * Existing rows win; every canonical key (plus "default") missing a row is
     * seeded with the tenant's global profit percentage.
     *
     * @param array<string, mixed>|null $existing
     * @param list<string> $keys
     * @return array<string, array{profit_type: string, profit_value: float}>
     */
    public function seedProfit(?array $existing, array $keys, float $tenantPct): array
    {
        $rows = [];

        foreach ((array) $existing as $key => $row) {
            if (!is_array($row)) {
                continue;
            }
            $rows[(string) $key] = [
                'profit_type' => ($row['profit_type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage',
                'profit_value' => max(0.0, (float) ($row['profit_value'] ?? 0)),
            ];
        }

        foreach (['default', ...$keys] as $key) {
            $rows[(string) $key] ??= ['profit_type' => 'percentage', 'profit_value' => max(0.0, $tenantPct)];
        }

        return $rows;
    }

    /**
     * @param array<string, array<string, mixed>> $profit
     * @param array<string, float> $shipping
     * @return array{0: array<string, float>, 1: array<string, array{profit_type: string, profit_value: float, total_profit: float}>}
     */
    public function compute(float $base, array $profit, array $shipping): array
    {
        $prices = [];
        $profitJson = [];

        foreach ($profit as $key => $row) {
            $type = ($row['profit_type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage';
            $value = max(0.0, (float) ($row['profit_value'] ?? 0));
            $amount = $type === 'fixed' ? round($value, 2) : round($base * $value / 100, 2);

            $prices[(string) $key] = round($base + $amount + (float) ($shipping[(string) $key] ?? 0), 2);
            $profitJson[(string) $key] = ['profit_type' => $type, 'profit_value' => $value, 'total_profit' => $amount];
        }

        return [$prices, $profitJson];
    }

    /**
     * Vendor typed an explicit default price: pin it as a fixed profit on the
     * "default" row only (shipping for "default" is 0). Other rows are untouched.
     *
     * @param array<string, array<string, mixed>> $profit
     * @return array<string, array<string, mixed>>
     */
    public function withDefaultOverride(float $base, array $profit, float $newDefaultPrice): array
    {
        $profit['default'] = [
            'profit_type' => 'fixed',
            'profit_value' => max(0.0, round($newDefaultPrice - $base, 2)),
        ];

        return $profit;
    }

    /**
     * Full derivation used by catalog sync and the jobs.
     *
     * @param array<string, mixed>|null $costs
     * @param array<string, mixed>|null $existingProfit
     * @return array{prices: array<string, float>, default: float, profit: array<string, array<string, mixed>>}
     */
    public function forCatalog(float $base, ?array $costs, ?array $existingProfit, float $tenantPct, ?float $overrideDefault = null): array
    {
        $shipping = $this->shippingMap($costs);
        $keys = array_values(array_diff(
            array_unique([...$this->activeCountryKeys(), ...array_map('strval', array_keys($shipping))]),
            ['default'],
        ));

        $profit = $this->seedProfit($existingProfit, $keys, $tenantPct);

        if ($overrideDefault !== null) {
            $profit = $this->withDefaultOverride($base, $profit, $overrideDefault);
        }

        [$prices, $profitJson] = $this->compute($base, $profit, $shipping);

        return ['prices' => $prices, 'default' => $prices['default'] ?? 0.0, 'profit' => $profitJson];
    }

    /**
     * Central base price of a product: sale price when positive, else base price.
     */
    public function centralBasePrice(object $central): float
    {
        $sale = (float) ($central->sale_price ?? 0);

        return $sale > 0 ? $sale : (float) ($central->base_price ?? 0);
    }
}
