{{--
    Renders a product's labels (icon + text) inside a product card.
    The label data is theme-agnostic; each card passes its own existing label style:
      labels    — $product->cardLabels() / $p['labels']: [['icon' => inner svg|null, 'text' => string], ...]
      tag       — wrapper element per label (p, span, div)
      class     — classes on each label wrapper (the card's own "label row" typography/colour)
      iconClass — classes on the <svg> (size)
      style     — inline style on each label wrapper (e.g. "color:var(--color-success)")
      limit     — max labels to render (null = all)
--}}
@props(['labels' => [], 'tag' => 'p', 'class' => '', 'iconClass' => 'w-3 h-3 shrink-0', 'style' => null, 'limit' => null])

@foreach (array_slice((array) $labels, 0, $limit) as $label)
    <{{ $tag }} class="product-label flex items-center min-w-0 {{ $class }}" @if ($style) style="{{ $style }}" @endif>
        @if (!empty($label['icon']))
            <svg class="{{ $iconClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $label['icon'] !!}</svg>
        @endif
        <span class="truncate">{{ $label['text'] }}</span>
    </{{ $tag }}>
@endforeach
