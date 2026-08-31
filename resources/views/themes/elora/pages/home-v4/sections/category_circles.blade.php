    <!-- ============ CATEGORIES ============ -->
    @if ($categories->isNotEmpty())
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[57px] flex flex-col gap-[16px] lg:gap-[34px]"
      style="background: var(--color-bg-main)"
      wire:ignore
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          {{ __('Categories') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-brand-orange-bright)"
          >{{ __('see all') }}</a
        >
      </div>
      <div
        class="grid grid-cols-4 gap-[8px] lg:flex lg:items-start lg:gap-[32px]"
      >
        @foreach ($categories->take(4) as $cat)
        <a
          href="{{ route('tenant.storefront.category', $cat->slug) }}"
          class="flex flex-col items-center gap-[4px] lg:gap-[4px] lg:flex-1"
        >
          <div
            class="relative flex items-center justify-center overflow-hidden w-full h-[64px] lg:h-[72px] rounded-bl-[28px] rounded-tr-[28px] lg:rounded-bl-[62px] lg:rounded-tr-[62px]"
            style="background: var(--color-brand-orange)"
          >
            @if ($cat->thumb_url ?? null)
              <img
                loading="lazy"
                src="{{ $cat->thumb_url }}"
                alt="{{ $cat->translationValue('name') ?? $cat->name }}"
                class="h-full w-full object-cover"
              />
            @else
              <span class="text-3xl">🛍️</span>
            @endif
          </div>
          <p
            class="font-semibold text-[12px] lg:text-[23px] tracking-[0.4px] lg:tracking-[1px] text-black text-center line-clamp-1"
          >
            {{ $cat->translationValue('name') ?? $cat->name }}
          </p>
        </a>
        @endforeach
      </div>
    </section>
    @endif
