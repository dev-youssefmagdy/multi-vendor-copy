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
      <div class="flex flex-row items-center justify-between gap-[8px] h-[23px] lg:h-[40px]">
        <h2 class="font-medium text-[18px] lg:text-[32px] leading-[23px] lg:leading-[40px] text-black">
          Categories
        </h2>
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="font-normal text-[12px] lg:text-[20px] leading-[15px] lg:leading-[25px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >see all</a
        >
      </div>
      <div
        class="flex flex-row items-start gap-[16px] lg:gap-[31.58px] h-[115px] lg:h-[227px]"
      >
        <a
          href="{{ $circleCatData[0]['url'] ?? '#' }}"
          class="bg-white rounded-[9px] lg:rounded-[17.7652px] flex flex-col items-center py-[12px] lg:py-[23.687px] gap-[8px] lg:gap-[15.79px] relative overflow-hidden flex-1 h-full"
        >
          <p
            class="font-semibold text-[12px] lg:text-[23.687px] leading-[150%] tracking-[0.5px] lg:tracking-[0.986957px] text-center px-1 truncate max-w-full relative z-0"
            style="color: var(--color-accent-purple)"
          >
            {{ $circleCatData[0]['name'] ?? 'Women bags' }}
          </p>
          <img
            src="{{ $circleCatData[0]['image'] ?? asset('elora-1/assets/images/cat-bag.png') }}"
            alt="{{ $circleCatData[0]['name'] ?? 'Women bags' }}"
            class="absolute z-[1] w-[71px] h-[77px] left-[18px] top-[43px] lg:w-[140.15px] lg:h-[151.99px] lg:left-[35.53px] lg:top-[84.88px] object-contain"
          />
        </a>
        <div class="flex flex-col gap-[12px] lg:gap-[23.69px] w-[132px] lg:w-[260.56px] h-[114px] lg:h-[225.03px]">
          <a
            href="{{ $circleCatData[1]['url'] ?? '#' }}"
            class="bg-white rounded-[9px] lg:rounded-[17.7652px] flex flex-row items-center justify-center gap-[8px] lg:gap-[15.79px] pl-[8px] lg:pl-[15.7913px] pr-[42px] lg:pr-[82.9044px] pt-[9px] lg:pt-[17.7652px] pb-[7px] lg:pb-[13.8174px] relative overflow-hidden w-full h-[58px] lg:h-[114.49px]"
          >
            <p
              class="font-semibold text-[12px] lg:text-[23.687px] leading-[150%] tracking-[0.5px] lg:tracking-[0.986957px] w-[82px] lg:w-[161.86px] truncate relative z-0"
              style="color: var(--color-accent-purple)"
            >
              {{ $circleCatData[1]['name'] ?? 'Home Accessories' }}
            </p>
            <img
              src="{{ $circleCatData[1]['image'] ?? asset('elora-1/assets/images/cat-lamp.png') }}"
              alt="{{ $circleCatData[1]['name'] ?? 'Home Accessories' }}"
              class="absolute z-[1] w-[40px] h-[65px] left-[88px] top-[-11px] lg:w-[79.19px] lg:h-[127.92px] lg:left-[173.7px] lg:top-[-21.71px] object-contain"
            />
          </a>
          <a
            href="{{ $circleCatData[2]['url'] ?? '#' }}"
            class="bg-white rounded-[9px] lg:rounded-[17.7652px] flex flex-row items-center justify-center gap-[8px] lg:gap-[15.79px] pl-[8px] lg:pl-[15.7913px] pr-[42px] lg:pr-[82.9044px] pt-[9px] lg:pt-[17.7652px] pb-[7px] lg:pb-[13.8174px] relative overflow-hidden w-full h-[44px] lg:h-[86.85px]"
          >
            <p
              class="font-semibold text-[12px] lg:text-[23.687px] leading-[150%] tracking-[0.5px] lg:tracking-[0.986957px] w-[82px] lg:w-[161.86px] truncate relative z-0"
              style="color: var(--color-accent-purple)"
            >
              {{ $circleCatData[2]['name'] ?? 'Electronics' }}
            </p>
            <img
              src="{{ $circleCatData[2]['image'] ?? asset('elora-1/assets/images/cat-laptop.png') }}"
              alt="{{ $circleCatData[2]['name'] ?? 'Electronics' }}"
              class="absolute z-[1] w-[53px] h-[47px] left-[79px] top-[-3px] lg:w-[104.62px] lg:h-[92.77px] lg:left-[155.94px] lg:top-[-5.92px] object-contain"
            />
          </a>
        </div>
        <a
          href="{{ $circleCatData[3]['url'] ?? '#' }}"
          class="bg-white rounded-[9px] lg:rounded-[17.7652px] flex flex-col items-center py-[12px] lg:py-[23.687px] gap-[8px] lg:gap-[15.79px] relative overflow-hidden flex-1 h-full"
        >
          <p
            class="font-semibold text-[12px] lg:text-[23.687px] leading-[150%] tracking-[0.5px] lg:tracking-[0.986957px] truncate max-w-full relative z-0"
            style="color: var(--color-accent-purple)"
          >
            {{ $circleCatData[3]['name'] ?? 'Gaming' }}
          </p>
          <img
            src="{{ $circleCatData[3]['image'] ?? asset('elora-1/assets/images/cat-controller.png') }}"
            alt="{{ $circleCatData[3]['name'] ?? 'Gaming' }}"
            class="absolute z-[1] w-[74.88px] h-[69.18px] left-[12px] top-[43px] lg:w-[147.81px] lg:h-[136.55px] lg:left-[23.69px] lg:top-[84.88px] object-contain [transform:matrix(-0.96,-0.26,-0.26,0.96,0,0)]"
          />
        </a>
      </div>
    </section>
    @endif
