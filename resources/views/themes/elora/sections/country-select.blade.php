{{--
Searchable country select for the Elora theme.

Props:
$wireModel – dot-notation Livewire property (e.g. "data.modal.country_id")
$countries – collection of country objects (id, name, flag_emoji)
$currentId – currently selected country id (integer|null)
--}}
@php
    $currentId ??= null;
    $jsonCountries = $countries->map(fn($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'flag' => $c->flag_emoji ?? '',
    ])->values()->toJson();
@endphp

<div x-data="{
        open: false,
        search: '',
        selected: null,
        wireModel: '{{ $wireModel }}',
        countries: {{ $jsonCountries }},
        panelStyle: '',
        get filtered() {
            if (!this.search.trim()) return this.countries;
            const q = this.search.toLowerCase();
            return this.countries.filter(c => c.name.toLowerCase().includes(q));
        },
        init() {
            const id = {{ $currentId ?? 'null' }};
            if (id) this.selected = this.countries.find(c => c.id === id) ?? null;
            this.$el.addEventListener('country-select-reset', () => {
                this.selected = null;
                this.search = '';
            });
            window.addEventListener('scroll', () => this.open && this.position(), true);
            window.addEventListener('resize', () => this.open && this.position());
        },
        position() {
            const r = this.$refs.trigger.getBoundingClientRect();
            const vh = window.innerHeight;
            const spaceBelow = vh - r.bottom - 12;
            const spaceAbove = r.top - 12;
            const maxPanelHeight = 280;
            if (spaceBelow >= Math.min(maxPanelHeight, 200) || spaceBelow >= spaceAbove) {
                const height = Math.max(120, Math.min(maxPanelHeight, spaceBelow));
                this.panelStyle = `position:fixed; top:${r.bottom + 4}px; left:${r.left}px; max-height:${height}px;`;
            } else {
                const height = Math.max(120, Math.min(maxPanelHeight, spaceAbove));
                this.panelStyle = `position:fixed; bottom:${vh - r.top + 4}px; left:${r.left}px; max-height:${height}px;`;
            }
        },
        openDropdown() {
            this.position();
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
    }" x-on:click.outside="open = false" class="relative">
    {{-- Trigger --}}
    <button type="button" x-ref="trigger" x-on:click="open ? (open = false) : openDropdown()"
        class="w-full border border-[#dcdcdc] rounded-[8px] px-4 py-2.5 text-[13px] outline-none transition bg-white flex items-center justify-between gap-2"
        :class="open ? 'border-[#242424]' : ''">
        <span class="flex items-center gap-2 truncate">
            <template x-if="selected">
                <span class="flex items-center gap-2 truncate">
                    <span x-text="selected.flag" class="text-base leading-none"></span>
                    <span x-text="selected.name" class="truncate"></span>
                </span>
            </template>
            <template x-if="!selected">
                <span class="text-[#999]">{{ __('Select Country') }}</span>
            </template>
        </span>
        <span class="flex items-center gap-1 shrink-0">
            <template x-if="selected">
                <button type="button" x-on:click.stop="clear()" class="text-[#999] hover:text-[#242424] p-0.5 rounded">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </template>
            <svg class="w-4 h-4 text-[#999] transition-transform" :class="open ? 'rotate-180' : ''" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </span>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="z-[9999] flex flex-col bg-white border border-[#dcdcdc] rounded-[8px] shadow-lg ring-1 ring-black/5 overflow-hidden"
        :style="panelStyle" style="display:none">
        {{-- Search --}}
        <div class="p-2 border-b border-[#eee] shrink-0">
            <div
                class="flex items-center gap-2 px-2.5 py-1.5 bg-[#f7f7f7] rounded-[6px] border border-[#eee] focus-within:border-[#242424]">
                <svg class="w-3.5 h-3.5 text-[#999] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-4.35-4.35M17 11A6 6 0 111 11a6 6 0 0116 0z" />
                </svg>
                <input type="text" x-model="search" x-ref="searchInput" x-on:keydown.escape="open = false"
                    placeholder="{{ __('Search country...') }}"
                    class="flex-1 text-[13px] bg-transparent outline-none text-[#242424] placeholder-[#999]"
                    autocomplete="off">
            </div>
        </div>

        {{-- List --}}
        <ul class="flex-1 min-h-0 overflow-y-auto py-1">
            <template x-for="country in filtered" :key="country.id">
                <li>
                    <button type="button" x-on:click="select(country)"
                        class="w-full flex items-center gap-2.5 px-4 py-2 text-[13px] text-[#242424] hover:bg-[#f7f7f7] transition text-left"
                        :class="selected && selected.id === country.id ? 'bg-[#f7f7f7] font-medium' : ''">
                        <span x-text="country.flag" class="text-base leading-none w-5 text-center"></span>
                        <span x-text="country.name" class="truncate"></span>
                        <template x-if="selected && selected.id === country.id">
                            <svg class="w-3.5 h-3.5 ml-auto text-[#242424] shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                    </button>
                </li>
            </template>
            <template x-if="filtered.length === 0">
                <li class="px-4 py-3 text-[13px] text-[#999] text-center">{{ __('No countries found.') }}</li>
            </template>
        </ul>
    </div>
</div>