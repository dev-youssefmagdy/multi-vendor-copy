    @if ($categories->isNotEmpty())
    <section
      class="mt-[16px] lg:mt-0 px-[16px] lg:px-[56px] py-[16px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[34px]"
      style="background: #f0f0f0"
      wire:ignore
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          {{ __('Categories') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="font-normal text-[14px] lg:text-[24px] tracking-[0.5px]"
          style="color: var(--color-brand-pink)"
          >{{ __('see all') }}</a
        >
      </div>
      <div
        class="categories-row no-scrollbar flex items-center gap-[10px] lg:gap-[14px]"
      >
        @foreach ($categories->take(4) as $cat)
          @php
            $catName = $cat->translationValue('name') ?? $cat->name;
            $catNameDisplay = \Illuminate\Support\Str::limit($catName, 15);
          @endphp
          <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
            class="relative isolate shrink-0 flex items-center h-[62px] lg:h-[93px] w-[140px] lg:w-[215px] rounded-[18px] lg:rounded-[28px] bg-white overflow-hidden p-[5px] lg:p-[7px] gap-[2px] lg:gap-[3.5px]"
          >
            <span
              class="absolute inset-0 z-0"
              style="
                background: var(--color-brand-pink);
                clip-path: polygon(0 0, 82% 0, 58% 100%, 0 100%);
              "
            ></span>
            <p
              class="relative z-[1] flex-1 min-w-0 truncate font-semibold text-white text-[13px] lg:text-[20.7px] tracking-[0.5px] lg:tracking-[0.86px] leading-[150%]"
            >
              {{ $catNameDisplay }}
            </p>
            @if ($cat->thumb_url ?? null)
              <img
                src="{{ $cat->thumb_url }}"
                alt="{{ $catName }}"
                class="relative z-[2] shrink-0 w-[44px] h-[44px] lg:w-[67px] lg:h-[67px] object-contain"
              />
            @endif
          </a>
        @endforeach
      </div>
    </section>
    @endif
