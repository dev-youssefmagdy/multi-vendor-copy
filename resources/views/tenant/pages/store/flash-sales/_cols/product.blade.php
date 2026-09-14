@props(['flashSale', 'names', 'images'])

@php
    $ids = $flashSale->products->isNotEmpty()
        ? $flashSale->products->pluck('id')
        : collect($flashSale->product)->filter()->pluck('id');
    $labels = $ids->map(fn ($id) => $names[$id] ?? ('Product #' . $id))->values();
@endphp

@if($labels->isEmpty())
    {{ 'No products linked' }}
@else
    <div class="entity-title">{{ $labels->first() }}</div>
    @php($extra = $labels->count() - 1)
    <div class="entity-subtitle">{{ $extra > 0 ? '+' . $extra . ' more product' . ($extra > 1 ? 's' : '') : '1 product' }}</div>
@endif
