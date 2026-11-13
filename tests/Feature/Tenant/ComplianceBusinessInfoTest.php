<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\City;
use App\Models\Country;
use App\Models\Tenant\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Client review 11/13: on the Compliance Center business section,
 * - an Individual account must not be asked for a Business Name,
 * - Country is required and City optional,
 * - each section's Save button sits at the bottom of its form.
 */
class ComplianceBusinessInfoTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private int $countryId;
    private int $cityId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();
        $this->tenant->run(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());

        $country = Country::query()->firstOrCreate(['iso2' => 'MA'], ['iso3' => 'MAR', 'name' => 'Morocco']);
        $this->countryId = $country->id;
        $this->cityId = City::query()->create(['country_id' => $country->id, 'name' => 'Casablanca'])->id;
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function save(array $fields)
    {
        return $this->actingAsTenantAdmin()->json('post', $this->tenantUrl('/admin/settings/compliance'), array_merge([
            'section' => 'business',
            'accountType' => 'individual',
            'storeName' => 'gogo',
            'countryId' => $this->countryId,
            'phone' => '+212655487566',
            'email' => 'owner@example.com',
        ], $fields));
    }

    private function setting(string $name): ?string
    {
        return $this->tenant->run(fn () => Setting::query()->where('name', $name)->value('value'));
    }

    public function test_individual_saves_without_business_name_or_city(): void
    {
        $this->save([])->assertSuccessful();

        $this->assertSame('gogo', $this->setting('compliance_business_name'), 'individuals trade under their store name');
        $this->assertSame((string) $this->countryId, (string) $this->setting('compliance_country'));
        $this->assertEmpty($this->setting('compliance_city'));
    }

    public function test_company_still_needs_a_business_name(): void
    {
        $this->save(['accountType' => 'company'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('businessName');

        $this->save(['accountType' => 'company', 'businessName' => 'Casa Trading', 'cityId' => $this->cityId])
            ->assertSuccessful();
        $this->assertSame('Casa Trading', $this->setting('compliance_business_name'));
        $this->assertSame((string) $this->cityId, (string) $this->setting('compliance_city'));
    }

    public function test_country_is_required(): void
    {
        $this->save(['countryId' => null])
            ->assertStatus(422)
            ->assertJsonValidationErrors('countryId');
    }

    public function test_page_hides_business_name_for_individuals_and_saves_at_the_bottom(): void
    {
        $this->tenant->run(fn () => \App\Models\Tenant::saveData(tenant('id'), ['account_type' => 'individual']));

        $html = $this->actingAsTenantAdmin()
            ->get($this->tenantUrl('/admin/settings/compliance'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<div data-company-only\s+hidden/', $html);
        $this->assertStringNotContainsString('acct-section-save', $html);
        $this->assertGreaterThanOrEqual(4, substr_count($html, 'acct-section-footer'));
    }
}
