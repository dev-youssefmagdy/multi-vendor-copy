@props(['title', 'subtitle' => null, 'startOpen' => true])

<details class="card overflow-hidden collapse-card" @if ($startOpen) open @endif>
    <summary class="panel-head cursor-pointer select-none mb-0 pb-0 collapse-trigger">
        <div>
            <h3 class="panel-title">{{ $title }}</h3>
            @if($subtitle)<p class="panel-copy">{{ $subtitle }}</p>@endif
        </div>
        <div class="collapse-icon transition-transform duration-300">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </summary>

    <div class="mt-4 pt-4 border-t collapse-content">
        {{ $slot }}
    </div>
</details>
