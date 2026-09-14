@props(['key' => null, 'active' => false])

<div class="t-tab-panel {{ $active ? 'is-active' : '' }}" data-tab-panel="{{ $key }}" @unless($active) hidden @endunless>
    {{ $slot }}
</div>
