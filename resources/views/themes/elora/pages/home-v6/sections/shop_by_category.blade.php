    <!-- ============ SHOP BY CATEGORY ============ -->
    <section
      class="bg-white border-l-[8px] lg:border-l-[14px] px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]"
      style="border-color: var(--color-accent-green)"
    >
      <h2
        class="lg:hidden font-medium text-[22px]"
        style="color: var(--color-accent-green)"
      >
        Shop by Category
      </h2>
      <div class="flex gap-[8px] lg:gap-[19px] items-stretch">
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
              class="relative rounded-[8px] lg:rounded-[13px] overflow-hidden flex {{ $loop->first ? 'items-end' : 'items-center' }} justify-center p-[12px] lg:p-[16px] h-[160px] lg:h-auto {{ $loop->first ? 'lg:row-span-2' : '' }}"
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
      <div class="flex justify-center">
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
