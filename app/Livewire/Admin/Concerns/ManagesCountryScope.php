<?php

namespace App\Livewire\Admin\Concerns;

use App\Support\CountryScope;
use Illuminate\Support\Collection;

/**
 * Country targeting for admin list pages (central coupons, flash sales,
 * default banners): the modal's multi-select (`countryIds`, `all` = every
 * country) and the list's country filter (`countryFilter`).
 */
trait ManagesCountryScope
{
    /** @var array<int, string|int> */
    public array $countryIds = [CountryScope::ALL];

    /** '' = no filter, 'all' = available in all countries only, or a country id. */
    public string $countryFilter = '';

    /** Bumped whenever the modal is (re)filled so the Alpine multi-select re-initialises. */
    public int $countryScopeVersion = 0;

    protected ?Collection $centralCountriesCache = null;

    protected function centralCountries(): Collection
    {
        return $this->centralCountriesCache ??= CountryScope::centralCountries();
    }

    /** Options for the modal's multi-select (keys are stringified for the Alpine component). */
    protected function countryOptions(): array
    {
        $options = [];
        foreach (CountryScope::options($this->centralCountries()) as $value => $label) {
            $options[(string) $value] = $label;
        }

        return $options;
    }

    /** Options for the list filter select. */
    protected function countryFilterOptions(): array
    {
        return ['' => 'Any country', CountryScope::ALL => 'Available in all countries']
            + array_slice($this->countryOptions(), 1, null, true);
    }

    protected function countryRules(): array
    {
        return CountryScope::rules($this->centralCountries()->pluck('id')->all(), 'countryIds');
    }

    protected function resetCountryScope(): void
    {
        $this->countryIds = [CountryScope::ALL];
        $this->countryScopeVersion++;
    }

    /** @param int[] $ids */
    protected function fillCountryScope(array $ids): void
    {
        $this->countryIds = array_map('strval', CountryScope::formValue($ids));
        $this->countryScopeVersion++;
    }

    /** @return int[] ids to store; [] = all countries */
    protected function selectedCountryIds(): array
    {
        return CountryScope::normalize($this->countryIds);
    }

    protected function countryBadges(array $ids): string
    {
        return CountryScope::badges($ids, $this->centralCountries()->keyBy('id'));
    }

    public function updatedCountryFilter(): void
    {
        $this->resetPage();
    }
}
