<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Store</div>
            <h1 class="D page-title">{{ $title }}</h1>
            <p class="page-copy">{{ $description }}</p>
        </div>
    </div>

    <div class="fu d1 section-gap" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
        <a href="{{ route('admin.store.flash-sales.list') }}" class="card" style="display:block;text-decoration:none;">
            <h3 class="panel-title">Default</h3>
            <p class="panel-copy">Synced to every tenant that has no country-specific flash sales.</p>
            <span class="badge mt-2">{{ $defaultCount }} {{ Str::plural('flash sale', $defaultCount) }}</span>
        </a>

        @foreach ($countries as $country)
            <a href="{{ route('admin.store.flash-sales.list', ['countryId' => $country->id]) }}" class="card" style="display:block;text-decoration:none;">
                <h3 class="panel-title">{{ $country->flag_emoji }} {{ $country->name }}</h3>
                <p class="panel-copy">Synced to tenants targeting {{ $country->name }}.</p>
                @php($count = (int) ($countryCounts[$country->id] ?? 0))
                <span class="badge mt-2">{{ $count }} {{ Str::plural('flash sale', $count) }}</span>
            </a>
        @endforeach
    </div>
</main>
