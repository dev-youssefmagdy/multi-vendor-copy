<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
    </div>

    <div class="fu d1 section-gap" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
        <div class="card" style="display:block;">
            <a href="{{ route('tenant.store.flash-sales') }}" style="display:block;text-decoration:none;">
                <h3 class="panel-title">Default</h3>
                <p class="panel-copy">Shown to visitors when no country-specific flash sales exist.</p>
                <span class="badge mt-2">{{ $defaultCount }} {{ Str::plural('flash sale', $defaultCount) }}</span>
            </a>
            @if($storefrontBase ?? null)
                <a href="{{ $storefrontBase }}" target="_blank" rel="noopener noreferrer"
                    style="font-size:11px;color:var(--t3);text-decoration:none;display:inline-block;margin-top:8px">Preview</a>
            @endif
        </div>

        @foreach ($countries as $country)
            <div class="card" style="display:block;">
                <a href="{{ route('tenant.store.flash-sales', ['countryId' => $country->id]) }}" style="display:block;text-decoration:none;">
                    <h3 class="panel-title">{{ $country->flag_emoji }} {{ $country->name }}</h3>
                    <p class="panel-copy">Flash sales shown to visitors from {{ $country->name }}.</p>
                    @php($count = (int) ($countryCounts[$country->id] ?? 0))
                    <span class="badge mt-2">{{ $count }} {{ Str::plural('flash sale', $count) }}</span>
                </a>
                @if($storefrontBase ?? null)
                    <a href="{{ $storefrontBase }}?country={{ $country->iso2 }}" target="_blank" rel="noopener noreferrer"
                        style="font-size:11px;color:var(--t3);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-top:8px">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        Preview
                    </a>
                @endif
            </div>
        @endforeach
    </div>

    @if ($countries->isEmpty())
        <div class="card section-gap">
            <div class="empty-state">
                <h3 class="panel-title">No target countries yet</h3>
                <p class="empty-state-copy">Add target countries during onboarding to manage per-country flash sales. The Default card above still applies everywhere.</p>
            </div>
        </div>
    @endif
</main>
