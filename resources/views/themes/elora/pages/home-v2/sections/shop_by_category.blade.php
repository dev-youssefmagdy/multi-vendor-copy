    @if ($categories->isNotEmpty())
    @php
      $shopByCatFallbackImgs = ['shop-accessories.png', 'shop-fashion.png', 'shop-electronics.png', 'shop-presents.png', 'shop-decor.png', 'shop-furniture.png'];
      $shopByCatData = $categories->take(6)->values()->map(function ($cat, $i) use ($shopByCatFallbackImgs) {
          $catName = $cat->translationValue('name') ?? $cat->name;
          return [
              'url' => route('tenant.storefront.category', $cat->slug),
              'name' => \Illuminate\Support\Str::limit($catName, 20),
              'image' => $cat->thumb_url ?? asset('elora-1/assets/images/' . $shopByCatFallbackImgs[$i % count($shopByCatFallbackImgs)]),
          ];
      });
      // Slots, left to right / top to bottom: [0]=large tile of block 1,
      // [1][2]=stacked pair of block 1, [3][4]=stacked pair of block 2,
      // [5]=large tile of block 2 — matching the reference design's
      // mirrored large-tile placement (left in the first block, right in
      // the second), not a plain repeating grid.
      $overlayGradient = 'linear-gradient(0deg, rgba(0, 0, 0, 0.79) 0%, rgba(138, 56, 245, 0.09) 100%)';
    @endphp
    <section class="flex flex-col items-center px-[16px] lg:px-0 py-[32px] gap-[16px] lg:gap-[24px] w-full max-w-[375px] lg:max-w-[1328px] mx-auto bg-white box-border" style="font-family: 'Outfit', sans-serif">
      <div class="flex flex-col justify-center items-center w-[265px] lg:w-auto h-[48px]">
        <h2 class="m-0 font-semibold text-[32px] leading-[150%] tracking-[0.5px] lg:whitespace-nowrap" style="color: var(--color-text-primary)">
          Shop by Category
        </h2>
      </div>

      <div class="flex flex-col lg:flex-row items-start gap-[12px] w-[343px] lg:w-[1328px] h-[486px] lg:h-[330px]">
        {{-- Block 1: large tile (left) + stacked pair (right) --}}
        <div class="flex flex-row items-center gap-[12px] w-[343px] lg:w-[658px] h-[237px] lg:h-[330px] lg:flex-1">
          <a
            href="{{ $shopByCatData[0]['url'] }}"
            class="flex flex-row justify-center items-end py-[16px] lg:py-[16px] gap-[8px] w-[165.5px] lg:w-[323px] h-[237px] lg:h-[326px] rounded-[8px] bg-cover bg-center flex-1 no-underline box-border"
            style="background-image: {{ $overlayGradient }}, url('{{ $shopByCatData[0]['image'] }}')"
          >
            <span class="font-medium text-[16px] lg:text-[24px] leading-[150%] tracking-[0.5px] text-white capitalize">{{ $shopByCatData[0]['name'] }}</span>
          </a>
          <div class="flex flex-col items-start gap-[12px] w-[165.5px] lg:w-[323px] h-[236px] lg:h-[330px] flex-1">
            @foreach ([1, 2] as $i)
              <a
                href="{{ $shopByCatData[$i]['url'] }}"
                class="flex flex-row justify-center items-center pt-[85px] lg:pt-[85px] pr-[44px] lg:pr-[44px] pb-[3px] lg:pb-[3px] pl-[43px] lg:pl-[43px] gap-[8px] w-full h-[112px] lg:h-[157px] rounded-[8px] bg-cover bg-center self-stretch no-underline box-border shrink-0 lg:flex-1"
                style="background-image: {{ $overlayGradient }}, url('{{ $shopByCatData[$i]['image'] }}')"
              >
                <span class="font-medium text-[16px] lg:text-[24px] leading-[150%] tracking-[0.5px] text-white capitalize">{{ $shopByCatData[$i]['name'] }}</span>
              </a>
            @endforeach
          </div>
        </div>

        {{-- Block 2: stacked pair (left) + large tile (right) --}}
        <div class="flex flex-row items-center gap-[12px] w-[343px] lg:w-[658px] h-[237px] lg:h-[330px] lg:flex-1">
          <div class="flex flex-col items-start gap-[12px] w-[165.5px] lg:w-[323px] h-[236px] lg:h-[330px] flex-1">
            @foreach ([3, 4] as $i)
              <a
                href="{{ $shopByCatData[$i]['url'] }}"
                class="flex flex-row justify-center items-center pt-[85px] lg:pt-[85px] pr-[44px] lg:pr-[44px] pb-[3px] lg:pb-[3px] pl-[43px] lg:pl-[43px] gap-[8px] w-full h-[112px] lg:h-[157px] rounded-[8px] bg-cover bg-center self-stretch no-underline box-border shrink-0 lg:flex-1"
                style="background-image: {{ $overlayGradient }}, url('{{ $shopByCatData[$i]['image'] }}')"
              >
                <span class="font-medium text-[16px] lg:text-[24px] leading-[150%] tracking-[0.5px] text-white capitalize">{{ $shopByCatData[$i]['name'] }}</span>
              </a>
            @endforeach
          </div>
          <a
            href="{{ $shopByCatData[5]['url'] }}"
            class="flex flex-row justify-center items-end py-[16px] gap-[8px] w-[165.5px] lg:w-[323px] h-[237px] lg:h-[326px] rounded-[8px] bg-cover bg-center flex-1 no-underline box-border"
            style="background-image: {{ $overlayGradient }}, url('{{ $shopByCatData[5]['image'] }}')"
          >
            <span class="font-medium text-[16px] lg:text-[24px] leading-[150%] tracking-[0.5px] text-white capitalize">{{ $shopByCatData[5]['name'] }}</span>
          </a>
        </div>
      </div>

      <a
        href="{{ route('tenant.storefront.category') }}"
        class="group box-border flex flex-row justify-center items-center p-[8px] gap-[8px] w-[121px] h-[38px] rounded-[34px] border border-solid bg-transparent cursor-pointer transition-all duration-200 ease-in-out hover:bg-[var(--color-accent-purple)]"
        style="border-color: var(--color-accent-purple)"
      >
        <span class="font-medium text-[14px] leading-[25px] tracking-[0.5px] text-[var(--color-accent-purple)] group-hover:text-white">Explore all</span>
      </a>
    </section>
    @endif
