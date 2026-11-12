@switch($gateway->webhook_status)
    @case('connected')
        <span class="badge badge-green">{{ __('Webhook OK') }}</span>
        @break
    @case('failed')
        <span class="badge badge-red">{{ __('Webhook failed') }}</span>
        @break
    @default
        <span class="badge badge-gray">{{ __('No webhook events yet') }}</span>
@endswitch
