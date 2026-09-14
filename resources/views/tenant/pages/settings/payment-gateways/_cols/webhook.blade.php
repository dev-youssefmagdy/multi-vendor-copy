@switch($gateway->webhook_status)
    @case('connected')
        <span class="badge badge-green">Webhook OK</span>
        @break
    @case('failed')
        <span class="badge badge-red">Webhook failed</span>
        @break
    @default
        <span class="badge badge-gray">No webhook events yet</span>
@endswitch
