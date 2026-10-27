<?php

namespace App\Support;

use App\Models\Country;
use App\Models\TenantCountry;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Shared helpers for the country multi-select used by banners, coupons and
 * flash sales (tenant panel + admin panel). A record's country set is a list
 * of country ids where an empty list means "all countries"; the form
 * represents that as the single sentinel value `all`.
 */
final class CountryScope
{
    public const ALL = 'all';

    /**
     * Countries the current tenant sells in (tenant panel context).
     *
     * @return Collection<int, Country>
     */
    public static function tenantCountries(): Collection
    {
        $ids = TenantCountry::query()
            ->where('tenant_id', tenant()->id)
            ->where('is_active', true)
            ->pluck('country_id');

        return Country::query()->whereIn('id', $ids)->orderBy('name')->get();
    }

    /**
     * Countries available to central (admin panel) records.
     *
     * @return Collection<int, Country>
     */
    public static function centralCountries(): Collection
    {
        return Country::query()->where('is_active_for_tenants', true)->orderBy('name')->get();
    }

    /**
     * Form input → country ids to store. `all` (or nothing) → [] (all countries).
     *
     * @return int[]
     */
    public static function normalize(mixed $input): array
    {
        $values = collect(is_array($input) ? $input : [$input])
            ->filter(fn ($value) => filled($value));

        if ($values->contains(self::ALL)) {
            return [];
        }

        return $values->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    /** Stored country ids → select value(s) (`['all']` when unscoped). */
    public static function formValue(array $countryIds): array
    {
        return $countryIds === [] ? [self::ALL] : array_map('intval', $countryIds);
    }

    /**
     * Validation rules for the `country_ids` field.
     *
     * @param  int[]  $allowedIds
     */
    public static function rules(array $allowedIds, string $field = 'country_ids'): array
    {
        return [
            $field => ['required', 'array', 'min:1'],
            $field.'.*' => [Rule::in(array_merge([self::ALL], array_map('strval', $allowedIds)))],
        ];
    }

    /**
     * Select options: "All countries" first, then each country.
     *
     * @param  Collection<int, \App\Models\Country>  $countries
     */
    public static function options(Collection $countries): array
    {
        $options = [self::ALL => 'All countries'];

        foreach ($countries as $country) {
            $options[$country->id] = trim(($country->flag_emoji ? $country->flag_emoji.' ' : '').$country->name);
        }

        return $options;
    }

    /**
     * Compact badge list for the "Countries" table column (already escaped).
     *
     * @param  int[]  $countryIds
     * @param  Collection<int, \App\Models\Country>  $countriesById
     */
    public static function badges(array $countryIds, Collection $countriesById, int $limit = 3, string $badgeClass = 'badge'): string
    {
        if ($countryIds === []) {
            return '<span class="'.$badgeClass.' badge-cyan">All countries</span>';
        }

        $labels = collect($countryIds)->map(function ($id) use ($countriesById) {
            $country = $countriesById->get($id);

            return $country
                ? trim(($country->flag_emoji ? $country->flag_emoji.' ' : '').$country->name)
                : '#'.$id;
        });

        $html = $labels->take($limit)
            ->map(fn ($label) => '<span class="'.$badgeClass.'">'.e($label).'</span>')
            ->implode(' ');

        $extra = $labels->count() - $limit;
        if ($extra > 0) {
            $html .= ' <span class="'.$badgeClass.'" title="'.e($labels->slice($limit)->implode(', ')).'">+'.$extra.'</span>';
        }

        return '<div style="display:flex;flex-wrap:wrap;gap:4px">'.$html.'</div>';
    }
}
