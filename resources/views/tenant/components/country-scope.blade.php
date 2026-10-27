@props([
    'name' => 'country_ids',
    'label' => 'Countries',
    'options' => [],
    'help' => 'Choose "All countries" or pick the countries this applies to.',
])

{{-- Searchable multi-select of countries. "All countries" (value "all") is exclusive:
     it clears the specific countries, and picking a country drops it. --}}
<x-tenant::select2
    :name="$name"
    :label="$label"
    :options="$options"
    :value="['all']"
    :help="$help"
    multiple
    required
    :allow-clear="false"
    :close-on-select="false"
    placeholder="Search countries…"
    data-exclusive-value="all"
    {{ $attributes }}
/>
