    @if ($categories->isNotEmpty())
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[34px]"
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
        @php
          $catSlotStyles = [
              ['w' => 'w-[108px] lg:w-[162px]', 'clip' => 'polygon(0 0, 100% 0, 78% 100%, 0 100%)', 'iw' => '70%', 'imgClass' => 'right-[4px] lg:right-[8px] h-[45px] lg:h-[67px]'],
              ['w' => 'w-[108px] lg:w-[162px]', 'clip' => 'polygon(0 0, 100% 0, 74% 100%, 0 100%)', 'iw' => '68%', 'imgClass' => 'right-[2px] lg:right-[4px] h-[48px] lg:h-[78px] -rotate-[15deg]'],
              ['w' => 'w-[128px] lg:w-[196px]', 'clip' => 'polygon(0 0, 100% 0, 76% 100%, 0 100%)', 'iw' => '68%', 'imgClass' => 'right-[4px] lg:right-[6px] h-[42px] lg:h-[68px]'],
              ['w' => 'w-[122px] lg:w-[186px]', 'clip' => 'polygon(0 0, 100% 0, 76% 100%, 0 100%)', 'iw' => '68%', 'imgClass' => 'right-[6px] lg:right-[10px] h-[40px] lg:h-[62px]'],
          ];
        @endphp
        @foreach ($categories->take(4) as $cat)
          @php
            $slot = $catSlotStyles[$loop->index % count($catSlotStyles)];
            $catName = $cat->translationValue('name') ?? $cat->name;
          @endphp
          <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
            class="relative shrink-0 h-[62px] lg:h-[93px] {{ $slot['w'] }} rounded-[18px] lg:rounded-[28px] bg-white overflow-hidden flex items-center p-[5px] lg:p-[7px]"
          >
            <div
              class="absolute inset-y-0 left-0 flex items-center pl-[10px] lg:pl-[16px]"
              style="
                width: {{ $slot['iw'] }};
                background: var(--color-brand-pink);
                clip-path: {{ $slot['clip'] }};
              "
            >
              <p
                class="font-semibold text-white text-[13px] lg:text-[20.7px] tracking-[0.5px] lg:tracking-[0.86px]"
              >
                {{ $catName }}
              </p>
            </div>
            @if ($cat->thumb_url ?? null)
              <img
                src="{{ $cat->thumb_url }}"
                alt="{{ $catName }}"
                class="absolute top-1/2 -translate-y-1/2 w-auto {{ $slot['imgClass'] }}"
              />
            @endif
          </a>
        @endforeach
      </div>
    </section>
    @endif
