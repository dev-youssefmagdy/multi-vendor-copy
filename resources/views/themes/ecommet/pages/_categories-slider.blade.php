{{--
    Reusable categories slider with left/right arrow navigation.
    Props:
      $allHref   - URL for the "All" / "All categories" pill
      $allLabel  - label for the "All" pill (default: 'All')
      $allActive - bool, whether the "All" pill is the active one
      $items     - iterable of ['href' => string, 'label' => string, 'active' => bool]
--}}
@php
    $allLabel = $allLabel ?? __('All');
@endphp

@if (!empty($items) && collect($items)->isNotEmpty())
    <div class="relative w-full overflow-hidden mb-6">
        <div id="interestsPillsContainer" dir="ltr"
            class="flex items-stretch overflow-x-auto gap-3 pb-4 pl-12 pr-12 scroll-smooth"
            style="scrollbar-width: none;">
            <a href="{{ $allHref }}"
                class="flex items-center justify-center px-6 py-2 border rounded-full text-[13px] font-bold whitespace-nowrap transition {{ !empty($allActive) ? 'border-black bg-[#242424] text-white' : 'border-gray-light bg-white text-gray-darkest hover:border-black' }}">
                {{ $allLabel }}
            </a>
            @foreach ($items as $item)
                <a href="{{ $item['href'] }}"
                    class="flex items-center justify-center px-6 py-2 border rounded-full text-[13px] font-semibold whitespace-nowrap transition {{ !empty($item['active']) ? 'border-black bg-[#242424] text-white' : 'border-gray-light bg-white text-gray-darkest hover:border-black' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        <div id="leftScrollWrapper"
            class="absolute left-0 top-0 bottom-4 flex items-center bg-gradient-to-r from-white via-white to-transparent pr-8 pl-1 pointer-events-none transition-opacity">
            <button id="scrollLeftInterestsBtn"
                class="w-8 h-8 rounded-full bg-white shadow-[0_0_5px_rgba(0,0,0,0.15)] border border-gray-lighter flex items-center justify-center pointer-events-auto hover:bg-gray-50 transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </div>

        <div id="rightScrollWrapper"
            class="absolute right-0 top-0 bottom-4 flex items-center bg-gradient-to-l from-white via-white to-transparent pl-8 pr-1 pointer-events-none transition-opacity">
            <button id="scrollRightInterestsBtn"
                class="w-8 h-8 rounded-full bg-white shadow-[0_0_5px_rgba(0,0,0,0.15)] border border-gray-lighter flex items-center justify-center pointer-events-auto hover:bg-gray-50 transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>
@endif
