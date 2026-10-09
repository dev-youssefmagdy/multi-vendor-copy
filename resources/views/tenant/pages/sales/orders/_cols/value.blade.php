@php($paymentState = $order->paymentState())
$ {{ number_format((float) $order->grand_total, 2) }}
@if(in_array($paymentState, [\App\Enums\OrderPaymentStatus::Refunded, \App\Enums\OrderPaymentStatus::PartiallyRefunded], true))
    <div><span class="badge badge-{{ $paymentState->color() }}" title="{{ __('Refunded amount') }}: $ {{ number_format((float) $order->refunded_amount, 2) }}"><span class="badge-dot"></span>{{ $paymentState->label() }}</span></div>
@endif
