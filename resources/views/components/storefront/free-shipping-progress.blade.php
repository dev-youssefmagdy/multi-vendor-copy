@props([
    'theme',
    'threshold',
    'weight',
    'idSuffix' => '',
    'bare' => false,
])

@php
    $pct = $threshold > 0 ? min(100, (int) round($weight / $threshold * 100)) : 0;
    $remaining = max(0, $threshold - $weight);
    $reached = $weight >= $threshold;
    $weightLabel = fn (int $g) => $g >= 1000 ? number_format($g / 1000, 2) . __('kg') : number_format($g) . __('g');
@endphp

@if($threshold > 0)
    @if($theme === 'elora')
        <div id="elora-shipping-widget{{ $idSuffix }}" class="p-3 bg-[#ff4d0016] {{ $bare ? '' : 'rounded-2xl border border-[#ff4d00]' }} flex items-center gap-3">
            <div class="flex-1 min-w-0">
                <p id="elora-shipping-message{{ $idSuffix }}" class="text-start text-[15px] text-[#FF4D00] font-medium mb-2">
                    @if($reached)
                        {{ __("You've reached free shipping!") }}
                    @else
                        {{ __('Add :weight more to qualify for free shipping', ['weight' => $weightLabel($remaining)]) }}
                    @endif
                </p>
                <div class="h-2 bg-[#D9D9D9] rounded-full overflow-hidden">
                    <div id="elora-shipping-bar{{ $idSuffix }}" class="h-full bg-[#FF4D00] rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            {{-- Last in the row: right of the bar in LTR, left of it in RTL --}}
            <x-storefront.free-shipping-icon :size="44" color="#FF4D00" />
        </div>
    @elseif($theme === 'souqify')
        <div class="bg-[#FDF6F0] border border-[#F8CFB9] rounded-xl p-2 lg:p-4 flex items-center gap-3">
            <div class="flex-1 min-w-0 flex flex-col gap-2">
                <p id="sq-shipping-message{{ $idSuffix }}" class="text-xs lg:text-base text-[#FF570F]">
                    @if($reached)
                        <span class="font-semibold">{{ __('You unlocked FREE shipping') }}</span>
                    @else
                        {{ __('Add :weight more to unlock FREE shipping', ['weight' => $weightLabel($remaining)]) }}
                    @endif
                </p>
                <div class="h-[10px] bg-[#D9D9D9] rounded-full">
                    <span id="sq-shipping-bar{{ $idSuffix }}" class="rounded-full h-full bg-[#FF570F] block" style="width: {{ $pct }}%"></span>
                </div>
            </div>
            {{-- Last in the row: right of the bar in LTR, left of it in RTL --}}
            <x-storefront.free-shipping-icon :size="40" color="#FF570F" />
        </div>
    @endif
@endif
