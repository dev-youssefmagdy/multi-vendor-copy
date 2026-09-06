<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Target Countries</h1>
                <span class="page-badge">Storefront</span>
                @if($selectedCount > 0)
                    <span class="badge badge-green">{{ $selectedCount }} selected</span>
                @endif
            </div>
            <p class="page-copy">Choose which countries your store targets. Each country gets its own banners, flash sales, and product badges. Use the preview button to see your storefront as a visitor from that country.</p>
        </div>
        <div class="page-actions">
            <x-btn type="button" variant="secondary" wire:click="unselectAll"
                wire:confirm="Remove all countries from targets?">
                Unselect All
            </x-btn>
            <x-btn type="button" wire:click="selectAll">Select All</x-btn>
        </div>
    </div>

    <div class="card fu d2 section-gap" style="padding:14px 20px">
        <div style="display:flex;align-items:center;gap:10px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;color:var(--t3)">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="Search countries..."
                class="form-input" style="flex:1;border:none;background:transparent;padding:0;outline:none;font-size:13px">
            @if($search !== '')
                <button type="button" wire:click="$set('search', '')" style="background:none;border:none;cursor:pointer;color:var(--t3)">×</button>
            @endif
        </div>
    </div>

    <div class="section-gap" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px">
        @forelse($countries as $country)
            @php $isSelected = in_array($country->id, $selectedIds, true); @endphp
            <div class="card fu d2"
                style="padding:16px;display:flex;align-items:center;gap:14px;transition:border-color .15s;border-color:{{ $isSelected ? 'var(--primary)' : '' }}"
                wire:key="country-{{ $country->id }}">

                <div style="font-size:26px;flex-shrink:0">{{ $country->flag_emoji }}</div>
                <div style="flex:1;min-width:0">
                    <div class="entity-title" style="font-size:13px">{{ $country->name }}</div>
                    <div class="entity-subtitle" style="font-size:11px">
                        {{ strtoupper($country->iso2) }}
                        @if($country->is_free)
                            · <span style="color:var(--color-green,#22c55e)">Free</span>
                        @endif
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;flex-shrink:0">
                    <button type="button"
                        wire:click="toggleCountry({{ $country->id }})"
                        wire:loading.attr="disabled"
                        wire:target="toggleCountry({{ $country->id }})"
                        style="display:flex;align-items:center;gap:6px;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s;border:1px solid {{ $isSelected ? 'rgba(239,68,68,.4)' : 'var(--primary)' }};background:{{ $isSelected ? 'rgba(239,68,68,.08)' : 'rgba(var(--primary-rgb,99,102,241),.1)' }};color:{{ $isSelected ? 'var(--color-red,#ef4444)' : 'var(--primary)' }}">
                        @if($isSelected)
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Remove
                        @else
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add
                        @endif
                    </button>

                    @if($storefrontBase)
                        <a href="{{ $storefrontBase }}?country={{ $country->iso2 }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            title="Preview your storefront as a visitor from {{ $country->name }}"
                            style="display:flex;align-items:center;gap:5px;padding:4px 8px;border-radius:6px;font-size:11px;font-weight:500;color:var(--t2);border:1px solid var(--border-color);text-decoration:none;transition:border-color .15s"
                            onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border-color)'">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            Preview
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="card fu d2 section-gap" style="grid-column:1/-1">
                <div class="empty-state">
                    <div class="empty-state-title">No countries found</div>
                    <p class="empty-state-copy">Try a different search term or clear the filter.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if($selectedCount > 0)
        <div class="section-gap" style="padding-top:0">
            <div class="card fu d2" style="padding:14px 20px;display:flex;align-items:center;gap:10px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-green)"><polyline points="20 6 9 17 4 12"/></svg>
                <span class="panel-copy" style="margin:0">
                    <strong>{{ $selectedCount }}</strong> of <strong>{{ $totalCount }}</strong> available countries selected as targets.
                    Country-specific banners, flash sales, and badges are now available for each selected country.
                </span>
            </div>
        </div>
    @endif
</main>
