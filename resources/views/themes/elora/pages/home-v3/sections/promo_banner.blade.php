{{-- No standalone promo banner in the elora-3 mockup; built from its hero photography + pink brand accent --}}
<section class="relative px-[16px] lg:px-[56px] py-[24px] lg:py-[32px]">
  <div class="relative rounded-[16px] lg:rounded-[24px] overflow-hidden h-[180px] lg:h-[320px] flex items-center px-[24px] lg:px-[64px]">
    <img
      src="{{ asset('elora-3/assets/images/hero-desktop.jpg') }}"
      alt=""
      class="absolute inset-0 h-full w-full object-cover"
      style="background: var(--color-hero-placeholder)"
    />
    <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.1) 65%, rgba(0,0,0,0) 100%);"></div>
    <div class="relative flex flex-col items-start gap-[8px] lg:gap-[16px]">
      <span
        class="text-white font-black text-[11px] lg:text-[18px] tracking-[1px] lg:tracking-[2px] rounded-[4px] lg:rounded-[8px] px-[8px] lg:px-[16px] py-[2px] lg:py-[4px]"
        style="background: var(--color-brand-pink)"
      >NEW USER EXCLUSIVE</span>
      <h2 class="font-black text-[20px] lg:text-[42px] text-white leading-[1.1]">Sign Up &amp; Save 20% Today</h2>
      <button type="button" class="flex h-[32px] lg:h-[56px] items-center justify-center rounded-full px-[20px] lg:px-[36px] cursor-pointer" style="background: var(--color-brand-pink)">
        <span class="font-bold text-[12px] lg:text-[18px] text-white">Claim Offer</span>
      </button>
    </div>
  </div>
</section>
