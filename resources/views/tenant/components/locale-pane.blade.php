@props([
    'code' => null,
    'dir' => 'ltr',
    'active' => false,
])

<div class="t-locale-pane {{ $active ? 'is-active' : '' }}" data-locale-pane="{{ $code }}" dir="{{ $dir }}" lang="{{ $code }}" @unless($active) hidden @endunless>
    {{ $slot }}
</div>
