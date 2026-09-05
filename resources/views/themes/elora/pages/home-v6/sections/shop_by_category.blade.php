    <!-- ============ SHOP BY CATEGORY ============ -->
    <section
      class="bg-white w-full max-w-360 mx-auto border-l-[6px] lg:border-l-14 flex flex-col justify-end lg:justify-start items-center px-2 py-4.5 lg:px-14 lg:py-8 gap-2 lg:gap-6"
      style="border-color: var(--color-accent-green)"
    >
      <h2
        class="lg:hidden w-full font-medium text-[22px]"
        style="color: var(--color-accent-green)"
      >
        Shop by Category
      </h2>
      <div class="w-full flex gap-[8px] lg:gap-[19px] items-stretch">
        <div
          class="hidden lg:flex items-center justify-center w-[66px] shrink-0"
        >
          <p
            class="vertical-heading font-semibold text-[24px] tracking-[0.7px] whitespace-nowrap"
            style="color: var(--color-accent-green)"
          >
            Shop by Category
          </p>
        </div>
        <div
          class="grid grid-cols-2 lg:grid-cols-3 gap-[10px] lg:gap-[19px] flex-1"
        >
          @php
            $__shopFallbackImgs = ['shop-accessories.png', 'shop-fashion.png', 'shop-electronics.png'];
          @endphp
          @foreach ($categories->take(5) as $category)
            @php
              $__shopCatName = $category->translationValue('name') ?? $category->slug;
            @endphp
            <a
              href="{{ route('tenant.storefront.category', $category->slug) }}"
              class="relative rounded-[8px] lg:rounded-[13px] overflow-hidden flex justify-center p-3 h-40 lg:h-auto {{ $loop->first ? 'items-end lg:py-6.25 lg:px-0 lg:row-span-2' : 'items-center lg:pt-33.75 lg:pr-17.5 lg:pb-1.25 lg:pl-17' }}"
            >
              <img
                src="{{ $category->thumb_url ?? asset('elora-2/assets/images/' . $__shopFallbackImgs[$loop->index % 3]) }}"
                alt="{{ $__shopCatName }}"
                class="absolute inset-0 h-full w-full object-cover"
              />
              <div
                class="absolute inset-0"
                style="
                  background: linear-gradient(
                    0deg,
                    rgba(0, 0, 0, 0.79) 0%,
                    rgba(0, 0, 0, 0) 100%
                  );
                "
              ></div>
              <span
                class="relative text-white font-medium text-[16px] lg:text-[25px] tracking-[0.5px] lg:tracking-[0.8px]"
                >{{ $__shopCatName }}</span
              >
            </a>
          @endforeach
        </div>
      </div>
      <div class="w-full flex justify-center">
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="border rounded-full px-[32px] py-[16px] text-[14px] lg:text-[20px] font-medium tracking-[0.5px] cursor-pointer"
          style="
            border-color: var(--color-text-primary);
            color: var(--color-text-primary);
          "
        >
          Explore all
        </a>
      </div>
    </section>
