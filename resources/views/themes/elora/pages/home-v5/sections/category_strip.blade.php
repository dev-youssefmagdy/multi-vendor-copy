    {{-- No direct counterpart in the elora-5 mockup; built to match its visual language
         (Outfit type, blue-primary/yellow accents, pill chips over the section rhythm). --}}
    @php
      $stripCategories = ['Women bags', 'Accessories', 'Gaming', 'Electronics'];
    @endphp
    <section
      class="overflow-x-auto no-scrollbar"
      style="background: var(--color-primary)"
    >
      <div class="flex items-center gap-[12px] lg:gap-[20px] px-[16px] lg:px-[56px] py-[14px] lg:py-[18px] w-max lg:w-full lg:justify-center">
        @foreach ($stripCategories as $cat)
          <a
            href="#"
            class="shrink-0 flex items-center justify-center rounded-full px-[18px] lg:px-[28px] h-[36px] lg:h-[46px] text-[13px] lg:text-[16px] font-medium tracking-[0.3px] whitespace-nowrap"
            style="background: var(--color-yellow); color: var(--color-black-alt)"
          >
            {{ $cat }}
          </a>
        @endforeach
      </div>
    </section>
