@props([
    'theme',
    'threshold',
    'weight',
    'itemWeight' => null,
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
        <div id="elora-shipping-widget{{ $idSuffix }}" class="p-3 bg-[#ff4d0016] {{ $bare ? '' : 'rounded-2xl border border-[#ff4d00]' }}">
            <p id="elora-shipping-message{{ $idSuffix }}" class="text-center text-[15px] text-[#FF4D00] font-medium mb-2">
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
    @elseif($theme === 'souqify')
        <div class="bg-[#FDF6F0] border border-[#F8CFB9] rounded-xl p-2 lg:p-4 flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1 text-sm text-[#FF570F]">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_180_11273)">
                            <path d="M9.33594 12V3.99996C9.33594 3.64634 9.19546 3.3072 8.94541 3.05715C8.69536 2.8071 8.35623 2.66663 8.0026 2.66663H2.66927C2.31565 2.66663 1.97651 2.8071 1.72646 3.05715C1.47641 3.3072 1.33594 3.64634 1.33594 3.99996V11.3333C1.33594 11.5101 1.40618 11.6797 1.5312 11.8047C1.65622 11.9297 1.82579 12 2.0026 12H3.33594" stroke="#FF570F" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M10 12H6" stroke="#FF570F" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M12.6693 12H14.0026C14.1794 12 14.349 11.9298 14.474 11.8048C14.599 11.6798 14.6693 11.5102 14.6693 11.3334V8.90004C14.669 8.74875 14.6173 8.60205 14.5226 8.48404L12.2026 5.58404C12.1403 5.50596 12.0611 5.44289 11.9711 5.39951C11.8811 5.35612 11.7825 5.33352 11.6826 5.33337H9.33594" stroke="#FF570F" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M11.3333 13.3333C12.0697 13.3333 12.6667 12.7363 12.6667 12C12.6667 11.2636 12.0697 10.6666 11.3333 10.6666C10.597 10.6666 10 11.2636 10 12C10 12.7363 10.597 13.3333 11.3333 13.3333Z" stroke="#FF570F" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M4.66927 13.3333C5.40565 13.3333 6.0026 12.7363 6.0026 12C6.0026 11.2636 5.40565 10.6666 4.66927 10.6666C3.93289 10.6666 3.33594 11.2636 3.33594 12C3.33594 12.7363 3.93289 13.3333 4.66927 13.3333Z" stroke="#FF570F" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round" />
                        </g>
                        <defs>
                            <clipPath id="clip0_180_11273">
                                <rect width="16" height="16" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>

                    <p id="sq-shipping-message{{ $idSuffix }}" class="text-xs lg:text-base">
                        @if($reached)
                            <span class="font-semibold">{{ __('You unlocked FREE shipping') }}</span>
                        @else
                            {{ __('Add :weight more to unlock FREE shipping', ['weight' => $weightLabel($remaining)]) }}
                        @endif
                    </p>
                </div>
                <p id="sq-shipping-ratio{{ $idSuffix }}" class="text-xs text-[#8F8F8F] hidden lg:block">{{ $weightLabel($weight) }}/{{ $weightLabel($threshold) }}</p>
            </div>
            <div class="h-[10px] bg-[#D9D9D9] rounded-full">
                <span id="sq-shipping-bar{{ $idSuffix }}" class="rounded-full h-full bg-[#FF570F] block" style="width: {{ $pct }}%"></span>
            </div>
            @if($itemWeight !== null)
                <p class="text-[10px] lg:text-xs text-[#8F8F8F]">{{ __('This item weighs') }} <span id="sq-item-weight{{ $idSuffix }}" class="font-semibold">{{ $weightLabel($itemWeight) }}.</span>
                    {{ __('free shipping threshold:') }} <span class="font-semibold">{{ $weightLabel($threshold) }}</span>
                    {{ __('combined order weight.') }}
                </p>
            @endif
        </div>
    @endif
@endif
