@props(['lines' => 3, 'height' => 12])

<div class="t-skeleton" aria-hidden="true">
    @for($i = 0; $i < $lines; $i++)
        <div class="t-skeleton-line" style="--h: {{ $height }}px; width: {{ $i === $lines - 1 ? '60%' : '100%' }}"></div>
    @endfor
</div>
