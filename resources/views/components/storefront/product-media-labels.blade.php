{{--
    Label bar on the bottom-left corner of the product-page image slider.
      labels — $product->cardLabels()
      bg     — bar background (theme colour)
      color  — text / icon colour
      limit  — max labels (default 1)
      class  — extra classes (e.g. "sm:hidden")
--}}
@props(['labels' => [], 'bg' => 'var(--color-primary)', 'color' => '#fff', 'limit' => 1, 'class' => ''])

@if (!empty($labels))
    <div class="product-media-labels absolute bottom-0 start-0 z-10 flex max-w-[90%] flex-wrap items-center gap-y-1 rounded-se-xl px-3 py-2 lg:px-4 lg:py-2.5 {{ $class }}"
        style="background: {{ $bg }}; color: {{ $color }}">
        @foreach (array_slice((array) $labels, 0, $limit) as $label)
            <span class="flex items-center gap-1.5 text-xs font-medium leading-none lg:text-sm {{ !$loop->first ? 'ms-3 ps-3 border-s border-current/25' : '' }}">
                @if (!empty($label['icon']))
                    <svg class="size-4 shrink-0 lg:size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $label['icon'] !!}</svg>
                @endif
                <span class="whitespace-nowrap">{{ $label['text'] }}</span>
            </span>
        @endforeach
    </div>
@endif
