@props(['flashSale', 'names', 'images'])

@php
    $ids = $flashSale->products->isNotEmpty()
        ? $flashSale->products->pluck('id')
        : collect($flashSale->product)->filter()->pluck('id');
    $labels = $ids->map(fn ($id) => $names[$id] ?? (__('Product #:id', ['id' => $id])))->values();
@endphp

@if($labels->isEmpty())
    {{ __('No products linked') }}
@else
    <div class="entity-title">{{ $labels->first() }}</div>
    @php($extra = $labels->count() - 1)
    <div class="entity-subtitle">{{ $extra > 0 ? trans_choice('+:count more product|+:count more products', $extra, ['count' => $extra]) : __('1 product') }}</div>
@endif
