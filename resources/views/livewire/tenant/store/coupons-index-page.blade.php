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
            <a href="{{ route('tenant.store.coupons.list') }}" style="display:block;text-decoration:none;">
                <h3 class="panel-title">Default</h3>
                <p class="panel-copy">Shown to visitors when no country-specific coupons exist.</p>
                <span class="badge mt-2">{{ $defaultCount }} {{ Str::plural('coupon', $defaultCount) }}</span>
            </a>
        </div>

        @foreach ($countries as $country)
            <div class="card" style="display:block;">
                <a href="{{ route('tenant.store.coupons.list', ['countryId' => $country->id]) }}" style="display:block;text-decoration:none;">
                    <h3 class="panel-title">{{ $country->flag_emoji }} {{ $country->name }}</h3>
                    <p class="panel-copy">Coupons for visitors from {{ $country->name }}.</p>
                    @php($count = (int) ($countryCounts[$country->id] ?? 0))
                    <span class="badge mt-2">{{ $count }} {{ Str::plural('coupon', $count) }}</span>
                </a>
            </div>
        @endforeach
    </div>
</main>
