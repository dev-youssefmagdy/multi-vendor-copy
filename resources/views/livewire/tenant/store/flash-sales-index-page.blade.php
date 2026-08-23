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
        <a href="{{ route('tenant.store.flash-sales') }}" class="card" style="display:block;text-decoration:none;">
            <h3 class="panel-title">Default</h3>
            <p class="panel-copy">Shown to visitors when no country-specific flash sales exist.</p>
            <span class="badge mt-2">{{ $defaultCount }} {{ Str::plural('flash sale', $defaultCount) }}</span>
        </a>

        @foreach ($countries as $country)
            <a href="{{ route('tenant.store.flash-sales', ['countryId' => $country->id]) }}" class="card" style="display:block;text-decoration:none;">
                <h3 class="panel-title">{{ $country->flag_emoji }} {{ $country->name }}</h3>
                <p class="panel-copy">Flash sales shown to visitors from {{ $country->name }}.</p>
                @php($count = (int) ($countryCounts[$country->id] ?? 0))
                <span class="badge mt-2">{{ $count }} {{ Str::plural('flash sale', $count) }}</span>
            </a>
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
