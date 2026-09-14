@props(['name' => '', 'seed' => null, 'size' => 30, 'src' => null])

@php
    $palette = ['cyan', 'violet', 'green', 'amber', 'red'];
    $seedValue = $seed ?? $name;
    $paletteIndex = crc32((string) $seedValue) % count($palette);
    $colorKey = $palette[$paletteIndex];

    $initials = collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
    $initials = mb_strtoupper($initials) ?: '?';
@endphp

@if($src)
    <img src="{{ $src }}" alt="{{ $name }}" class="t-avatar t-avatar-img" style="--avatar-size: {{ $size }}px" width="{{ $size }}" height="{{ $size }}">
@else
    <span class="t-avatar t-avatar-{{ $colorKey }}" style="--avatar-size: {{ $size }}px" aria-hidden="true">{{ $initials }}</span>
@endif
