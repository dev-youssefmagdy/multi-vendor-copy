    <!-- ============ CATEGORIES ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[34px]"
      style="background: var(--color-page-bg)"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          Categories
        </h2>
        <a
          href="#"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-green)"
          >see all</a
        >
      </div>
      <div
        class="flex items-stretch gap-[12px] lg:gap-[22px] overflow-x-auto no-scrollbar pb-[4px]"
      >
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
            class="{{ $__catFilled ? '' : 'border-2' }} flex flex-col items-center justify-between px-[12px] py-[8px] lg:px-[22px] lg:py-[15px] rounded-[12px] lg:rounded-[22px] shrink-0"
            style="{{ $__catFilled ? 'background: var(--color-accent-green)' : 'border-color: var(--color-accent-green)' }}"
          >
            <img
              src="{{ $category->thumb_url ?? asset('elora-2/assets/icons/' . $__catIcon) }}"
              alt="{{ $__catName }}"
              class="w-[39px] lg:w-[73px] h-auto {{ $__catRotate }}"
            />
            <p
              class="font-semibold text-[12px] lg:text-[22px] tracking-[0.5px] lg:tracking-[0.9px] whitespace-nowrap {{ $__catFilled ? 'text-white' : '' }}"
              @unless($__catFilled) style="color: var(--color-accent-green)" @endunless
            >
              {{ $__catName }}
            </p>
          </a>
        @endforeach
      </div>
    </section>
