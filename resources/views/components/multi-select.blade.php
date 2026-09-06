{{--
    x-multi-select — Alpine-powered multi-select with search
    Props:
      label       — field label (string)
      options     — associative array ['CODE' => 'Label', …] or collection
      model       — Livewire property name (string), e.g. "countries"  [Livewire mode]
      name        — HTML input name for normal form submission, e.g. "countries" [form mode]
      selected    — currently selected array
      placeholder — search placeholder text
      errorKey    — validation error key
--}}
@props([
    'label'       => 'Select',
    'options'     => [],
    'model'       => '',
    'name'        => '',
    'selected'    => [],
    'placeholder' => 'Search…',
    'errorKey'    => '',
])

@php
    $selectedArr  = is_array($selected) ? $selected : [];
    $optionsJson  = json_encode((object) $options, JSON_UNESCAPED_UNICODE);
    $selectedJson = json_encode(array_values($selectedArr), JSON_UNESCAPED_UNICODE);
    $modelName    = $model;
    $inputName    = $name;
    $isFormMode   = $inputName !== '';
    $uid          = 'msl_' . md5(($model ?: $name) . uniqid());
@endphp

<div
    x-data="{
        open: false,
        search: '',
        selected: {{ $selectedJson }},
        options: {{ $optionsJson }},
        triggerId: '{{ $uid }}_trigger',
        dropId:    '{{ $uid }}_drop',
        get filtered() {
            const q = this.search.toLowerCase();
            return Object.entries(this.options).filter(([code, name]) =>
                !q || name.toLowerCase().includes(q) || code.toLowerCase().includes(q)
            );
        },
        openDrop() {
            this.open = true;
            this.$nextTick(() => this.reposition());
        },
        reposition() {
            const trigger = document.getElementById(this.triggerId);
            const drop    = document.getElementById(this.dropId);
            if (!trigger || !drop) return;
            const rect = trigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            drop.style.width = rect.width + 'px';
            drop.style.left  = rect.left + 'px';
            if (spaceBelow > 200) {
                drop.style.top    = (rect.bottom + 4) + 'px';
                drop.style.bottom = 'auto';
            } else {
                drop.style.top    = 'auto';
                drop.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            }
        },
        closeDrop(e) {
            const drop    = document.getElementById(this.dropId);
            const trigger = document.getElementById(this.triggerId);
            if (drop && drop.contains(e.target)) return;
            if (trigger && trigger.contains(e.target)) return;
            this.open = false;
        },
        toggle(code) {
            const idx = this.selected.indexOf(code);
            if (idx === -1) {
                this.selected = [...this.selected, code];
            } else {
                this.selected = this.selected.filter(c => c !== code);
            }
            @if ($isFormMode)
            this.syncHiddenInputs();
            @else
            $wire.set('{{ $modelName }}', this.selected);
            @endif
        },
        remove(code) {
            this.selected = this.selected.filter(c => c !== code);
            @if ($isFormMode)
            this.syncHiddenInputs();
            @else
            $wire.set('{{ $modelName }}', this.selected);
            @endif
        },
        syncHiddenInputs() {
            const container = document.getElementById('{{ $uid }}_hidden');
            if (!container) return;
            container.innerHTML = '';
            this.selected.forEach(code => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = '{{ $inputName }}[]';
                inp.value = code;
                container.appendChild(inp);
            });
        },
        isSelected(code) { return this.selected.includes(code); },
        labelFor(code)   { return this.options[code] ?? code; },
    }"
    x-init="$watch('open', v => { if (v) { $nextTick(() => reposition()); document.addEventListener('click', closeDrop); } else { document.removeEventListener('click', closeDrop); } })"
    class="msl-wrap"
