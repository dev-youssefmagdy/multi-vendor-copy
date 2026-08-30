    @php
      $categories = [
        ['name' => 'Women bags', 'image' => asset('elora-5/assets/images/cat-bag.png')],
        ['name' => 'Accessories', 'image' => asset('elora-5/assets/images/cat-accessories.png')],
        ['name' => 'Gaming', 'image' => asset('elora-5/assets/images/cat-gaming.png')],
        ['name' => 'Electronics', 'image' => asset('elora-5/assets/images/cat-electronics.png')],
        ['name' => 'Women bags', 'image' => asset('elora-5/assets/images/cat-bag.png')],
        ['name' => 'Accessories', 'image' => asset('elora-5/assets/images/cat-accessories.png')],
        ['name' => 'Gaming', 'image' => asset('elora-5/assets/images/cat-gaming.png')],
        ['name' => 'Electronics', 'image' => asset('elora-5/assets/images/cat-electronics.png')],
      ];
    @endphp
    <!-- ============ CATEGORIES ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[40px] flex flex-col gap-[16px] lg:gap-[28px]"
      style="background: var(--color-page-bg)"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[20px] lg:text-[32px]"
          style="color: var(--color-black)"
        >
          Categories
        </h2>
        <a
          href="#"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-primary)"
          >see all</a
        >
      </div>
      <div
        class="flex items-start gap-[16px] lg:gap-[32px] overflow-x-auto no-scrollbar pb-[4px]"
      >
        @foreach ($categories as $c)
          <div class="flex flex-col items-center gap-[6px] shrink-0 w-[80px] lg:w-[150px]">
            <div class="category-blob relative flex items-center justify-center w-full h-[64px] lg:h-[120px]">
              <img src="{{ $c['image'] }}" alt="{{ $c['name'] }}" class="h-[42px] lg:h-[78px] w-auto object-contain" />
            </div>
            <p class="font-semibold text-[12px] lg:text-[20px] tracking-[0.3px] text-center whitespace-nowrap" style="color:var(--color-black)">{{ $c['name'] }}</p>
          </div>
        @endforeach
      </div>
    </section>
