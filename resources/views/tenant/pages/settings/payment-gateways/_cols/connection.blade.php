@php($status = $gateway->getRawOriginal('connection_status'))
@switch($status)
    @case('connected')
        <span class="badge badge-green">{{ __('Connected') }}</span>
        @break
    @case('not_connected')
        <span class="badge badge-red">{{ __('Not Connected') }}</span>
        @break
    @default
        <span class="badge badge-gray">{{ __('Not checked') }}</span>
@endswitch
