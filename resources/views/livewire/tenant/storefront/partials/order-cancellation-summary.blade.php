{{--
    Shared cancellation / refunded banner (RETURN_EXCHANGE_REFUND_PLAN.md B.3.4).

        @include('livewire.tenant.storefront.partials.order-cancellation-summary')

    Variables:
        $order          Order (required)
        $cancellation   optional — OrderAfterSalesPresenter::cancellation($order) (OrderStatusPage passes it)
        $paymentState   optional — App\Enums\OrderPaymentStatus ($order->paymentState())

    Renders nothing unless the order is Cancelled / Rejected (red banner: reason, note, date, who)
    or Refunded (violet banner).
--}}
@php
    use App\Enums\OrderPaymentStatus;
    use App\Enums\OrderStatus;

    $__ui = \App\Support\Tenant\Storefront\AfterSalesUi::tokens();
    $__font = $__ui['font'] !== 'inherit' ? 'font-family:'.$__ui['font'].';' : '';
    $__cancel = $cancellation ?? \App\Support\Tenant\Storefront\OrderAfterSalesPresenter::cancellation($order);
    $__payment = ($paymentState ?? null) instanceof OrderPaymentStatus ? $paymentState : $order->paymentState();
    $__isRefundedOrder = $order->status === OrderStatus::Refunded;
@endphp

@if ($__cancel)
    <section class="px-5 py-4 flex flex-col gap-3" role="status" aria-labelledby="order-cancelled-title-{{ $order->id }}"
        style="background:#FEF2F2;border:1px solid #FECACA;border-radius:{{ $__ui['card_radius'] }};color:#7F1D1D;{{ $__font }}">
        <div class="flex items-start gap-3">
            <span class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center" style="background:#FEE2E2;color:#DC2626" aria-hidden="true">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/>
                </svg>
            </span>
            <div class="min-w-0 flex-1">
                <h2 id="order-cancelled-title-{{ $order->id }}" class="text-base font-semibold" style="color:#DC2626">
                    {{ $order->status === OrderStatus::Rejected ? __('This order was rejected by the store') : __('This order was cancelled') }}
                </h2>
                @if ($__payment === OrderPaymentStatus::Refunded)
                    <p class="text-sm mt-0.5">{{ __('Your payment has been refunded.') }}</p>
                @elseif ($order->isPaymentCollected() && $__payment !== OrderPaymentStatus::PartiallyRefunded)
                    <p class="text-sm mt-0.5">{{ __('Your payment will be refunded — see the refund status below.') }}</p>
                @endif
            </div>
        </div>

        <dl class="grid gap-x-6 gap-y-2 text-sm" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
            @if ($__cancel['reason_label'])
                <div>
                    <dt class="text-xs" style="color:#991B1B">{{ __('Reason') }}</dt>
                    <dd class="font-medium">{{ $__cancel['reason_label'] }}</dd>
                </div>
            @endif
            @if ($__cancel['cancelled_at'])
                <div>
                    <dt class="text-xs" style="color:#991B1B">{{ __('Cancelled on') }}</dt>
                    <dd class="font-medium">
                        <time datetime="{{ $__cancel['cancelled_at']->toIso8601String() }}">{{ $__cancel['cancelled_at']->translatedFormat('M j, Y H:i') }}</time>
                    </dd>
                </div>
            @endif
            @if ($__cancel['cancelled_by_label'])
                <div>
                    <dt class="text-xs" style="color:#991B1B">{{ __('Cancelled by') }}</dt>
                    <dd class="font-medium">{{ $__cancel['cancelled_by_label'] }}</dd>
                </div>
            @endif
        </dl>

        @if (filled($__cancel['note']))
            <div class="text-sm px-3 py-2 break-words" style="background:#FFFFFF;border:1px solid #FECACA;border-radius:10px">
                <span class="text-xs block mb-0.5" style="color:#991B1B">{{ __('Note') }}</span>
                {{ $__cancel['note'] }}
            </div>
        @endif
    </section>
@elseif ($__isRefundedOrder)
    @php $__violet = \App\Support\Tenant\Storefront\AfterSalesUi::badge('violet'); @endphp
    <section class="px-5 py-4 flex items-start gap-3" role="status"
        style="background:{{ $__violet['bg'] }};border:1px solid {{ $__violet['border'] }};border-radius:{{ $__ui['card_radius'] }};color:{{ $__violet['text'] }};{{ $__font }}">
        <span class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center bg-white" aria-hidden="true">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7L3 8m0-5v5h5"/>
            </svg>
        </span>
        <div class="min-w-0">
            <h2 class="text-base font-semibold">{{ __('This order has been refunded') }}</h2>
            @if ($order->refunded_at)
                <p class="text-sm mt-0.5">
                    {{ __('Refunded on :date', ['date' => $order->refunded_at->translatedFormat('M j, Y H:i')]) }}
                </p>
            @endif
        </div>
    </section>
@endif
