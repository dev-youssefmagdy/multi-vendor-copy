<div class="flex items-center gap-3 overflow-x-auto cat-circles-row flex-nowrap sm:flex-wrap pb-0.5">

    {{-- Flash products --}}
    <button wire:click="toggleSale('flash_sale')"
        type="button"
        class="flex items-center justify-center gap-[7px] px-3 py-2 rounded-2xl text-xs font-medium tracking-[0.5px] transition border whitespace-nowrap shrink-0
              {{ $isFlashActive ? 'bg-[#FF4D00] text-white border-[#FF4D00]' : 'border-[#FFAC88] text-[#FF4D00]' }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            class="shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <span>{{ __('Flash products') }}</span>
    </button>

    {{-- Top-rated products --}}
    <button wire:click="toggleRatings('4')"
        type="button"
        class="flex items-center justify-center gap-[7px] px-3 py-2 rounded-2xl text-xs font-medium tracking-[0.5px] transition border whitespace-nowrap shrink-0
              {{ $isTopRatedActive ? 'bg-[#FF4D00] text-white border-[#FF4D00]' : 'border-[#FFAC88] text-[#FF4D00]' }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            class="shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
        <span>{{ __('Top-rated') }}</span>
    </button>

    {{-- Fast delivery (no specific filter, just clears tab filters) --}}
    <button wire:click="$set('sale', '')"
        type="button"
        class="flex items-center justify-center gap-[7px] px-3 py-2 rounded-2xl text-xs font-medium tracking-[0.5px] transition border whitespace-nowrap shrink-0 border-[#FFAC88] text-[#FF4D00]">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            class="shrink-0">
            <rect x="1" y="3" width="15" height="13" rx="1" stroke-linecap="round" stroke-linejoin="round" />
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M16 8h4l3 5v4h-7V8zM5.5 21a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm13 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
        </svg>
        <span>{{ __('Fast delivery') }}</span>
    </button>

    {{-- Coupon deals --}}
    <button wire:click="toggleSale('on_sale')"
        type="button"
        class="flex items-center justify-center gap-[7px] px-3 py-2 rounded-2xl text-xs font-medium tracking-[0.5px] transition border whitespace-nowrap shrink-0
              {{ $isCouponActive ? 'bg-[#FF4D00] text-white border-[#FF4D00]' : 'border-[#FFAC88] text-[#FF4D00]' }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            class="shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
        </svg>
        <span>{{ __('Coupon deals') }}</span>
    </button>

    {{-- New in --}}
    <button wire:click="toggleProductFlag('featured')"
        type="button"
        class="flex items-center justify-center gap-[7px] px-3 py-2 rounded-2xl text-xs font-medium tracking-[0.5px] transition border whitespace-nowrap shrink-0
              {{ $isNewInActive ? 'bg-[#FF4D00] text-white border-[#FF4D00]' : 'border-[#FFAC88] text-[#FF4D00]' }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            class="shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
        </svg>
        <span>{{ __('New in') }}</span>
    </button>

</div>
