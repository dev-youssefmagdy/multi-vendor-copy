<?php

namespace Tests\Unit;

use App\Services\Tenant\TenantPricingService;
use PHPUnit\Framework\TestCase;

class TenantPricingServiceTest extends TestCase
{
    private const COSTS = ['52' => 2.65, '150' => 14.4, '184' => 2.4];

    public function test_it_builds_every_country_key_with_profit_rows_and_total_profit(): void
    {
        $result = (new TenantPricingService(['52', '150', '184']))->forCatalog(0.84, self::COSTS, null, 25.0);

        $this->assertEqualsCanonicalizing(['default', '52', '150', '184'], array_keys($result['prices']));
        $this->assertSame(1.05, $result['prices']['default']);
        $this->assertSame(3.7, $result['prices']['52']);
        $this->assertSame(15.45, $result['prices']['150']);
        $this->assertSame(1.05, $result['default']);
        $this->assertSame(['profit_type' => 'percentage', 'profit_value' => 25.0, 'total_profit' => 0.21], $result['profit']['52']);
    }

    public function test_it_keeps_the_vendor_profit_config_and_seeds_missing_countries(): void
    {
        $existing = ['default' => ['profit_type' => 'fixed', 'profit_value' => 1.0, 'total_profit' => 1.0]];

        $result = (new TenantPricingService(['52']))->forCatalog(2.0, ['52' => 1.0], $existing, 10.0);

        $this->assertSame('fixed', $result['profit']['default']['profit_type']);
        $this->assertSame(3.0, $result['prices']['default']);
        $this->assertSame('percentage', $result['profit']['52']['profit_type']);
        $this->assertSame(3.2, $result['prices']['52']);
    }

    public function test_a_product_without_shipping_costs_still_gets_all_active_country_rows(): void
    {
        $result = (new TenantPricingService(['52', '184']))->forCatalog(10.0, null, null, 20.0);

        $this->assertEqualsCanonicalizing(['default', '52', '184'], array_keys($result['prices']));
        $this->assertSame(12.0, $result['prices']['52']);
    }

    public function test_default_override_pins_a_fixed_profit_on_the_default_row_only(): void
    {
        $result = (new TenantPricingService(['52']))->forCatalog(0.84, ['52' => 2.65], null, 25.0, 2.0);

        $this->assertSame(2.0, $result['prices']['default']);
        $this->assertSame(['profit_type' => 'fixed', 'profit_value' => 1.16, 'total_profit' => 1.16], $result['profit']['default']);
        $this->assertSame(3.7, $result['prices']['52']);
        $this->assertSame('percentage', $result['profit']['52']['profit_type']);
    }

    public function test_override_below_cost_never_produces_a_negative_profit(): void
    {
        $result = (new TenantPricingService([]))->forCatalog(5.0, null, null, 10.0, 3.0);

        $this->assertSame(5.0, $result['prices']['default']);
        $this->assertSame(0.0, $result['profit']['default']['total_profit']);
    }

    public function test_central_base_price_prefers_a_positive_sale_price(): void
    {
        $service = new TenantPricingService([]);

        $this->assertSame(0.84, $service->centralBasePrice((object) ['sale_price' => '0.84', 'base_price' => '1.00']));
        $this->assertSame(1.0, $service->centralBasePrice((object) ['sale_price' => '0.00', 'base_price' => '1.00']));
        $this->assertSame(1.0, $service->centralBasePrice((object) ['sale_price' => null, 'base_price' => '1.00']));
    }
}
