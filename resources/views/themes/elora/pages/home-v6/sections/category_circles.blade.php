    <!-- ============ CATEGORIES ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[34px]"
      style="background: var(--color-page-bg)"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px] lg:leading-[40px]"
          style="color: #000000"
        >
          Categories
        </h2>
        <a
          href="#"
          class="font-normal text-[14px] lg:text-[24px] lg:leading-[30px] tracking-[0.5px]"
          style="color: var(--color-accent-green)"
          >see all</a
        >
      </div>
      <div class="swiper categories-swiper">
        <div class="swiper-wrapper" id="categoriesWrapper">
          @php
            $__catIcons = ['cat-bag.png', 'cat-lamp.png', 'cat-controller.png', 'cat-laptop.png'];
          @endphp
          @foreach ($categories->take(8) as $category)
            @php
              $__catFilled = $loop->index % 4 === 2;
              $__catIcon = $__catIcons[$loop->index % 4];
              $__catRotate = $loop->index % 4 === 2 ? '-rotate-[15deg]' : '';
              $__catName = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 15);
            @endphp
            <a
              href="{{ route('tenant.storefront.category', $category->slug) }}"
              class="swiper-slide {{ $__catFilled ? '' : 'border-2 lg:border-[1.86px]' }} flex flex-col items-center justify-between gap-[4px] lg:gap-[5.59px] px-[12px] py-[8px] lg:px-[22.35px] lg:py-[14.9px] rounded-[12px] lg:rounded-[22.35px]"
              style="{{ $__catFilled ? 'background: var(--color-accent-green)' : 'border-color: var(--color-accent-green)' }}"
            >
              <img
                src="{{ $category->thumb_url ?? asset('elora-2/assets/icons/' . $__catIcon) }}"
                alt="{{ $__catName }}"
                class="mx-auto w-[39px] h-[39px] lg:w-[73px] lg:h-[73px] object-contain {{ $__catRotate }}"
              />
              <p
                class="w-full min-w-0 truncate text-center font-semibold text-[12px] lg:text-[22.35px] lg:leading-[150%] tracking-[0.5px] lg:tracking-[0.93px] {{ $__catFilled ? 'text-white' : '' }}"
                @unless($__catFilled) style="color: var(--color-accent-green)" @endunless
              >
                {{ $__catName }}
              </p>
            </a>
          @endforeach
        </div>
      </div>
    </section>
