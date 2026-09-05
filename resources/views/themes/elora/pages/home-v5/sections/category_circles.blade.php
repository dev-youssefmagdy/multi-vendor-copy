    @if ($categories->isNotEmpty())
    @php
      $circleCategories = $categories->take(8)->map(function ($cat) {
          return [
              'name' => \Illuminate\Support\Str::limit($cat->translationValue('name') ?? $cat->name, 15),
              'image' => $cat->thumb_url ?? asset('elora-5/assets/images/cat-placeholder.png'),
              'url' => route('tenant.storefront.category', $cat->slug),
          ];
      });
    @endphp
    <!-- ============ CATEGORIES ============ -->
    <section
      class="py-[24px] lg:py-[40px] flex flex-col gap-[16px] lg:gap-[34px]"
      style="background: var(--color-page-bg)"
    >
      <div class="flex items-center justify-between px-[16px] lg:px-[56px]">
        <h2
          class="font-medium text-[20px] lg:text-[32px]"
          style="color: var(--color-black)"
        >
          {{ __('Categories') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-primary)"
          >{{ __('see all') }}</a
        >
      </div>
      <div class="swiper categories-swiper !ps-[16px] lg:!ps-[56px]" id="categoriesSwiper">
        <div class="swiper-wrapper" id="categoriesWrapper">
          @foreach ($circleCategories as $c)
            <div class="swiper-slide">
              <a href="{{ $c['url'] }}" class="flex flex-col items-center gap-[6px] lg:gap-[6.65px] w-full">
                <div class="category-blob relative flex items-center justify-center w-full h-[64px] lg:h-[104px]">
                  <img src="{{ $c['image'] }}" alt="{{ $c['name'] }}" class="h-[42px] lg:h-[84px] w-auto object-contain" />
                </div>
                <p class="font-semibold text-[12px] lg:text-[26.58px] tracking-[0.3px] lg:tracking-[1.1px] text-center truncate w-full" style="color:var(--color-black)">{{ $c['name'] }}</p>
              </a>
            </div>
          @endforeach
        </div>
      </div>
    </section>
    @endif
