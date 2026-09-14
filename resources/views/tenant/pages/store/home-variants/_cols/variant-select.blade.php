@php
    $countryValue = $row['country_id'] ?? '';
@endphp
<select class="field-control" data-hv-variant-select data-country-id="{{ $countryValue }}">
    <option value="" @selected(!$row['selected_variant_id'])>Theme default</option>
    @foreach($availableVariants as $variant)
        <option value="{{ $variant->id }}" @selected($row['selected_variant_id'] === $variant->id)>
            {{ $variant->name }}{{ $variant->is_default ? ' (Default)' : '' }}
        </option>
    @endforeach
</select>
