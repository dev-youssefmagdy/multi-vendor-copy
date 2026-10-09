{{--
    Shared refund status cards (RETURN_EXCHANGE_REFUND_PLAN.md B.8 Customer).

        @include('livewire.tenant.storefront.partials.order-refunds-summary')

    Variables:
        $order              Order (required unless $refunds is given)
        $refunds            optional — list from OrderAfterSalesPresenter::refundsFor($order) / ::refunds()
        $paymentState       optional — App\Enums\OrderPaymentStatus
        $refundsTitle       optional heading (default "Refunds")
        $currentCurrency    optional — display currency (symbol + conversion rate)

    Each card: reference, amount, method, status badge, requested / completed dates and, for a
    rejected refund, the reason. Renders nothing when there are no refunds.
--}}
@php
    use App\Enums\OrderPaymentStatus;
    use App\Support\Tenant\Storefront\AfterSalesUi;

    $__ui = AfterSalesUi::tokens();
    $__font = $__ui['font'] !== 'inherit' ? 'font-family:'.$__ui['font'].';' : '';
    $__refunds = $refunds ?? (isset($order) ? \App\Support\Tenant\Storefront\OrderAfterSalesPresenter::refundsFor($order) : []);
    $__payment = ($paymentState ?? null) instanceof OrderPaymentStatus ? $paymentState : (isset($order) ? $order->paymentState() : null);
    $__currency = $currentCurrency ?? null;
    $__symbol = $__currency?->symbol ?? '$';
    $__rate = (float) ($__currency?->conversion_rate ?? 1.0);
    $__money = fn (float $v): string => $__symbol.number_format($v * $__rate, 2);
@endphp

@if (! empty($__refunds))
    <section class="bg-white px-5 py-4 flex flex-col gap-3" aria-labelledby="order-refunds-title-{{ isset($order) ? $order->id : 'list' }}"
        style="border:1px solid {{ $__ui['border'] }};border-radius:{{ $__ui['card_radius'] }};color:{{ $__ui['text'] }};{{ $__font }}">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <h2 id="order-refunds-title-{{ isset($order) ? $order->id : 'list' }}" class="text-base font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7L3 8m0-5v5h5"/>
                </svg>
                {{ $refundsTitle ?? __('Refunds') }}
            </h2>
            @if ($__payment && in_array($__payment, [OrderPaymentStatus::Refunded, OrderPaymentStatus::PartiallyRefunded], true))
                @php $__pb = AfterSalesUi::badge($__payment->color()); @endphp
                <span class="text-xs font-medium px-3 py-1 rounded-full"
                    style="background:{{ $__pb['bg'] }};color:{{ $__pb['text'] }};border:1px solid {{ $__pb['border'] }}">
                    {{ __('Payment') }}: {{ $__payment === OrderPaymentStatus::Refunded ? __('Refunded') : __('Partially refunded') }}
                </span>
            @endif
        </div>

        <ul class="flex flex-col gap-3" role="list">
            @foreach ($__refunds as $__refund)
                @php $__badge = AfterSalesUi::badge($__refund['status_color'] ?? null); @endphp
                <li class="px-4 py-3 flex flex-col gap-2" style="background:{{ $__ui['surface'] }};border:1px solid {{ $__ui['border'] }};border-radius:12px">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <p class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Reference') }}</p>
                            <p class="text-sm font-semibold break-all" dir="ltr">{{ $__refund['reference'] }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-base font-semibold">{{ $__money((float) $__refund['amount']) }}</span>
                            <span class="text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap"
                                style="background:{{ $__badge['bg'] }};color:{{ $__badge['text'] }};border:1px solid {{ $__badge['border'] }}">
                                {{ $__refund['status_label'] }}
                            </span>
                        </div>
                    </div>

                    <dl class="grid gap-x-6 gap-y-1 text-xs" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));color:{{ $__ui['muted'] }}">
                        @if ($__refund['method_label'])
                            <div>
                                <dt>{{ __('Refund method') }}</dt>
                                <dd class="text-sm" style="color:{{ $__ui['text'] }}">{{ $__refund['method_label'] }}</dd>
                            </div>
                        @endif
                        @if ($__refund['requested_at'])
                            <div>
                                <dt>{{ __('Requested on') }}</dt>
                                <dd class="text-sm" style="color:{{ $__ui['text'] }}">
                                    <time datetime="{{ $__refund['requested_at']->toIso8601String() }}">{{ $__refund['requested_at']->translatedFormat('M j, Y') }}</time>
                                </dd>
                            </div>
                        @endif
                        @if ($__refund['processed_at'])
                            <div>
                                <dt>{{ __('Completed on') }}</dt>
                                <dd class="text-sm" style="color:{{ $__ui['text'] }}">
                                    <time datetime="{{ $__refund['processed_at']->toIso8601String() }}">{{ $__refund['processed_at']->translatedFormat('M j, Y') }}</time>
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if (filled($__refund['rejection_reason'] ?? null))
                        <p class="text-xs px-3 py-2" style="background:#FEF2F2;color:#B91C1C;border-radius:8px">
                            <span class="font-semibold">{{ __('Reason') }}:</span> {{ $__refund['rejection_reason'] }}
                        </p>
                    @elseif (($__refund['status'] ?? null) === 'failed')
                        <p class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('The store is looking into this refund and will complete it shortly.') }}</p>
                    @elseif (in_array($__refund['status'] ?? null, ['pending', 'processing'], true))
                        <p class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('We will let you know as soon as your refund is completed.') }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
