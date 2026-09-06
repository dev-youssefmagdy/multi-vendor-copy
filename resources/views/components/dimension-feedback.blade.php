@props(['width', 'height'])

<div class="dimension-preview-box" style="aspect-ratio: {{ $width }} / {{ $height }}">
    <img class="dimension-preview-img" hidden alt="Preview">
</div>
<p class="dimension-warning" hidden></p>
