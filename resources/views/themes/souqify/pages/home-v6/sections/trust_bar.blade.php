<!-- ============ FEATURED STRIP ============ -->
<section class="overflow-x-auto no-scrollbar lg:hidden" style="background:var(--color-brand-pink)">
    <div class="flex items-center gap-[19px] px-[18px] py-[4px] w-max">
        <div class="flex items-center gap-[6px] shrink-0 pr-[19px] border-r border-white">
            <img src="{{ asset('souqify-5/assets/icons/feature-truck.svg') }}" alt="" class="size-[19px]" />
            <span class="text-[9px] text-white tracking-[0.4px] whitespace-nowrap">{{ __('Free Shipping') }}</span>
        </div>
        <div class="flex items-center gap-[6px] shrink-0 pr-[19px] border-r border-white">
            <img src="{{ asset('souqify-5/assets/icons/feature-headphones.svg') }}" alt="" class="size-[19px]" />
            <span class="text-[9px] text-white tracking-[0.4px] whitespace-nowrap">{{ __('Customer Support 24/7') }}</span>
        </div>
        <div class="flex items-center gap-[6px] shrink-0 pr-[19px] border-r border-white">
            <img src="{{ asset('souqify-5/assets/icons/feature-package.svg') }}" alt="" class="size-[19px]" />
            <span class="text-[9px] text-white tracking-[0.4px] whitespace-nowrap">{{ __('Money-Back Guarantee') }}</span>
        </div>
        <div class="flex items-center gap-[6px] shrink-0">
            <img src="{{ asset('souqify-5/assets/icons/feature-secure.svg') }}" alt="" class="size-[19px]" />
            <span class="text-[9px] text-white tracking-[0.4px] whitespace-nowrap">{{ __('100% Secure Payment') }}</span>
        </div>
    </div>
</section>
<section class="hidden lg:block" style="background:var(--color-accent-yellow); box-shadow: 0 13px 33px rgba(0,38,3,0.08)">
    <div class="flex items-center justify-center gap-[52px] px-[56px] py-[13px]">
        <div class="flex items-center gap-[26px]">
            <img src="{{ asset('souqify-5/assets/icons/feature-truck.svg') }}" alt="" class="size-[39px]" />
            <div class="flex flex-col text-[19.6px] tracking-[0.8px] whitespace-nowrap">
                <span class="font-semibold" style="color:var(--color-feature-text)">{{ __('Free Shipping') }}</span>
                <span class="font-normal" style="color:var(--color-feature-subtext)">{{ __('Free shipping on all your order') }}</span>
            </div>
        </div>
        <div class="flex items-center gap-[26px]">
            <img src="{{ asset('souqify-5/assets/icons/feature-headphones.svg') }}" alt="" class="size-[39px]" />
            <div class="flex flex-col text-[19.6px] tracking-[0.8px] whitespace-nowrap">
                <span class="font-semibold" style="color:var(--color-feature-text)">{{ __('Customer Support 24/7') }}</span>
                <span class="font-normal" style="color:var(--color-feature-subtext)">{{ __('Instant access to Support') }}</span>
            </div>
        </div>
        <div class="flex items-center gap-[26px]">
            <img src="{{ asset('souqify-5/assets/icons/feature-secure.svg') }}" alt="" class="size-[39px]" />
            <div class="flex flex-col text-[19.6px] tracking-[0.8px] whitespace-nowrap">
                <span class="font-semibold" style="color:var(--color-feature-text)">{{ __('100% Secure Payment') }}</span>
                <span class="font-normal" style="color:var(--color-feature-subtext)">{{ __('We ensure your money is save') }}</span>
            </div>
        </div>
        <div class="flex items-center gap-[26px]">
            <img src="{{ asset('souqify-5/assets/icons/feature-package.svg') }}" alt="" class="size-[39px]" />
            <div class="flex flex-col text-[19.6px] tracking-[0.8px] whitespace-nowrap">
                <span class="font-semibold" style="color:var(--color-feature-text)">{{ __('Money-Back Guarantee') }}</span>
                <span class="font-normal" style="color:var(--color-feature-subtext)">{{ __('30 Days Money-Back Guarantee') }}</span>
            </div>
        </div>
    </div>
</section>
