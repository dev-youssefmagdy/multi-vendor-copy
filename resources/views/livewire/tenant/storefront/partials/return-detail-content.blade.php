{{--
    Shared return / exchange request detail (ReturnDetailPage). Every theme's pages/return-detail
    includes this partial.

    $returnRecord keys: id, order_number, status, status_label, status_color, reason, description,
    refund_amount, created_at, media, notes, can_reply, can_withdraw and (Phase 5, optional)
    type, type_label, is_exchange, quantity, product_name, variant_label, return_method_label,
    customer_note, replacement_label, replacement_quantity, inspection_label, inspection_color,
    exchange_tracking_number, exchange_shipped_at, timeline, refunds.
--}}
@php
    use App\Support\Tenant\Storefront\AfterSalesUi;
    use App\Support\Tenant\Storefront\ReturnTimeline;

    $record = $returnRecord;
    $__ui = AfterSalesUi::tokens();
    $__font = $__ui['font'] !== 'inherit' ? 'font-family:'.$__ui['font'].';' : '';
    $__badge = AfterSalesUi::badge($record['status_color'] ?? null);
    $__isExchange = (bool) ($record['is_exchange'] ?? false);
    $__timeline = $record['timeline'] ?? [];
    $__refunds = $record['refunds'] ?? [];
    $__card = 'background:#fff;border:1px solid '.$__ui['border'].';border-radius:'.$__ui['card_radius'].';';
@endphp

