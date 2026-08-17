<main id="mn">
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
    <div class="appearance-tabs fu d1 section-gap">
        @foreach ($themes as $theme)
            <button type="button"
                class="appearance-tab {{ $selectedThemeId === $theme->id ? 'act' : '' }}"
                wire:click="selectTheme({{ $theme->id }})">
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
                                <select class="field-control home-variant-select"
                                    data-country-id="{{ $row['country_id'] ?? '' }}">
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

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                document.querySelectorAll('.home-variant-select').forEach((select) => {
                    select.addEventListener('change', () => {
                        const countryId = select.dataset.countryId ? parseInt(select.dataset.countryId, 10) : null;
                        const variantId = select.value ? parseInt(select.value, 10) : null;
                        @this.call('selectVariant', countryId, variantId);
                    });
                });
            });
        </script>
    @endpush
</main>
