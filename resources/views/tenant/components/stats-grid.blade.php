@props(['stats' => [], 'columns' => null])

{{-- KPI card row; becomes a swipeable carousel on mobile ([data-kpi-carousel]). --}}

@php
    $columns ??= count($stats) > 3 ? 4 : max(count($stats), 1);
@endphp

<div class="ds-kpis" style="--ds-kpi-cols: {{ $columns }}" data-kpi-carousel>
    @foreach($stats as $i => $stat)
        <x-tenant::stat-card
            :label="$stat['label'] ?? null"
            :value="$stat['value'] ?? null"
            :caption="$stat['caption'] ?? null"
            :dot="$stat['dot'] ?? 'dot-cyan'"
            :glow="$stat['glow'] ?? null"
            :href="$stat['href'] ?? null"
            :icon="$stat['icon'] ?? null"
            :trend="$stat['trend'] ?? null"
            :delay="$i + 1"
        />
    @endforeach
</div>
