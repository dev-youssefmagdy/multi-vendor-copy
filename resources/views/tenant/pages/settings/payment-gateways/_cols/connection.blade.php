@php($status = $gateway->getRawOriginal('connection_status'))
@switch($status)
    @case('connected')
        <span class="badge badge-green">Connected</span>
        @break
    @case('not_connected')
        <span class="badge badge-red">Not Connected</span>
        @break
    @default
        <span class="badge badge-gray">Not checked</span>
@endswitch
