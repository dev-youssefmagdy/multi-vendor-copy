<main id="mn">
<style>
.hv-tabs {
    display: flex;
    gap: 4px;
    padding: 4px;
    background: var(--card2);
    border: 1px solid var(--border);
    border-radius: 14px;
    width: fit-content;
    margin-bottom: 24px;
}
.hv-tab {
    padding: 8px 20px;
    border-radius: 10px;
    border: none;
    background: transparent;
    color: var(--t2);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s, color 0.15s, box-shadow 0.15s;
    display: flex;
    align-items: center;
    gap: 8px;
}
.hv-tab:hover:not(.act) {
    background: var(--card);
    color: var(--t1);
}
.hv-tab.act {
    background: var(--card);
    color: var(--t1);
    box-shadow: 0 1px 4px rgba(0,0,0,0.18);
}
.hv-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--primary, #f97316);
    opacity: 0;
    transition: opacity 0.15s;
}
.hv-tab.act .hv-dot {
    opacity: 1;
}
</style>
    {{-- Page header --}}
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

    {{-- Theme switcher --}}
    <div class="hv-tabs fu d1 section-gap">
        @foreach ($themes as $theme)
            <button type="button"
                class="hv-tab {{ $selectedThemeId === $theme->id ? 'act' : '' }}"
                wire:click="selectTheme({{ $theme->id }})">
                <span class="hv-dot"></span>
                {{ $theme->name }}
            </button>
        @endforeach
    </div>

    <div class="card table-card-shell fu d2">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Home Page Variant</h3>
                <p class="panel-copy">Choose which variant of {{ $selectedTheme?->name }}'s home page to show. Set a per-country override to give visitors from that country a different layout or colors.</p>
            </div>
        </div>

        @if ($availableVariants->isEmpty())
            <div class="empty-state">
                <div class="empty-state-title">No variants available</div>
                <p class="empty-state-copy">The platform hasn't published any home page variants for this theme yet.</p>
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Scope</th>
                        <th>Variant</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>
                                <select class="field-control"
                                    wire:change="selectVariant({{ $row['country_id'] ?? 'null' }}, $event.target.value ? parseInt($event.target.value) : null)">
                                    <option value="" @selected(!$row['selected_variant_id'])>Theme default</option>
                                    @foreach ($availableVariants as $variant)
                                        <option value="{{ $variant->id }}" @selected($row['selected_variant_id'] === $variant->id)>
                                            {{ $variant->name }}{{ $variant->is_default ? ' (Default)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    @php
                                        $shown = $row['selected_variant_id']
                                            ? $availableVariants->firstWhere('id', $row['selected_variant_id'])
                                            : $availableVariants->firstWhere('is_default', true);
                                    @endphp
                                    @foreach (array_slice(array_values($shown?->colors ?? []), 0, 5) as $hex)
                                        <span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:{{ $hex }};border:1px solid var(--border2);"></span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</main>
