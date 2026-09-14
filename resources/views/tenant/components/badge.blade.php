@props(['color' => 'cyan', 'dot' => false])

<span {{ $attributes->merge(['class' => 'badge badge-'.$color]) }}>
    @if($dot)<span class="badge-dot"></span>@endif
    {{ $slot }}
</span>
