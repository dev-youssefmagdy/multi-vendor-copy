@props(['width', 'height', 'label' => null])

<p class="dimension-hint">
    Required size{{ $label ? " ({$label})" : '' }}: <strong>{{ $width }} × {{ $height }}px</strong>
</p>
