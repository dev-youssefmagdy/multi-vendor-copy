@if(session('status'))
    <div id="flash-status" data-message="{{ session('status') }}" data-type="{{ session('status_type', 'success') }}" hidden></div>
@endif
@if(session('setup_warning'))
    <div id="flash-setup-warning" data-message="{{ session('setup_warning') }}" data-type="warning" hidden></div>
@endif
@if(session('setup_error'))
    <div id="flash-setup-error" data-message="{{ session('setup_error') }}" data-type="error" hidden></div>
@endif
@php
    $paymentFlashKeys = [
        'success' => ['payment_success', 'purchase_success', 'subscription_success', 'vendor_settlement_success', 'mf_payment_success', 'br_payment_success'],
        'error' => ['purchase_error', 'vendor_settlement_error', 'mf_payment_error', 'br_payment_error'],
        'warning' => ['payment_cancelled', 'subscription_cancelled'],
    ];
@endphp
@foreach($paymentFlashKeys as $type => $keys)
    @foreach($keys as $key)
        @if(session($key))
            <div id="flash-{{ str_replace('_', '-', $key) }}" data-message="{{ session($key) }}" data-type="{{ $type }}" hidden></div>
        @php session()->forget($key); @endphp
        @endif
    @endforeach
@endforeach
