<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Support\CountryScope;

/**
 * Adds the `country_ids` multi-select (a list of tenant country ids, or the
 * single value `all`) to a store form request.
 */
trait ScopesToCountries
{
    protected function countryRules(): array
    {
        return CountryScope::rules(CountryScope::tenantCountries()->pluck('id')->all());
    }

    /** Validated country ids to store; an empty array means "all countries". */
    public function countryIds(): array
    {
        return CountryScope::normalize($this->validated('country_ids'));
    }
}
