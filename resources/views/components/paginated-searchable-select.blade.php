@props([
    // Field label shown above the picker
    'label'        => 'Select',

    // Livewire property name for the search text input
    'searchModel',
    // Current search string value (used to conditionally show the default option)
    'searchValue'  => '',

    // Livewire action names for pagination
    'nextAction',
    'prevAction',

    // LengthAwarePaginator result set
    'results',
    // Current page number (int)
    'currentPage'  => 1,

    // Currently-selected value (compared against each item's valueKey)
    'selectedValue',
    // Livewire property name to $set when the user clicks an option
    'setModel',

    // Keys used to read data from each result item
    'valueKey'     => 'id',
    'labelKey'     => 'name',
    'isoKey'       => null,   // optional secondary monospace tag (e.g. country ISO code)
    'emojiKey'     => null,   // optional emoji/flag field
    'defaultEmoji' => '🏳️',  // fallback when emojiKey value is empty

    // "Global Default" option shown when search is empty
    'showDefault'  => false,
    'defaultValue' => '',
    'defaultLabel' => 'Global Default',
    'defaultIcon'  => '🌐',

    // Validation error key
    'errorKey'     => '',

    'placeholder'  => 'Search…',
])

<div>
    <label class="field-label">{{ $label }}</label>

    {{-- ── Selected display ──────────────────────────────────────────── --}}
    <div class="pss-selected">
        @if ($showDefault && $selectedValue === $defaultValue)
            <span class="badge badge-cyan">{{ $defaultLabel }}</span>
        @elseif ($selectedValue !== '')
            <span class="pss-selected-value">{{ $selectedValue }}</span>
        @else
            <span class="pss-selected-value pss-none">—</span>
        @endif
        <span class="pss-selected-hint">currently selected</span>
    </div>

    {{-- ── Search input ───────────────────────────────────────────────── --}}
    <input
        type="text"
        class="field-control"
        wire:model.live.debounce.300ms="{{ $searchModel }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
    >

    {{-- ── Options list ───────────────────────────────────────────────── --}}
    <div class="pss-list">

        {{-- Global default – hidden while the user is searching --}}
        @if ($showDefault && blank($searchValue))
            <button
                type="button"
                class="pss-option {{ $selectedValue === $defaultValue ? 'is-selected' : '' }}"
                wire:click="$set('{{ $setModel }}', '{{ $defaultValue }}')"
            >
                <span class="pss-emoji">{{ $defaultIcon }}</span>
                <span class="pss-label">{{ $defaultLabel }}</span>
                @if ($isoKey)
                    <span class="pss-iso">—</span>
                @endif
            </button>
        @endif

        @forelse ($results as $item)
            @php $val = data_get($item, $valueKey); @endphp
            <button
                type="button"
                class="pss-option {{ $selectedValue === $val ? 'is-selected' : '' }}"
                wire:click="$set('{{ $setModel }}', '{{ $val }}')"
            >
                @if ($emojiKey)
                    <span class="pss-emoji">{{ data_get($item, $emojiKey) ?: $defaultEmoji }}</span>
                @endif
                <span class="pss-label">{{ data_get($item, $labelKey) }}</span>
                @if ($isoKey)
                    <span class="pss-iso">{{ data_get($item, $isoKey) }}</span>
                @endif
            </button>
        @empty
            <p class="pss-empty">No results match your search.</p>
        @endforelse
    </div>

    {{-- ── Pagination ─────────────────────────────────────────────────── --}}
    @if ($results->lastPage() > 1)
        <div class="pss-pagination">
            <button
                type="button"
                class="pagination-chip pagination-chip-nav"
                wire:click="{{ $prevAction }}"
                @disabled($currentPage <= 1)
            >‹</button>

            <span class="pagination-chip pagination-chip-status">
                {{ $currentPage }} / {{ $results->lastPage() }}
            </span>

            <button
                type="button"
                class="pagination-chip pagination-chip-nav"
                wire:click="{{ $nextAction }}"
                @disabled($currentPage >= $results->lastPage())
            >›</button>
        </div>
    @endif

    {{-- ── Validation error ───────────────────────────────────────────── --}}
    @if ($errorKey)
        @error($errorKey)
            <div class="field-error">{{ $message }}</div>
        @enderror
    @endif
</div>

{{-- ── Styles (injected once per page) ───────────────────────────────── --}}
@once
<style>
    .pss-selected {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    .pss-selected-value {
        font-family: monospace;
        font-size: 13px;
        font-weight: 700;
        color: var(--cyan);
    }

    .pss-selected-value.pss-none {
        color: var(--t3);
        font-weight: 400;
    }

    .pss-selected-hint {
        font-size: 11px;
        color: var(--t3);
    }

    .pss-list {
        margin-top: 8px;
        max-height: 260px;
        overflow-y: auto;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 4px;
        display: flex;
        flex-direction: column;
        gap: 2px;
        background: var(--elevated);
    }

    .pss-option {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 7px 10px;
        border-radius: 6px;
        border: 1px solid transparent;
        background: transparent;
        color: var(--t1);
        font-size: 13px;
        text-align: left;
        cursor: pointer;
        transition: background 0.15s;
    }

    .pss-option:hover {
        background: var(--card2);
    }

    .pss-option.is-selected {
        background: rgba(0, 229, 255, 0.10);
        color: var(--cyan);
        border-color: rgba(0, 229, 255, 0.22);
    }

    .pss-emoji {
        font-size: 16px;
        line-height: 1;
        flex-shrink: 0;
    }

    .pss-label {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pss-iso {
        font-family: monospace;
        font-size: 11px;
        opacity: 0.55;
        flex-shrink: 0;
    }

    .pss-empty {
        padding: 16px;
        text-align: center;
        font-size: 13px;
        color: var(--t3);
    }

    .pss-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 8px;
    }

    [data-theme="dark"] .pss-list {
        background: var(--card);
        border-color: var(--border2);
    }

    [data-theme="dark"] .pss-option {
        color: var(--t1);
    }

    [data-theme="dark"] .pss-option:hover {
        background: var(--card2);
    }
</style>
@endonce