>
    <label class="field-label">{{ $label }}</label>

    {{-- Selected pills --}}
    <div class="msl-pills" @click="openDrop()">
        <template x-for="code in selected" :key="code">
            <span class="msl-pill">
                <span x-text="labelFor(code)"></span>
                <button type="button" class="msl-pill-remove"
                    @click.stop="remove(code)" aria-label="Remove">×</button>
            </span>
        </template>
        <span x-show="selected.length === 0" class="msl-pill-empty">{{ __('None selected') }}</span>
    </div>

    {{-- Dropdown trigger --}}
    <button type="button" class="field-control msl-trigger" id="{{ $uid }}_trigger"
        @click="open ? open = false : openDrop()">
        <span x-text="selected.length > 0 ? selected.length + ' selected' : '{{ __('Select countries…') }}'"></span>
        <svg class="msl-chevron" :class="{ 'msl-chevron-open': open }" width="14" height="14"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <polyline points="6 9 12 15 18 9" />
        </svg>
    </button>

    {{-- Teleported dropdown — renders in <body>, bypasses any overflow:hidden ancestor --}}
    <template x-teleport="body">
        <div
            id="{{ $uid }}_drop"
            x-show="open"
            x-transition:enter="msl-drop-enter"
            x-transition:enter-start="msl-drop-enter-start"
            x-transition:enter-end="msl-drop-enter-end"
            x-transition:leave="msl-drop-leave"
            x-transition:leave-start="msl-drop-enter-end"
            x-transition:leave-end="msl-drop-enter-start"
            class="msl-dropdown"
            style="position:fixed;z-index:9999;"
        >
            <div class="msl-search-wrap">
                <input type="text" class="msl-search" x-model="search"
                    placeholder="{{ $placeholder }}" autocomplete="off" @click.stop />
            </div>
            <div class="msl-list">
                <template x-if="filtered.length === 0">
                    <p class="msl-empty">{{ __('No results.') }}</p>
                </template>
                <template x-for="[code, name] in filtered" :key="code">
                    <button
                        type="button"
                        class="msl-option"
                        :class="{ 'msl-option-selected': isSelected(code) }"
                        @click.stop="toggle(code)"
                    >
                        <span class="msl-option-check" x-show="isSelected(code)">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </span>
                        <span class="msl-option-check msl-option-check-empty" x-show="!isSelected(code)"></span>
                        <span class="msl-option-label" x-text="name"></span>
                        <span class="msl-option-code" x-text="code"></span>
                    </button>
                </template>
            </div>
        </div>
    </template>

    @if ($isFormMode)
        {{-- Hidden inputs for normal form submission --}}
        <div id="{{ $uid }}_hidden">
            @foreach ($selectedArr as $code)
                <input type="hidden" name="{{ $inputName }}[]" value="{{ $code }}">
            @endforeach
        </div>
    @endif

    @if ($errorKey)
        @error($errorKey)
            <div class="field-error">{{ $message }}</div>
        @enderror
    @endif
</div>

@once
<style>
    .msl-wrap {
        position: relative;
    }
    .msl-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 8px;
        min-height: 24px;
        cursor: pointer;
    }
    .msl-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px 2px 10px;
        background: rgba(0,229,255,.12);
        border: 1px solid rgba(0,229,255,.28);
        border-radius: 999px;
        font-size: 12px;
        color: var(--cyan, #00e5ff);
        white-space: nowrap;
    }
    .msl-pill-remove {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
        padding: 0 1px;
        opacity: .7;
    }
    .msl-pill-remove:hover { opacity: 1; }
    .msl-pill-empty {
        font-size: 12px;
        color: var(--t3, #888);
        font-style: italic;
    }
    .msl-trigger {
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        text-align: left;
    }
    .msl-chevron {
        flex-shrink: 0;
        transition: transform .2s;
        color: var(--t3, #888);
    }
    .msl-chevron-open { transform: rotate(180deg); }

    /* Teleported dropdown — fixed position, positioned via JS */
    .msl-dropdown {
        background: var(--elevated, #fff);
        border: 1px solid var(--border, #e2e8f0);
        border-radius: 10px;
        box-shadow: 0 8px 32px rgba(0,0,0,.18);
        overflow: hidden;
    }
    .msl-drop-enter  { transition: opacity .12s, transform .12s; }
    .msl-drop-enter-start { opacity: 0; transform: translateY(-4px); }
    .msl-drop-enter-end   { opacity: 1; transform: translateY(0); }
    .msl-drop-leave  { transition: opacity .1s, transform .1s; }

    .msl-search-wrap {
        padding: 8px 8px 4px;
        border-bottom: 1px solid var(--border, #e2e8f0);
    }
    .msl-search {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid var(--border, #e2e8f0);
        border-radius: 6px;
        background: var(--card, #fff);
        color: var(--t1, #111);
        font-size: 13px;
        outline: none;
    }
    .msl-search:focus { border-color: var(--cyan, #00e5ff); }
    .msl-list {
        max-height: 260px;
        overflow-y: auto;
        padding: 4px;
        display: flex;
        flex-direction: column;
        gap: 1px;
    }
    .msl-option {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 7px 10px;
        border-radius: 6px;
        border: 1px solid transparent;
        background: transparent;
        color: var(--t1, #111);
        font-size: 13px;
        text-align: left;
        cursor: pointer;
        transition: background .12s;
    }
    .msl-option:hover { background: var(--card2, #f5f5f5); }
    .msl-option-selected {
        background: rgba(0,229,255,.08);
        border-color: rgba(0,229,255,.2);
        color: var(--cyan, #00e5ff);
    }
    .msl-option-check {
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--cyan, #00e5ff);
    }
    .msl-option-check-empty { width: 16px; height: 16px; flex-shrink: 0; }
    .msl-option-label { flex: 1; }
    .msl-option-code {
        font-family: monospace;
        font-size: 11px;
        opacity: .5;
        flex-shrink: 0;
    }
    .msl-empty {
        padding: 14px;
        text-align: center;
        font-size: 13px;
        color: var(--t3, #888);
    }
    [data-theme="dark"] .msl-dropdown {
        background: var(--card, #1a1a1a);
        border-color: var(--border2, #333);
    }
    [data-theme="dark"] .msl-search {
        background: var(--card2, #222);
        border-color: var(--border2, #333);
        color: var(--t1, #eee);
    }
    [data-theme="dark"] .msl-option { color: var(--t1, #eee); }
    [data-theme="dark"] .msl-option:hover { background: var(--card2, #2a2a2a); }
</style>
@endonce
