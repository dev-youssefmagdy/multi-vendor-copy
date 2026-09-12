@props(['columns' => 2])

<dl class="t-kv" style="--cols: {{ $columns }}">
    {{ $slot }}
</dl>
