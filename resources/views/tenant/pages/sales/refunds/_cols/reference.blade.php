<div class="entity-title" style="font-family:ui-monospace,monospace">{{ $refund->reference }}</div>
@if($refund->return_request_id)
    <a class="entity-subtitle" href="{{ route('tenant.returns.show', $refund->return_request_id) }}">{{ __('Return #:id', ['id' => $refund->return_request_id]) }}</a>
@endif
