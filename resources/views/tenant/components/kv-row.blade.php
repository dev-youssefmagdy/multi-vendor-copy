@props(['label' => null])

<div class="t-kv-row">
    <dt class="t-kv-label">{{ $label }}</dt>
    <dd class="t-kv-value">{{ $slot }}</dd>
</div>
