@props(['color' => 'muted'])

@php
    $colorMap = [
        'green' => 'c-g',
        'amber' => 'c-a',
        'red' => 'c-r',
        'cyan' => 'c-c',
        'muted' => 'bg-[var(--elevated)] text-[var(--t2)] border border-[var(--border)]',
    ];
    $colorClass = $colorMap[$color] ?? $colorMap['muted'];
@endphp

<span {{ $attributes->merge(['class' => "chip inline-flex items-center justify-center $colorClass"]) }}>
    {{ $slot }}
</span>
