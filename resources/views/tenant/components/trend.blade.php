@props([
    'value' => null,
    'suffix' => '%',
])

{{--
    Growth pill: <x-tenant::trend :value="12" /> → "+12%" (green, up arrow),
    <x-tenant::trend :value="-4.5" /> → "-4.5%" (red, down arrow).
    Also accepts a pre-formatted string such as "+12%".
--}}

@php
    $numeric = is_numeric($value) ? (float) $value : (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    $direction = $numeric > 0 ? 'up' : ($numeric < 0 ? 'down' : 'flat');
    $text = is_numeric($value)
        ? ($numeric > 0 ? '+' : '').rtrim(rtrim(number_format($numeric, 1, '.', ''), '0'), '.').$suffix
        : (string) $value;
@endphp

<span {{ $attributes->merge(['class' => "t-trend t-trend-$direction"]) }}>
    @if($direction === 'up')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 7.25l-7 7-4-4-6 6"/><path d="M16 7.25h4.5v4.5"/></svg>
    @elseif($direction === 'down')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 16.75l-7-7-4 4-6-6"/><path d="M16 16.75h4.5v-4.5"/></svg>
    @endif
    {{ $text }}
</span>