<div class="max-w-[720px] mx-auto px-4 sm:px-6 py-10" style="{{ $__font }}color:{{ $__ui['text'] }}">
    <a href="{{ route('tenant.storefront.profile', ['tab' => 'returns']) }}"
        class="text-sm hover:underline mb-6 inline-block" style="color:{{ $__ui['muted'] }}">
        <span aria-hidden="true">&larr;</span> {{ __('Back to Returns') }}
    </a>

    <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h1 class="text-2xl font-semibold">
                {{ $__isExchange ? __('Exchange Request #:id', ['id' => $record['id']]) : __('Return Request #:id', ['id' => $record['id']]) }}
            </h1>
            <p class="text-sm mt-1" style="color:{{ $__ui['muted'] }}">
                <a href="{{ route('tenant.storefront.order-status', $record['order_number']) }}" class="hover:underline">{{ __('Order #:order', ['order' => $record['order_number']]) }}</a>
                · {{ $record['created_at'] }}
            </p>
        </div>
        <span class="text-sm font-semibold px-4 py-1.5 rounded-full"
            style="background:{{ $__badge['bg'] }};color:{{ $__badge['text'] }};border:1px solid {{ $__badge['border'] }}">
            {{ $record['status_label'] }}
        </span>
    </div>

    {{-- Progress --}}
    @if (! empty($__timeline))
        <section class="p-5 mb-4" style="{{ $__card }}" aria-labelledby="return-progress-title">
            <h2 id="return-progress-title" class="text-sm font-semibold mb-4">{{ __('Progress') }}</h2>
            <ol class="flex flex-col" role="list">
                @foreach ($__timeline as $__step)
                    @php
                        $__state = $__step['state'];
                        $__dot = match ($__state) {
                            ReturnTimeline::DONE => 'background:'.$__ui['accent'].';color:#fff;border:2px solid '.$__ui['accent'],
                            ReturnTimeline::CURRENT => 'background:#fff;color:'.$__ui['accent'].';border:2px solid '.$__ui['accent'],
                            ReturnTimeline::STOPPED => 'background:#DC2626;color:#fff;border:2px solid #DC2626',
                            default => 'background:#fff;color:#9CA3AF;border:2px solid #E5E7EB',
                        };
                    @endphp
                    <li class="flex gap-3" @if ($__state === ReturnTimeline::CURRENT) aria-current="step" @endif>
                        <div class="flex flex-col items-center">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs shrink-0" style="{{ $__dot }}" aria-hidden="true">
                                @if ($__state === ReturnTimeline::DONE)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @elseif ($__state === ReturnTimeline::STOPPED)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" d="M18 6 6 18M6 6l12 12"/></svg>
                                @else
                                    <span class="w-2 h-2 rounded-full" style="background:currentColor"></span>
                                @endif
                            </span>
                            @if (! $loop->last)
                                <span class="flex-1" style="width:2px;min-height:20px;background:{{ $__state === ReturnTimeline::DONE ? $__ui['accent'] : '#E5E7EB' }}"></span>
                            @endif
                        </div>
                        <div class="pb-4 min-w-0">
                            <p class="text-sm {{ $__state === ReturnTimeline::UPCOMING ? '' : 'font-medium' }}"
                                style="color:{{ $__state === ReturnTimeline::UPCOMING ? '#9CA3AF' : ($__state === ReturnTimeline::STOPPED ? '#DC2626' : $__ui['text']) }}">
                                {{ $__step['label'] }}
                                @if ($__state === ReturnTimeline::CURRENT)
                                    <span class="text-xs font-normal" style="color:{{ $__ui['muted'] }}">· {{ $record['status_label'] }}</span>
                                @endif
                            </p>
                            @if ($__step['date'])
                                <p class="text-xs" style="color:{{ $__ui['muted'] }}">
                                    <time datetime="{{ $__step['date']->toIso8601String() }}">{{ $__step['date']->translatedFormat('M j, Y H:i') }}</time>
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- Request details --}}
    <section class="p-5 mb-4" style="{{ $__card }}" aria-labelledby="return-details-title">
        <h2 id="return-details-title" class="text-sm font-semibold mb-3">{{ __('Request details') }}</h2>
        <dl class="grid gap-x-6 gap-y-3 text-sm" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
            @if (! empty($record['type_label']))
                <div>
                    <dt class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Type') }}</dt>
                    <dd class="font-medium">{{ $record['type_label'] }}</dd>
                </div>
            @endif
            @if (! empty($record['product_name']))
                <div>
                    <dt class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Item') }}</dt>
                    <dd class="font-medium">{{ $record['product_name'] }}@if (! empty($record['variant_label'])) · {{ $record['variant_label'] }}@endif</dd>
                </div>
            @endif
            @if (! empty($record['quantity']))
                <div>
                    <dt class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Quantity') }}</dt>
                    <dd class="font-medium">{{ $record['quantity'] }}</dd>
                </div>
            @endif
            @if (! empty($record['return_method_label']))
                <div>
                    <dt class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Return method') }}</dt>
                    <dd class="font-medium">{{ $record['return_method_label'] }}</dd>
                </div>
            @endif
            @if ($__isExchange && ! empty($record['replacement_label']))
                <div>
                    <dt class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Replacement') }}</dt>
                    <dd class="font-medium">{{ $record['replacement_label'] }} × {{ $record['replacement_quantity'] ?? $record['quantity'] ?? 1 }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Reason') }}</dt>
                <dd class="font-medium">{{ $record['reason'] }}</dd>
            </div>
        </dl>
        @if ($record['description'])
            <div class="text-sm mt-4 break-words">
                <p class="text-xs mb-0.5" style="color:{{ $__ui['muted'] }}">{{ __('Description') }}</p>
                {{ $record['description'] }}
            </div>
        @endif
        @if (! empty($record['customer_note']))
            <div class="text-sm mt-3 break-words">
                <p class="text-xs mb-0.5" style="color:{{ $__ui['muted'] }}">{{ __('Additional notes') }}</p>
                {{ $record['customer_note'] }}
            </div>
        @endif
    </section>

    {{-- Inspection --}}
    @if (! empty($record['inspection_label']))
        @php $__ib = AfterSalesUi::badge($record['inspection_color'] ?? null); @endphp
        <div class="px-5 py-4 mb-4 text-sm font-medium flex items-center gap-2" role="status"
            style="background:{{ $__ib['bg'] }};border:1px solid {{ $__ib['border'] }};color:{{ $__ib['text'] }};border-radius:{{ $__ui['card_radius'] }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5 2a8 8 0 11-16 0 8 8 0 0116 0z"/>
            </svg>
            {{ $record['inspection_label'] }}
        </div>
    @endif

    {{-- Replacement shipment --}}
    @if (! empty($record['exchange_tracking_number']))
        <div class="px-5 py-4 mb-4 text-sm" style="{{ $__card }}">
            <p class="font-semibold mb-1">{{ __('Your replacement is on its way') }}</p>
            <p>
                <span style="color:{{ $__ui['muted'] }}">{{ __('Tracking number') }}:</span>
                <span class="font-medium break-all" dir="ltr">{{ $record['exchange_tracking_number'] }}</span>
                @if (! empty($record['exchange_shipped_at']))
                    <span style="color:{{ $__ui['muted'] }}">· {{ $record['exchange_shipped_at'] }}</span>
                @endif
            </p>
        </div>
    @endif

    {{-- Refunds --}}
    @if (! empty($__refunds))
        <div class="mb-4">
            @include('livewire.tenant.storefront.partials.order-refunds-summary', [
                'refunds' => $__refunds,
                'refundsTitle' => __('Refund'),
                'paymentState' => null,
            ])
        </div>
    @elseif ($record['refund_amount'])
        <div class="px-4 py-3 mb-4 text-sm font-semibold" style="background:#F0FDF4;border:1px solid #BBF7D0;color:#15803D;border-radius:{{ $__ui['card_radius'] }}">
            {{ __('Refund Issued: :amount', ['amount' => number_format((float) $record['refund_amount'], 2)]) }}
        </div>
    @endif

    @if (! empty($record['media']))
        <section class="p-5 mb-4" style="{{ $__card }}">
            <h2 class="text-sm mb-3" style="color:{{ $__ui['muted'] }}">{{ __('Evidence') }}</h2>
            <div class="flex gap-3 flex-wrap">
                @foreach ($record['media'] as $m)
                    @if ($m['type'] === 'photo')
                        <a href="{{ $m['url'] }}" target="_blank" rel="noopener">
                            <img src="{{ $m['url'] }}" alt="{{ __('Evidence photo') }}" class="w-20 h-20 object-cover" style="border:1px solid #E5E7EB;border-radius:10px">
                        </a>
                    @else
                        <a href="{{ $m['url'] }}" target="_blank" rel="noopener" class="flex items-center gap-2 text-xs font-medium underline">
                            {{ __('Video') }}
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    @if (! empty($record['notes']))
        <section class="p-5 mb-4" style="{{ $__card }}">
            <h2 class="text-sm mb-3" style="color:{{ $__ui['muted'] }}">{{ __('Messages') }}</h2>
            <div class="flex flex-col gap-4">
                @foreach ($record['notes'] as $note)
                    @php $__mine = $note['author_type'] === 'customer'; @endphp
                    <div style="{{ $__mine ? 'margin-inline-start:1.5rem' : 'margin-inline-end:1.5rem' }}">
                        <div class="text-xs font-semibold mb-1" style="color:{{ $__mine ? $__ui['accent'] : '#555' }}">
                            {{ $note['author'] }}
                            <span class="font-normal" style="color:{{ $__ui['muted'] }}">· {{ $note['created_at'] }}</span>
                        </div>
                        <div class="text-sm rounded-lg p-3 break-words" style="background:#F9FAFB">
                            {{ $note['note'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($errors->any())
        <div class="text-sm px-4 py-3 mb-4" role="alert" style="background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C;border-radius:12px">
            <ul class="list-disc" style="padding-inline-start:1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($record['can_reply'])
        <section class="p-5 mb-4" style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:{{ $__ui['card_radius'] }}">
            <p class="text-sm font-semibold mb-4" style="color:#B45309">
                {{ __('The team has requested more information. Please reply below.') }}
            </p>

            <form wire:submit.prevent="submitReply" class="flex flex-col gap-4">
                <div>
                    <label for="return-reply" class="block text-sm font-medium mb-1">{{ __('Your Reply') }}</label>
                    <textarea id="return-reply" wire:model="replyText" rows="4" required aria-required="true" maxlength="2000"
                        class="w-full px-3 py-2.5 text-sm" style="border:1px solid #D1D5DC;border-radius:12px;background:#fff"
                        placeholder="{{ __('Provide the requested information…') }}"></textarea>
                </div>
                <div>
                    <label for="return-reply-photos" class="block text-sm font-medium mb-1">{{ __('Additional Photos (optional)') }}</label>
                    <input id="return-reply-photos" type="file" wire:model="replyPhotos" multiple accept="image/*" class="text-sm w-full">
                </div>
                <div>
                    <label for="return-reply-video" class="block text-sm font-medium mb-1">{{ __('Video (optional)') }}</label>
                    <input id="return-reply-video" type="file" wire:model="replyVideo" accept="video/*" class="text-sm w-full">
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="submitReply"
                    class="self-start text-white text-sm font-semibold px-6 py-2.5 transition-opacity hover:opacity-90 disabled:opacity-60"
                    style="background:{{ $__ui['accent'] }};border-radius:{{ $__ui['button_radius'] }}">
                    {{ __('Submit Reply') }}
                </button>
            </form>
        </section>
    @endif

    @if ($record['can_withdraw'])
        <section class="mt-2">
            @if ($confirmingWithdraw ?? false)
                <div class="p-5 flex flex-col gap-4" role="alertdialog" aria-modal="false" aria-labelledby="withdraw-title" aria-describedby="withdraw-desc"
                    style="background:#FEF2F2;border:1px solid #FECACA;border-radius:{{ $__ui['card_radius'] }}"
                    x-data x-init="$nextTick(() => $el.querySelector('[data-autofocus]')?.focus())"
                    x-on:keydown.escape="$wire.cancelWithdraw()">
                    <div>
                        <p id="withdraw-title" class="text-sm font-semibold" style="color:#B91C1C">{{ __('Withdraw this request?') }}</p>
                        <p id="withdraw-desc" class="text-sm mt-1">{{ __('Are you sure you want to withdraw this request? This cannot be undone.') }}</p>
                    </div>
                    <div class="flex gap-3 flex-wrap">
                        <button type="button" wire:click="cancelWithdraw" data-autofocus
                            class="px-6 py-2.5 text-sm font-medium bg-white transition-colors hover:bg-gray-50"
                            style="border:1px solid #D1D5DC;border-radius:{{ $__ui['button_radius'] }}">
                            {{ __('Keep request') }}
                        </button>
                        <button type="button" wire:click="withdraw" wire:loading.attr="disabled" wire:target="withdraw"
                            class="px-6 py-2.5 text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-60"
                            style="background:#DC2626;border-radius:{{ $__ui['button_radius'] }}">
                            {{ __('Yes, withdraw') }}
                        </button>
                    </div>
                </div>
            @else
                <button type="button" wire:click="askWithdraw"
                    class="px-6 py-2.5 text-sm font-medium transition-colors hover:bg-red-50"
                    style="border:1px solid #FECACA;color:#DC2626;background:#fff;border-radius:{{ $__ui['button_radius'] }}">
                    {{ __('Withdraw request') }}
                </button>
            @endif
        </section>
    @endif

    @include('livewire.tenant.storefront.partials.after-sales-toast', ['toastEvent' => 'return-detail-swal'])
</div>
