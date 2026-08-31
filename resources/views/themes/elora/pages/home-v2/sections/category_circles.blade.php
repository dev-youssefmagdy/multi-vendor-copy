    @if ($categories->isNotEmpty())
    @php
      $circleCats = $categories->take(4)->values();
      $circleCatData = $circleCats->map(function ($cat) {
          $catName = $cat->translationValue('name') ?? $cat->name;
          return [
              'url' => route('tenant.storefront.category', $cat->slug),
              'name' => \Illuminate\Support\Str::limit($catName, 15),
              'image' => $cat->thumb_url ?? null,
          ];
      });
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[34px]"
      style="background: var(--color-page-bg)"
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          Categories
        </h2>
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >see all</a
        >
      </div>
      <div
        class="grid grid-cols-3 gap-[10px] lg:gap-[32px] lg:flex lg:items-start"
      >
        <a
          href="{{ $circleCatData[0]['url'] ?? '#' }}"
          class="bg-white rounded-[12px] lg:rounded-[18px] flex flex-col items-center py-[14px] lg:py-[24px] gap-[10px] lg:gap-[16px] relative overflow-hidden lg:flex-1 lg:h-[227px]"
        >
          <p
            class="font-semibold text-[13px] lg:text-[24px] tracking-[0.5px] lg:tracking-[1px] text-center px-1 truncate max-w-full"
            style="color: var(--color-accent-purple)"
          >
            {{ $circleCatData[0]['name'] ?? 'Women bags' }}
          </p>
          <img
            src="{{ $circleCatData[0]['image'] ?? asset('elora-1/assets/images/cat-bag.png') }}"
            alt="{{ $circleCatData[0]['name'] ?? 'Women bags' }}"
            class="w-[55px] lg:w-[140px] h-auto"
          />
        </a>
        <div class="flex flex-col gap-[8px] lg:gap-[24px] lg:w-[261px]">
          <a
            href="{{ $circleCatData[1]['url'] ?? '#' }}"
            class="bg-white rounded-[12px] lg:rounded-[18px] flex items-center gap-[8px] lg:gap-[16px] px-[8px] lg:px-[16px] py-[10px] lg:py-[18px] relative overflow-hidden h-[62px] lg:h-[114px]"
          >
            <p
              class="font-semibold text-[11px] lg:text-[24px] tracking-[0.5px] lg:tracking-[1px] w-[65%] lg:w-[162px] truncate"
              style="color: var(--color-accent-purple)"
            >
              {{ $circleCatData[1]['name'] ?? 'Home Accessories' }}
            </p>
            <img
              src="{{ $circleCatData[1]['image'] ?? asset('elora-1/assets/images/cat-lamp.png') }}"
              alt="{{ $circleCatData[1]['name'] ?? 'Home Accessories' }}"
              class="absolute right-[-10px] lg:right-[-4px] top-[-8px] lg:top-[-22px] w-[46px] lg:w-[79px] h-auto"
            />
          </a>
          <a
            href="{{ $circleCatData[2]['url'] ?? '#' }}"
            class="bg-white rounded-[12px] lg:rounded-[18px] flex items-center gap-[8px] lg:gap-[16px] px-[8px] lg:px-[16px] py-[10px] lg:py-[18px] relative overflow-hidden h-[46px] lg:h-[87px]"
          >
            <p
              class="font-semibold text-[11px] lg:text-[24px] tracking-[0.5px] lg:tracking-[1px] truncate max-w-full"
              style="color: var(--color-accent-purple)"
            >
              {{ $circleCatData[2]['name'] ?? 'Electronics' }}
            </p>
            <img
              src="{{ $circleCatData[2]['image'] ?? asset('elora-1/assets/images/cat-laptop.png') }}"
              alt="{{ $circleCatData[2]['name'] ?? 'Electronics' }}"
              class="absolute right-[0px] lg:right-[-4px] top-[-2px] lg:top-[-6px] w-[56px] lg:w-[105px] h-auto"
            />
          </a>
        </div>
        <a
          href="{{ $circleCatData[3]['url'] ?? '#' }}"
          class="bg-white rounded-[12px] lg:rounded-[18px] flex flex-col items-center py-[14px] lg:py-[24px] gap-[10px] lg:gap-[16px] relative overflow-hidden lg:flex-1 lg:h-[227px]"
        >
          <p
            class="font-semibold text-[13px] lg:text-[24px] tracking-[0.5px] lg:tracking-[1px] truncate max-w-full"
            style="color: var(--color-accent-purple)"
          >
            {{ $circleCatData[3]['name'] ?? 'Gaming' }}
          </p>
          <img
            src="{{ $circleCatData[3]['image'] ?? asset('elora-1/assets/images/cat-controller.png') }}"
            alt="{{ $circleCatData[3]['name'] ?? 'Gaming' }}"
            class="w-[64px] lg:w-[179px] h-auto -rotate-[10deg]"
          />
        </a>
      </div>
    </section>
    @endif
