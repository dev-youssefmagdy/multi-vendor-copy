{{ $gateway->name }}
@if($gateway->is_primary)
    <span class="badge badge-cyan">{{ __('Primary') }}</span>
@endif
