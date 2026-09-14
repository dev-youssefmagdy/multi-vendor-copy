@props(['href' => null, 'icon' => null, 'danger' => false])

@php $tag = $href ? 'a' : 'button'; @endphp

<{{ $tag }} @if($href) href="{{ $href }}" @else type="button" @endif role="menuitem"
    {{ $attributes->merge(['class' => 't-dropdown-item '.($danger ? 'is-danger' : '')]) }}>
    @if($icon)<span class="t-dropdown-item-icon">{!! $icon !!}</span>@endif
    {{ $slot }}
</{{ $tag }}>
