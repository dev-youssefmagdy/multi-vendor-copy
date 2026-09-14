@php
    $shown = $row['selected_variant_id']
        ? $availableVariants->firstWhere('id', $row['selected_variant_id'])
        : $availableVariants->firstWhere('is_default', true);
@endphp
<div class="flex gap-1">
    @foreach(array_slice(array_values($shown?->colors ?? []), 0, 5) as $hex)
        <span class="hv-swatch" style="background:{{ $hex }}"></span>
    @endforeach
</div>
