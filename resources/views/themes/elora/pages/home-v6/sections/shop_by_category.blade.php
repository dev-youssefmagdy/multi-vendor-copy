    <!-- ============ SHOP BY CATEGORY ============ -->
    <section
      class="bg-white w-full max-w-360 mx-auto border-l-[6px] lg:border-l-14 flex flex-col justify-end lg:justify-start items-center px-2 py-4.5 lg:px-14 lg:py-8 gap-2 lg:gap-6 lg:my-[24px]"
      style="border-color: var(--color-accent-green)"
    >
      <div class="w-full flex gap-[8px] items-stretch lg:items-center">
        <div class="flex items-center justify-center w-[48px] lg:w-[66px] shrink-0">
          <p
            class="vertical-heading font-semibold text-[32px] lg:text-[43.9144px] leading-[48px] lg:leading-[66px] tracking-[0.5px] lg:tracking-[0.686163px] whitespace-nowrap"
            style="color: var(--color-accent-green)"
          >
            Shop by Category
          </p>
        </div>
        <div
          class="flex gap-3 lg:grid lg:grid-cols-3 lg:gap-[19.06px] overflow-x-auto lg:overflow-visible no-scrollbar flex-1"
        >
          @php
            $__shopFallbackImgs = ['shop-accessories.png', 'shop-fashion.png', 'shop-electronics.png'];
            $__shopFirstCategory = $categories->first();
            $__shopCategoryPairs = $categories->skip(1)->take(4)->chunk(2);
          @endphp
          @if ($__shopFirstCategory)
            @php $__shopCatName = $__shopFirstCategory->translationValue('name') ?? $__shopFirstCategory->slug; @endphp
            <a
              href="{{ route('tenant.storefront.category', $__shopFirstCategory->slug) }}"
              class="relative shrink-0 lg:shrink w-[130.33px] lg:w-auto h-[237px] lg:h-auto rounded-[8px] lg:rounded-[12.7036px] overflow-hidden flex justify-center items-end no-underline py-4 px-0 lg:py-[25.4072px] lg:px-0 lg:row-span-2"
            >
              <img
                src="{{ $__shopFirstCategory->thumb_url ?? asset('elora-2/assets/images/' . $__shopFallbackImgs[0]) }}"
                alt="{{ $__shopCatName }}"
                class="absolute inset-0 h-full w-full object-cover"
              />
              <div
                class="absolute inset-0"
                style="background: linear-gradient(0deg, rgba(0, 0, 0, 0.79) 0%, rgba(0, 0, 0, 0) 100%);"
              ></div>
              <span class="relative text-white font-medium text-[16px] lg:text-[25.4072px] leading-[24px] lg:leading-[38px] tracking-[0.5px] lg:tracking-[0.793976px]">{{ $__shopCatName }}</span>
            </a>
          @endif
          @foreach ($__shopCategoryPairs as $__shopPair)
            <div class="flex flex-col gap-3 lg:contents shrink-0 w-[130.33px] lg:w-auto h-[237px] lg:h-auto">
              @foreach ($__shopPair as $category)
                @php $__shopCatName = $category->translationValue('name') ?? $category->slug; @endphp
                <a
                  href="{{ route('tenant.storefront.category', $category->slug) }}"
                  class="relative flex-1 lg:aspect-[405.3/177.85] rounded-[8px] lg:rounded-[12.7036px] overflow-hidden flex justify-center items-end no-underline pb-4 lg:pb-4"
                >
                  <img
                    src="{{ $category->thumb_url ?? asset('elora-2/assets/images/' . $__shopFallbackImgs[(1 + $loop->parent->index * 2 + $loop->index) % 3]) }}"
                    alt="{{ $__shopCatName }}"
                    class="absolute inset-0 h-full w-full object-cover"
                  />
                  <div
                    class="absolute inset-0"
                    style="background: linear-gradient(0deg, rgba(0, 0, 0, 0.79) 0%, rgba(0, 0, 0, 0) 100%);"
                  ></div>
                  <span class="relative text-white font-medium text-[16px] lg:text-[25.4072px] leading-[24px] lg:leading-[38px] tracking-[0.5px] lg:tracking-[0.793976px]">{{ $__shopCatName }}</span>
                </a>
              @endforeach
            </div>
          @endforeach
        </div>
      </div>
      <div class="w-full flex justify-center">
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="inline-flex items-center justify-center border rounded-full lg:rounded-[34px] w-[121px] h-[38px] p-2 lg:w-52 lg:h-16 lg:p-2 lg:gap-2 text-[14px] lg:text-[20px] leading-[25px] lg:leading-6.25 font-medium tracking-[0.5px] cursor-pointer"
          style="
            border-color: var(--color-text-primary);
            color: var(--color-text-primary);
          "
        >
          Explore all
        </a>
      </div>
    </section>
