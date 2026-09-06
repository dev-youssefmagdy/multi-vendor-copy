{{--
    Searchable country select for the Souqify theme.

    Props:
      $wireModel   – dot-notation Livewire property (e.g. "data.modal.country_id")
      $countries   – collection of country objects (id, name, flag_emoji)
      $currentId   – currently selected country id (integer|null)
      $labelClass  – (optional) extra classes for the trigger button
--}}
@php
    $currentId  ??= null;
    $labelClass ??= '';
    $jsonCountries = $countries->map(fn($c) => [
        'id'    => $c->id,
        'name'  => $c->name,
        'flag'  => $c->flag_emoji ?? '',
    ])->values()->toJson();
@endphp

<div
    x-data="{
        open: false,
        search: '',
        selected: null,
        wireModel: '{{ $wireModel }}',
        countries: {{ $jsonCountries }},
        get filtered() {
            if (!this.search.trim()) return this.countries;
            const q = this.search.toLowerCase();
            return this.countries.filter(c => c.name.toLowerCase().includes(q));
        },
        init() {
            const id = {{ $currentId ?? 'null' }};
            if (id) this.selected = this.countries.find(c => c.id === id) ?? null;

            // Re-sync when Livewire updates the property externally (modal open/close)
            this.$el.addEventListener('country-select-reset', () => {
                this.selected = null;
                this.search = '';
            });
        },
        openDropdown() {
            this.open = true;
            this.$nextTick(() => this.$refs.searchInput && this.$refs.searchInput.focus());
        },
        select(country) {
            this.selected = country;
            this.open = false;
            this.search = '';
            $wire.set(this.wireModel, country.id);
        },
        clear() {
            this.selected = null;
            this.open = false;
            this.search = '';
            $wire.set(this.wireModel, null);
        }
    }"
    x-on:click.outside="open = false"
    class="relative"
>
    {{-- Trigger --}}
    <button
        type="button"
        x-on:click="open ? (open = false) : openDropdown()"
        class="w-full h-11 px-3 flex items-center justify-between gap-2 rounded-lg border border-neutral-300 bg-white text-sm text-slate-700 outline-none focus:ring-2 focus:ring-blue-300 transition {{ $labelClass }}"
        :class="open ? 'ring-2 ring-blue-300 border-blue-400' : ''"
    >
        <span class="flex items-center gap-2 truncate">
            <template x-if="selected">
                <span class="flex items-center gap-2 truncate">
                    <span x-text="selected.flag" class="text-base leading-none"></span>
                    <span x-text="selected.name" class="truncate"></span>
                </span>
            </template>
            <template x-if="!selected">
                <span class="text-neutral-400">{{ __('Select country...') }}</span>
            </template>
        </span>
        <span class="flex items-center gap-1 shrink-0">
            <template x-if="selected">
                <button type="button" x-on:click.stop="clear()" class="text-neutral-400 hover:text-neutral-600 p-0.5 rounded">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </template>
            <svg class="w-4 h-4 text-neutral-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </span>
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-1 w-full bg-white border border-neutral-200 rounded-xl shadow-xl overflow-hidden"
        style="display:none"
    >
        {{-- Search --}}
        <div class="p-2 border-b border-neutral-100">
            <div class="flex items-center gap-2 px-2.5 py-1.5 bg-neutral-50 rounded-lg border border-neutral-200 focus-within:ring-2 focus-within:ring-blue-300">
                <svg class="w-3.5 h-3.5 text-neutral-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 111 11a6 6 0 0116 0z"/>
                </svg>
                <input
                    type="text"
                    x-model="search"
                    x-ref="searchInput"
                    x-on:keydown.escape="open = false"
                    placeholder="{{ __('Search country...') }}"
                    class="flex-1 text-sm bg-transparent outline-none text-slate-700 placeholder-neutral-400"
                    autocomplete="off"
                >
            </div>
        </div>

        {{-- List --}}
        <ul class="max-h-52 overflow-y-auto py-1">
            <template x-for="country in filtered" :key="country.id">
                <li>
                    <button
                        type="button"
                        x-on:click="select(country)"
                        class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 transition text-left"
                        :class="selected && selected.id === country.id ? 'bg-blue-50 font-medium text-blue-700' : ''"
                    >
                        <span x-text="country.flag" class="text-base leading-none w-5 text-center"></span>
                        <span x-text="country.name" class="truncate"></span>
                        <template x-if="selected && selected.id === country.id">
                            <svg class="w-3.5 h-3.5 ml-auto text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                    </button>
                </li>
            </template>
            <template x-if="filtered.length === 0">
                <li class="px-4 py-3 text-sm text-neutral-400 text-center">{{ __('No countries found.') }}</li>
            </template>
        </ul>
    </div>
</div>
