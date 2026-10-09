{{-- Refund rows with their actions (RefundPresenter::panel() arrays). Shared by Sales > Orders show and
     Sales > Returns show. Params: $refunds (array), $canManage (bool), $showReturnLink (bool, default true). --}}
@php
    $refunds = $refunds ?? [];
    $canManage = $canManage ?? false;
    $showReturnLink = $showReturnLink ?? true;
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('M d, Y H:i') : '—';
    $money = fn ($refund, $value) => ($refund['currency'] ?? 'USD').' '.number_format((float) $value, 2);
@endphp

@if(empty($refunds))
    <div class="json-empty">{{ __('No refunds have been issued for this order yet.') }}</div>
@else
    <x-tenant::table :headers="[__('Reference'), __('Source'), __('Amount'), __('Method'), __('Status'), __('Dates'), __('Actions')]">
        @foreach($refunds as $refund)
            <tr data-refund-row="{{ $refund['id'] }}">
                <td>
                    <div class="details-inline-title" style="font-family:ui-monospace,monospace">{{ $refund['reference'] }}</div>
                    @if($showReturnLink && !empty($refund['return_request_id']))
                        <a class="details-inline-copy" href="{{ route('tenant.returns.show', $refund['return_request_id']) }}">{{ __('Return #:id', ['id' => $refund['return_request_id']]) }}</a>
                    @endif
                </td>
                <td>
                    <div class="details-inline-title">{{ $refund['source_label'] ?? '—' }}</div>
                    @if(filled($refund['reason'] ?? null))
                        <div class="details-inline-copy">{{ $refund['reason'] }}</div>
                    @endif
                </td>
                <td>
                    <div class="details-inline-title">{{ $money($refund, $refund['amount']) }}</div>
                    <div class="details-inline-copy">
                        {{ __('Items') }} {{ number_format((float) $refund['items_amount'], 2) }}
                        · {{ __('Shipping') }} {{ number_format((float) $refund['shipping_amount'], 2) }}
                        · {{ __('Fee') }} −{{ number_format((float) $refund['return_fee'], 2) }}
                    </div>
                </td>
                <td>
                    <div class="details-inline-title">{{ $refund['method_label'] ?? '—' }}</div>
                    @if(filled($refund['gateway'] ?? null))
                        <div class="details-inline-copy">{{ str((string) $refund['gateway'])->replace(['_', '-'], ' ')->headline() }}@if(filled($refund['gateway_refund_id'] ?? null)) · {{ $refund['gateway_refund_id'] }}@endif</div>
                    @endif
                    @if(filled($refund['manual_reference'] ?? null))
                        <div class="details-inline-copy">{{ __('Reference') }}: {{ $refund['manual_reference'] }}</div>
                    @endif
                </td>
                <td>
                    <span class="badge badge-{{ $refund['status_color'] ?? 'gray' }}"><span class="badge-dot"></span>{{ $refund['status_label'] ?? '—' }}</span>
                    @if(filled($refund['failure_reason'] ?? null))
                        <div class="details-inline-copy" style="color:var(--red);margin-top:4px">{{ $refund['failure_reason'] }}</div>
                    @endif
                </td>
                <td>
                    <div class="details-inline-copy">{{ __('Requested') }}: {{ $date($refund['requested_at'] ?? null) }}</div>
                    <div class="details-inline-copy">{{ __('Processed') }}: {{ $date($refund['processed_at'] ?? null) }}</div>
                    @if(filled($refund['approved_by'] ?? null))
                        <div class="details-inline-copy">{{ __('Approved by') }}: {{ $refund['approved_by'] }}</div>
                    @endif
                </td>
                <td>
                    @if($canManage && (!empty($refund['can_retry']) || !empty($refund['can_complete']) || !empty($refund['can_reject'])))
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            @if(!empty($refund['can_retry']))
                                <button type="button" class="btn btn-secondary btn-sm"
                                    data-action-url="{{ route('tenant.refunds.retry', $refund['id']) }}"
                                    data-action-method="POST"
                                    data-confirm="{{ __('Send this refund to the payment gateway now?') }}"
                                    data-success="reload-page">{{ __('Retry') }}</button>
                            @endif
                            @if(!empty($refund['can_complete']))
                                <button type="button" class="btn btn-secondary btn-sm" data-modal-open="refund-complete-{{ $refund['id'] }}">{{ __('Mark completed') }}</button>
                            @endif
                            @if(!empty($refund['can_reject']))
                                <button type="button" class="btn btn-sm" style="background:var(--red);color:#fff" data-modal-open="refund-reject-{{ $refund['id'] }}">{{ __('Reject') }}</button>
                            @endif
                        </div>
                    @else
                        <span class="details-inline-copy">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-tenant::table>

    @if($canManage)
        @foreach($refunds as $refund)
            @if(!empty($refund['can_complete']))
                <x-tenant::modal id="refund-complete-{{ $refund['id'] }}" :title="__('Mark refund :reference as completed', ['reference' => $refund['reference']])">
                    <p class="panel-copy" style="margin-bottom:16px">{{ __('Use this when the money was returned outside the system (bank transfer, cash, …).') }}</p>
                    <x-tenant::form action="{{ route('tenant.refunds.complete', $refund['id']) }}" validate="{{ route('tenant.refunds.complete.validate', $refund['id']) }}" success="reload-page">
                        <x-tenant::input name="reference" :label="__('External reference (optional)')" maxlength="190" :placeholder="__('e.g. bank transfer ID')" />
                        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                            <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Mark completed') }}</button>
                        </div>
                    </x-tenant::form>
                </x-tenant::modal>
            @endif
            @if(!empty($refund['can_reject']))
                <x-tenant::modal id="refund-reject-{{ $refund['id'] }}" :title="__('Reject refund :reference', ['reference' => $refund['reference']])">
                    <x-tenant::form action="{{ route('tenant.refunds.reject', $refund['id']) }}" validate="{{ route('tenant.refunds.reject.validate', $refund['id']) }}" success="reload-page">
                        <x-tenant::textarea name="reason" :label="__('Reason (shown to the customer)')" rows="3" maxlength="1000" required />
                        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                            <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                            <button type="submit" class="btn" style="background:var(--red);color:#fff">{{ __('Reject refund') }}</button>
                        </div>
                    </x-tenant::form>
                </x-tenant::modal>
            @endif
        @endforeach
    @endif
@endif
