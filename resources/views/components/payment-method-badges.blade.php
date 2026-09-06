{{--
    Renders small pill badges for each payment method a gateway supports.
    Props:
      methods  - array of payment method keys, e.g. ['card', 'apple_pay', 'google_pay']
      size     - 'sm' (default) | 'xs'
--}}
@props(['methods' => [], 'size' => 'sm'])

@php
    $icons = \App\PaymentGateway\PaymentMethodIcons::resolve($methods);
    $h = $size === 'xs' ? '16px' : '20px';
@endphp

@if(!empty($icons))
<div {{ $attributes->merge(['class' => 'pm-badges']) }} style="display:flex;flex-wrap:wrap;gap:4px;align-items:center;">
    @foreach($icons as $pm)
        <span class="pm-badge pm-badge-{{ $pm['icon'] }}" title="{{ $pm['label'] }}"
              style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:6px;border:1px solid #e5e7eb;background:#fff;font-size:10.5px;font-weight:600;color:#374151;white-space:nowrap;height:{{ $h }};">
            @include('components.payment-method-icon', ['icon' => $pm['icon']])
            {{ $pm['label'] }}
        </span>
    @endforeach
</div>
@endif
