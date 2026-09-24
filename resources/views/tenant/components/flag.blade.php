@props(['code' => '', 'label' => null])

{{-- Small 4:3 country flag + short label, e.g. <x-tenant::flag code="sa" label="KSA" />. Simplified artwork for UI chips. --}}

<span {{ $attributes->merge(['class' => 't-flag']) }}>
    <svg viewBox="0 0 20 15" width="21" height="16" aria-hidden="true">
        @switch(strtolower($code))
            @case('sa')
                <rect width="20" height="15" fill="#005430"/><path d="M4 5.5h12M5 7h10" stroke="#fff" stroke-width=".9"/><path d="M5 10.5h9" stroke="#fff" stroke-width=".8"/>
                @break
            @case('gb')
                <rect width="20" height="15" fill="#2E42A5"/><path d="M0 0l20 15M20 0L0 15" stroke="#fff" stroke-width="3"/><path d="M0 0l20 15M20 0L0 15" stroke="#F50100" stroke-width="1"/><path d="M10 0v15M0 7.5h20" stroke="#fff" stroke-width="4.5"/><path d="M10 0v15M0 7.5h20" stroke="#F50100" stroke-width="2.5"/>
                @break
            @case('eg')
                <rect width="20" height="5" fill="#BF2714"/><rect y="5" width="20" height="5" fill="#fff"/><rect y="10" width="20" height="5" fill="#272727"/><circle cx="10" cy="7.5" r="1.4" fill="#C09302"/>
                @break
            @case('us')
                <rect width="20" height="15" fill="#F7FCFF"/>@foreach([0, 2.3, 4.6, 6.9, 9.2, 11.5, 13.8] as $y)<rect y="{{ $y }}" width="20" height="1.15" fill="#E31D1C"/>@endforeach<rect width="9" height="8" fill="#2E42A5"/>
                @break
            @case('ae')
                <rect width="20" height="5" fill="#5EAA22"/><rect y="5" width="20" height="5" fill="#fff"/><rect y="10" width="20" height="5" fill="#272727"/><rect width="6" height="15" fill="#E31D1C"/>
                @break
            @case('fr')
                <rect width="7" height="15" fill="#2E42A5"/><rect x="7" width="6" height="15" fill="#F7FCFF"/><rect x="13" width="7" height="15" fill="#F50100"/>
                @break
            @case('ma')
                <rect width="20" height="15" fill="#E31D1C"/><path d="M10 4.2l1 3.1h3.2l-2.6 1.9 1 3.1-2.6-1.9-2.6 1.9 1-3.1-2.6-1.9H9z" fill="none" stroke="#579D20" stroke-width=".7"/>
                @break
            @case('iq')
                <rect width="20" height="5" fill="#BF2714"/><rect y="5" width="20" height="5" fill="#fff"/><rect y="10" width="20" height="5" fill="#272727"/><path d="M6.5 7.5h7" stroke="#009C4E" stroke-width="1.2"/>
                @break
            @case('qa')
                <rect width="20" height="15" fill="#B61C49"/><path d="M0 0h7.5l-1.5 .83 1.5.83-1.5.83 1.5.83-1.5.83 1.5.83-1.5.83 1.5.83-1.5.83 1.5.83-1.5.83 1.5.83-1.5.83 1.5.83-1.5.83 1.5.83-1.5.83 1.5.87H0z" fill="#F7FCFF"/>
                @break
            @default
                <rect width="20" height="15" fill="#D9D9D9"/>
        @endswitch
    </svg>
    @if($label)<span>{{ $label }}</span>@endif
</span>
