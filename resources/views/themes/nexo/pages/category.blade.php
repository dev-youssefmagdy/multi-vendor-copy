@section('pageData', 'categories')

@push('head')
    @vite('resources/css/nexo/category.css')
@endpush

@php
$pageBase = $category
    ? route('tenant.storefront.category', $category->slug)
    : route('tenant.storefront.category');
@endphp

<main class="grow bg-white pb-14 w-full" style="min-height:calc(100vh - 99px)">

    {{-- ── MOBILE FILTER BACKDROP ── --}}
    <div id="mobile-filter-backdrop"
        class="fixed inset-0 z-40 bg-black/40 hidden lg:hidden transition-opacity"
        onclick="toggleMobileFilters()"
        aria-hidden="true"></div>

    <div class="flex w-full">

        {{-- ═══════════════════════════════════════════
             LEFT FILTER SIDEBAR
        ═══════════════════════════════════════════ --}}
        <aside id="mobile-filter-drawer"
            class="nexo-filter-section flex flex-col shrink-0 overflow-y-auto w-screen sm:w-[259px] h-screen sm:h-[calc(100vh-108px)] border-e border-e-[#F0F0F0] shadow-xl fixed top-0 lg:sticky sm:top-[108px] p-8 pt-12 pe-6 z-50 bg-white transition-transform -translate-x-full lg:translate-x-0">
            <span class="block lg:hidden absolute top-4 end-4 text-[24px] font-bold cursor-pointer"
                onclick="toggleMobileFilters()"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M13.5228 2.94799C13.6669 2.81352 13.7831 2.65195 13.8648 2.47252C13.9466 2.29309 13.9922 2.0993 13.9991 1.90222C14.006 1.70515 13.974 1.50864 13.905 1.32392C13.836 1.13921 13.7314 0.969902 13.597 0.825668C13.4627 0.681434 13.3012 0.565099 13.1219 0.483305C12.9426 0.401511 12.749 0.355859 12.5521 0.348958C12.3552 0.342056 12.1588 0.37404 11.9743 0.443082C11.7897 0.512123 11.6205 0.616871 11.4764 0.751345L7.08663 4.84735L2.99391 0.452552C2.72007 0.171845 2.34732 0.00955505 1.95542 0.000408585C1.56353 -0.00873789 1.18363 0.135986 0.897005 0.403613C0.610381 0.671239 0.439803 1.04051 0.421773 1.43241C0.403742 1.8243 0.539692 2.2077 0.80053 2.50055L4.89325 6.89385L0.501978 10.9899C0.352769 11.123 0.231643 11.2846 0.145713 11.4652C0.0597828 11.6458 0.0107816 11.8418 0.0015879 12.0416C-0.00760583 12.2414 0.0231927 12.4411 0.0921751 12.6288C0.161158 12.8166 0.266933 12.9887 0.403288 13.1349C0.539643 13.2812 0.703829 13.3987 0.886201 13.4806C1.06857 13.5625 1.26546 13.6071 1.46529 13.6117C1.66511 13.6164 1.86386 13.581 2.04985 13.5078C2.23583 13.4345 2.40532 13.3247 2.54834 13.185L6.9381 9.0905L11.0308 13.4838C11.163 13.6359 11.3244 13.7598 11.5054 13.8481C11.6864 13.9364 11.8833 13.9874 12.0844 13.9979C12.2855 14.0085 12.4867 13.9784 12.676 13.9095C12.8652 13.8406 13.0386 13.7342 13.1859 13.5968C13.3332 13.4593 13.4514 13.2936 13.5333 13.1095C13.6153 12.9254 13.6594 12.7267 13.6629 12.5252C13.6665 12.3236 13.6295 12.1235 13.5541 11.9366C13.4787 11.7497 13.3665 11.5799 13.2242 11.4373L9.13299 7.044L13.5228 2.94799Z"
                        fill="#8F8F8F" />
                </svg>
            </span>

            <div class="flex flex-col gap-0">

                {{-- ── CATEGORY section ── --}}
                <details open class="border-b border-[#F0F0F0] pb-6 mb-6">
                    <summary class="flex flex-row justify-between items-center cursor-pointer select-none mb-4">
                        <span class="font-medium text-[16px] leading-[20px] text-[#0A0A0A]">{{ __('Category') }}</span>
                        <svg class="chevron w-4 h-4 shrink-0" fill="none" stroke="#0A0A0A" viewBox="0 0 24 24"
                            stroke-width="1.33">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="flex flex-col gap-2">
                        @foreach ($allCategories as $rc)
                        @php $isCat = $slug == null ? false : ($rc->id === $category->id); @endphp
                        <a href="{{ route('tenant.storefront.category', $rc->slug) }}"
                            class="flex items-center gap-2 group">
                            <span
                                class="w-4 h-4 rounded-[4px] flex items-center justify-center shrink-0 shadow-sm transition
                                    {{ $isCat ? 'bg-[#333333] border border-[#333333]' : 'bg-[#F3F3F5] border border-[rgba(0,0,0,0.1)]' }}">
                                @if($isCat)
                                <svg width="10" height="8" viewBox="0 0 10 8" fill="none">
                                    <path d="M1 3.5L3.8 6.5L9 1" stroke="white" stroke-width="1.17"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                @endif
                            </span>
                            <span class="text-[{{ $isCat ? '16px' : '14px' }}] tracking-[0.5px] leading-[20px]
                                    {{ $isCat ? 'font-medium text-[#333333]' : 'font-normal text-[#171717]' }}">
                                {{ $rc->name }}
                            </span>
                        </a>
                        @endforeach
                    </div>
                </details>

                {{-- ── PROMOTIONAL ITEMS ── --}}
                <details class="border-b border-[#F0F0F0] py-4" {{ ($sale || $productFlag) ? 'open' : '' }}>
                    <summary class="flex flex-row justify-between items-center cursor-pointer select-none">
                        <span
                            class="font-medium text-[16px] leading-[20px] text-[#0A0A0A]">{{ __('Promotional items') }}</span>
                        <svg class="chevron w-4 h-4 shrink-0" fill="none" stroke="#0A0A0A" viewBox="0 0 24 24"
                            stroke-width="1.33">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="mt-3 flex flex-col gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                {{ $sale === 'on_sale' ? 'checked' : '' }}
                                wire:click="toggleSale('on_sale')"
                                class="w-4 h-4 accent-[#FF4D00] rounded-[4px]">
                            <span class="text-[14px] tracking-[0.5px] text-[#171717]">{{ __('On sale') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                {{ $sale === 'flash_sale' ? 'checked' : '' }}
                                wire:click="toggleSale('flash_sale')"
                                class="w-4 h-4 accent-[#FF4D00] rounded-[4px]">
                            <span class="text-[14px] tracking-[0.5px] text-[#171717]">{{ __('Flash Sale') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                {{ $productFlag === 'featured' ? 'checked' : '' }}
                                wire:click="toggleProductFlag('featured')"
                                class="w-4 h-4 accent-[#FF4D00] rounded-[4px]">
                            <span class="text-[14px] tracking-[0.5px] text-[#171717]">{{ __('Featured') }}</span>
                        </label>
                    </div>
                </details>

                {{-- ── PRICE ── --}}
                <details class="border-b border-[#F0F0F0] py-4" {{ ($min || $max) ? 'open' : '' }}>
                    <summary class="flex flex-row justify-between items-center cursor-pointer select-none">
                        <span class="font-medium text-[16px] leading-[20px] text-[#0A0A0A]">{{ __('Price') }}</span>
                        <svg class="chevron w-4 h-4 shrink-0" fill="none" stroke="#0A0A0A" viewBox="0 0 24 24"
                            stroke-width="1.33">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="mt-3 flex gap-2">
                        <input type="number" wire:model.lazy="min" min="0" step="0.01" value="{{ $min }}"
                            placeholder="{{ __('Min') }}"
                            class="w-full h-[34px] px-3 border border-[#D1D5DC] rounded-[4px] text-[13px] text-[#242424] focus:outline-none focus:border-[#0A0A0A]">
                        <input type="number" wire:model.lazy="max" min="0" step="0.01" value="{{ $max }}"
                            placeholder="{{ __('Max') }}"
                            class="w-full h-[34px] px-3 border border-[#D1D5DC] rounded-[4px] text-[13px] text-[#242424] focus:outline-none focus:border-[#0A0A0A]">
                    </div>
                </details>

                {{-- ── PRODUCT RATING ── --}}
                <details class="border-b border-[#F0F0F0] py-4" {{ $ratings ? 'open' : '' }}>
                    <summary class="flex flex-row justify-between items-center cursor-pointer select-none">
                        <span
                            class="font-medium text-[16px] leading-[20px] text-[#0A0A0A]">{{ __('Product rating') }}</span>
                        <svg class="chevron w-4 h-4 shrink-0" fill="none" stroke="#0A0A0A" viewBox="0 0 24 24"
                            stroke-width="1.33">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="mt-3 flex flex-col gap-2">
                        @foreach([4,3,2,1] as $star)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ratings_sidebar" value="{{ $star }}"
                                {{ $ratings == $star ? 'checked' : '' }}
                                wire:click="toggleRatings('{{ $star }}')"
                                class="w-4 h-4 accent-[#FF4D00]">
                            <span class="flex items-center gap-0.5">
                                @for($i = 1; $i <= 5; $i++) <svg class="w-[10px] h-[10px]"
                                    fill="{{ $i <= $star ? '#FFE100' : '#D5D5D5' }}" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    @endfor
                                    <span class="text-[12px] text-[#ADADAD] ms-1">&amp; up</span>
                            </span>
                        </label>
                        @endforeach
                        @if($ratings)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ratings_sidebar" value="" checked
                                wire:click="$set('ratings', '')"
                                class="w-4 h-4 accent-[#FF4D00]">
                            <span class="text-[13px] text-[#ADADAD]">{{ __('Any rating') }}</span>
                        </label>
                        @endif
                    </div>
                </details>

                {{-- ── AVAILABILITY ── --}}
                <details class="border-b border-[#F0F0F0] py-4" {{ $availability ? 'open' : '' }}>
                    <summary class="flex flex-row justify-between items-center cursor-pointer select-none">
                        <span
                            class="font-medium text-[16px] leading-[20px] text-[#0A0A0A]">{{ __('Availability') }}</span>
                        <svg class="chevron w-4 h-4 shrink-0" fill="none" stroke="#0A0A0A" viewBox="0 0 24 24"
                            stroke-width="1.33">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="mt-3 flex flex-col gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="availability_sidebar" value="in_stock"
                                {{ $availability === 'in_stock' ? 'checked' : '' }}
                                wire:click="$set('availability', 'in_stock')"
                                class="w-4 h-4 accent-[#FF4D00]">
                            <span class="text-[14px] tracking-[0.5px] text-[#171717]">{{ __('In Stock') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="availability_sidebar" value="out_of_stock"
                                {{ $availability === 'out_of_stock' ? 'checked' : '' }}
                                wire:click="$set('availability', 'out_of_stock')"
                                class="w-4 h-4 accent-[#FF4D00]">
                            <span class="text-[14px] tracking-[0.5px] text-[#171717]">{{ __('Out of Stock') }}</span>
                        </label>
                        @if($availability)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="availability_sidebar" value="" checked
                                wire:click="$set('availability', '')"
                                class="w-4 h-4 accent-[#FF4D00]">
                            <span class="text-[13px] text-[#ADADAD]">{{ __('All') }}</span>
                        </label>
                        @endif
                    </div>
                </details>

                {{-- Apply (close drawer on mobile) + Reset --}}
                <div class="pt-4 pb-2 flex flex-col gap-2">
                    <button type="button" onclick="toggleMobileFilters()"
                        class="w-full h-10 rounded-full bg-[#FF4D00] text-white text-[14px] font-medium hover:opacity-90 transition lg:hidden">
                        {{ __('Apply filters') }}
                    </button>
                    <button type="button" wire:click="clearFilters"
                        class="w-full h-10 rounded-full border border-[#F0F0F0] bg-white text-[#242424] text-[14px] font-medium hover:border-[#242424] transition inline-flex items-center justify-center">
                        {{ __('Reset') }}
                    </button>
                </div>

            </div>
        </aside>


        {{-- ═══════════════════════════════════════════
             RIGHT MAIN CONTENT
        ═══════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col gap-6 px-4 sm:px-6 pt-[19px] pb-10" style="min-width:0">
            <!-- ---- search field (mobile) ---- -->
            <div class="sm:hidden">
                <form action="{{ route('tenant.storefront.search') }}" method="GET"
                    data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}">
                    <div class="nexo-search-inner flex items-center bg-[#F6F5F5] rounded-[32px] px-3 py-2 gap-2 h-14 border border-[#D4D4D4]"
                        style="position:relative">
                        <!-- Search icon (mirrored per Figma) -->
                        <a href="{{ route('tenant.home') }}" class="">
                            <svg width="9" height="16" viewBox="0 0 9 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7.66797 0.666992L0.667969 7.66699L7.66797 14.667" stroke="#0A0A0A" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>

                        <input type="text" name="q" value="{{ request('q') }}"
                            placeholder="{{ $keyword ?: ($category?->name ?? __('Search...')) }}" autocomplete="off"
                            class="bg-transparent flex-1 text-base text-[#8F8F8F] placeholder-[#8F8F8F] min-w-0 outline-none font-normal"
                            style="font-family:'Outfit',sans-serif;" />
                    </div>
                </form>
            </div>

            {{-- Breadcrumb --}}
            <div class="hidden sm:flex items-center gap-1.5 text-[12px] text-[#808080] flex-wrap">
                <a href="{{ route('tenant.home') }}" class="hover:text-[#242424] transition">{{ __('Home') }}</a>
                <span>/</span>
                @if ($slug)
                <a href="{{ route('tenant.storefront.category') }}"
                    class="hover:text-[#242424] transition">{{ __('Categories') }}</a>
                <span>/</span>
                @if ($parentCategory)
                <a href="{{ route('tenant.storefront.category', $parentCategory->slug) }}"
                    class="hover:text-[#242424] transition">{{ $parentCategory->name }}</a>
                <span>/</span>
                @endif
                <span class="text-[#242424] font-medium">{{ $category->name }}</span>
                @else
                <span class="text-[#242424] font-medium">{{ __('All Products') }}</span>
                @endif
                @if ($products->total() > 0)
                <span class="text-[#ADADAD]">
                    ({{ $products->total() }} {{ __('products') }})
                </span>
                @endif
            </div>

            {{-- ── TAB PILLS + SORT ROW ── --}}
            {{-- Mobile: tabs on own full-width scrollable row; desktop: inline with sort --}}
            <div class="sm:hidden -mx-4 px-4 overflow-x-auto cat-circles-row -mb-4">
                <x-nexo.category-tabs :category="$category" :keyword="$keyword" :sort="$sort"
                    :availability="$availability" :product-flag="$productFlag" :sale="$sale" :ratings="$ratings"
                    :min="$min" :max="$max" />
            </div>
            <div class="flex items-center justify-between gap-3 flex-wrap">
                {{-- Tab pills (desktop only) --}}
                <div class="hidden sm:flex items-center">
                    <x-nexo.category-tabs :category="$category" :keyword="$keyword" :sort="$sort"
                        :availability="$availability" :product-flag="$productFlag" :sale="$sale" :ratings="$ratings"
                        :min="$min" :max="$max" />
                {{-- end tab pills (desktop) --}}
                </div>

                {{-- Sort dropdown — Livewire reactive, no form needed --}}
                <div class="shrink-0 relative">
                    <select wire:model.live="sort"
                        class="sort-select h-[35px] ps-4 pe-9 border border-[#EEEEEE] rounded-[16px] text-[14px] tracking-[0.5px] text-[#4D4D4D] bg-white focus:outline-none cursor-pointer">
                        <option value="latest">{{ __('Most relevant') }}</option>
                        <option value="old">{{ __('Oldest') }}</option>
                        <option value="ascending">{{ __('Price: low to high') }}</option>
                        <option value="descending">{{ __('Price: high to low') }}</option>
                        <option value="rating">{{ __('Top rated') }}</option>
                    </select>
                    <span class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#FF4D00"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" />
                        </svg>
                    </span>
                </div>

                <div class="lg:hidden flex items-center">
                    <button type="button" onclick="toggleMobileFilters()" aria-controls="mobile-filter-drawer"
                        aria-expanded="false"
                        class="ms-1 w-9 h-9 rounded-full bg-[#242424] text-white flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 640 640" aria-hidden="true">
                            <path
                                d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                        </svg>
                    </button>
                </div>

                {{-- ── CATEGORY CIRCLES ── --}}
                @if ($relatedCategories->isNotEmpty())
                <div style="width: 100% !important;" class="cat-circles-row hidden lg:flex items-start gap-6 overflow-x-auto pb-1">

                    @php
                        $parent = $category;
                        $isCatActive = !$parent;
                    @endphp
                    @if($parent)
                    <a href="{{ route('tenant.storefront.category', $parent?->slug ?? null) }}"
                        class="flex flex-col items-center gap-2 shrink-0 group" style="width:80px; padding-bottom:15px">
                        <div class="flex items-center justify-center overflow-hidden rounded-full transition p-[1px]
                                        {{ $isCatActive ? 'bg-[#fff0e8] border-2 border-[#FF4D00]' : 'bg-[#F3F4F6] border border-[#E5E7EB] group-hover:border-[#FF4D00]' }}"
                            style="width:64px; height:64px;">
                                <svg class="w-7 h-7 text-[#888]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 11H5m14 0a2 2 0 100-4H5a2 2 0 100 4m14 0v6a2 2 0 01-2 2H7a2 2 0 01-2-2v-6" />
                                </svg>
                        </div>
                        <span
                            class="text-[12px] font-medium text-center leading-[15px] tracking-[0.5px] text-[#364153] line-clamp-2"
                            style="max-width:80px">{{ $parent?->name ? __('Back to :Category', ['category' => $parent->name]) : __('All Categories') }}</span>
                    </a>
                    @endif
                    @foreach ($relatedCategories as $rc)
                    @php $isCatActive = $category && $rc->id === $category->id; @endphp
                    <a href="{{ route('tenant.storefront.category', $rc->slug) }}"
                        class="flex flex-col items-center gap-2 shrink-0 group" style="width:80px; padding-bottom:15px">
                        <div class="flex items-center justify-center overflow-hidden rounded-full transition p-[1px]
                                        {{ $isCatActive ? 'bg-[#fff0e8] border-2 border-[#FF4D00]' : 'bg-[#F3F4F6] border border-[#E5E7EB] group-hover:border-[#FF4D00]' }}"
                            style="width:64px; height:64px;">
                            @if ($rc->thumb_url)
                            <img loading="lazy" src="{{ $rc->thumb_url }}" alt="{{ $rc->name }}"
                                class="w-[62px] h-[62px] object-cover rounded-full">
                            @else
                            <svg class="w-7 h-7 text-[#888]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 11H5m14 0a2 2 0 100-4H5a2 2 0 100 4m14 0v6a2 2 0 01-2 2H7a2 2 0 01-2-2v-6" />
                            </svg>
                            @endif
                        </div>
                        <span
                            class="text-[12px] font-medium text-center leading-[15px] tracking-[0.5px] text-[#364153] line-clamp-2"
                            style="max-width:80px">{{ $rc->name }}</span>
                    </a>
                    @endforeach
                </div>
                @endif

                {{-- ── PRODUCTS GRID ── --}}
                @if ($products->isEmpty())
                <div class="w-full rounded-[28px] border border-dashed border-gray-200 bg-[#fafafa] px-6 py-14 text-center">
                    <h2 class="text-[20px] font-semibold text-[#222] mb-2">{{ __('No products matched your filters.') }}
                    </h2>
                    <p class="text-[13px] text-[#777] max-w-[460px] mx-auto mb-5">
                        {{ __('Try widening the price range, clearing a filter, or browsing another category.') }}
                    </p>
                    <button type="button" wire:click="clearFilters"
                        class="inline-flex items-center px-5 py-2.5 rounded-full bg-[#242424] text-white text-[13px] font-semibold hover:bg-main transition-colors">
                        {{ __('Clear filters') }}
                    </button>
                </div>
                @else
                <div
                    class="grid grid-cols-[repeat(auto-fill,minmax(130px,1fr))] sm:grid-cols-[repeat(auto-fill,180px)] md:grid-cols-[repeat(auto-fill,210px)] justify-center lg:justify-start gap-[14px] w-full">
                    @foreach ($products as $product)
                    @include('themes.nexo.pages._product-card', ['product' => $product])
                    @endforeach
                </div>
                {{-- infinite-scroll sentinel --}}
                <div id="nexo-infinite-sentinel"
                    data-slug="{{ $slug }}"
                    data-next-page="2"
                    data-has-more="{{ $products->hasMorePages() ? '1' : '0' }}"
                    data-keyword="{{ $keyword }}"
                    data-sort="{{ $sort }}"
                    data-availability="{{ $availability }}"
                    data-product-flag="{{ $productFlag }}"
                    data-sale="{{ $sale }}"
                    data-ratings="{{ $ratings }}"
                    data-min="{{ $min }}"
                    data-max="{{ $max }}"
                    class="w-full flex justify-center py-8">
                    <span id="nexo-loading-spinner" class="{{ $products->hasMorePages() ? '' : 'hidden' }} flex items-center gap-2 text-sm text-[#888]">
                        <svg class="animate-spin w-4 h-4 text-[#FF4D00]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        {{ __('Loading more…') }}
                    </span>
                </div>
                @endif

            </div>{{-- end controls+content row --}}

        </div>{{-- end right content --}}

    </div>{{-- end flex wrapper --}}
</main>

@push('scripts')
@vite('resources/js/nexo/category.js')
@endpush
