{{-- Prev/next arrows overlaid on a product carousel — desktop only. Place inside the .swiper element;
     home.js wires them up. Logical insets + rtl:rotate keep them correct in RTL. --}}
<button type="button" aria-label="{{ __('Previous') }}"
    class="elora-carousel-prev hidden lg:flex absolute top-1/2 -translate-y-1/2 start-2 z-10 w-10 h-10 items-center justify-center rounded-full bg-white shadow-lg hover:bg-gray-50 transition">
    <svg class="w-[10px] h-[14px] rtl:rotate-180" width="7" height="14" viewBox="0 0 7 14" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M5.75 0.75L1.15683 5.68939C0.614389 6.27273 0.614389 7.22727 1.15683 7.81061L5.75 12.75"
            stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
</button>
<button type="button" aria-label="{{ __('Next') }}"
    class="elora-carousel-next hidden lg:flex absolute top-1/2 -translate-y-1/2 end-2 z-10 w-10 h-10 items-center justify-center rounded-full bg-white shadow-lg hover:bg-gray-50 transition">
    <svg class="w-[10px] h-[14px] rtl:rotate-180" width="7" height="14" viewBox="0 0 7 14" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0.75 12.75L5.34317 7.81061C5.88561 7.22727 5.88561 6.27273 5.34317 5.68939L0.75 0.75"
            stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
</button>
