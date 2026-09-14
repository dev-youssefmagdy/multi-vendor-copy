@props(['stats' => [], 'columns' => null])

@php
    $columns ??= count($stats) > 3 ? 4 : 3;
    $gridClass = $columns >= 4 ? 'g-stats4' : 'g-stats3';
@endphp

<div class="{{ $gridClass }} section-gap">
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
