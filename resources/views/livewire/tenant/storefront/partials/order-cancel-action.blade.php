{{--
    Shared "Cancel order" action (RETURN_EXCHANGE_REFUND_PLAN.md B.8 Customer).

    Include from a theme's order page / account order list:
        @include('livewire.tenant.storefront.partials.order-cancel-action')

    Optional variables (all have fallbacks):
        $order            Order the trigger / notice is for (required for 'trigger' and 'notice')
        $cancelPart       'all' (default: notice + trigger + modal) | 'trigger' | 'notice' | 'modal'
        $cancelSize       'full' (default, full-width button) | 'compact' (pill, for order lists)
        $cancelDecision   App\Services\Orders\CancellationDecision (OrderStatusPage passes it)
        $canCancel        bool — the logged-in owner may cancel (OrderStatusPage passes it)
        $cancelViaModal   bool — the Livewire component uses ManagesOrderCancellation (OrderStatusPage /
                          ProfilePage). When false the trigger links to the order page, which opens the modal.
        $cancelReasons    value => label (customer reasons)

    The modal is driven by the component's public properties $cancelStep (0 closed, 1 reason,
    2 confirm), $cancelOrderUuid, $cancelReason and $cancelNote. ESC / backdrop / ✕ close it.
--}}
@php
    $__ui = \App\Support\Tenant\Storefront\AfterSalesUi::tokens();
    $__part = $cancelPart ?? 'all';
    $__size = $cancelSize ?? 'full';
    $__viaModal = (bool) ($cancelViaModal ?? false);
    $__order = $order ?? null;
    $__customer = auth('storefront')->user();
    $__isOwner = $__order && $__customer && (int) $__order->customer_id === (int) $__customer->id;

    $__decision = $cancelDecision ?? null;
    if ($__order && ! $__decision instanceof \App\Services\Orders\CancellationDecision) {
        $__decision = app(\App\Services\Orders\OrderCancellationPolicy::class)->evaluate(
            $__order,
            \App\Enums\CancellationActor::Customer,
            app(\App\Services\Orders\OrderPolicyService::class),
        );
    }
    $__canCancel = isset($canCancel) ? (bool) $canCancel && $__isOwner : ($__isOwner && $__decision?->allowed);

    $__reasons = $cancelReasons ?? \App\Enums\CancellationReason::options(\App\Enums\CancellationActor::Customer);
    $__step = (int) ($cancelStep ?? 0);
    $__modalUuid = $cancelOrderUuid ?? null;
    $__showModal = $__viaModal && $__step > 0 && $__modalUuid
        && ($__part === 'modal' || ($__order && $__modalUuid === $__order->uuid));
    $__selectedReason = $cancelReason ?? '';
    $__noteRequired = $__selectedReason === \App\Enums\CancellationReason::Other->value;
    $__font = $__ui['font'] !== 'inherit' ? 'font-family:'.$__ui['font'].';' : '';
@endphp

{{-- ── Notice: shipped (→ return after delivery) / no longer cancellable while processing ── --}}
@if (in_array($__part, ['all', 'notice'], true) && $__order && $__decision && ! $__decision->allowed && $__isOwner)
    @if ($__decision->code === \App\Services\Orders\CancellationDecision::SHIPPED)
        <div role="status" class="flex items-start gap-3 px-4 py-3 text-sm"
            style="background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF;border-radius:{{ $__ui['card_radius'] }};{{ $__font }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
            </svg>
            <span>{{ __('Already shipped — you can request a return after delivery.') }}</span>
        </div>
    @elseif ($__decision->code === \App\Services\Orders\CancellationDecision::PROCESSING_LOCKED)
        <div role="status" class="flex items-start gap-3 px-4 py-3 text-sm"
            style="background:{{ $__ui['surface'] }};border:1px solid {{ $__ui['border'] }};color:{{ $__ui['muted'] }};border-radius:{{ $__ui['card_radius'] }};{{ $__font }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
            </svg>
            <span>{{ $__decision->message }}</span>
        </div>
    @endif
@endif

{{-- ── Trigger ── --}}
@if (in_array($__part, ['all', 'trigger'], true) && $__order && $__canCancel)
    @php
        $__triggerClass = $__size === 'compact'
            ? 'inline-flex items-center justify-center gap-1.5 px-5 py-2.5 text-sm whitespace-nowrap transition-colors'
            : 'w-full flex items-center justify-center gap-2 px-6 py-3.5 text-base transition-colors';
        $__triggerStyle = 'border:1px solid #FECACA;color:#DC2626;background:#FFFFFF;border-radius:'.$__ui['button_radius'].';'.$__font;
    @endphp
    @if ($__viaModal)
        <button type="button" wire:click="openCancelModal('{{ $__order->uuid }}')"
            wire:loading.attr="disabled" wire:target="openCancelModal"
            aria-haspopup="dialog"
            class="{{ $__triggerClass }} hover:bg-red-50 disabled:opacity-60"
            style="{{ $__triggerStyle }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/>
            </svg>
            {{ __('Cancel order') }}
        </button>
    @else
        <a href="{{ route('tenant.storefront.order-status', ['uuid' => $__order->uuid, 'cancel' => 1]) }}"
            class="{{ $__triggerClass }} hover:bg-red-50"
            style="{{ $__triggerStyle }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/>
            </svg>
            {{ __('Cancel order') }}
        </a>
    @endif
@endif

{{-- ── Modal (step 1: reason + note, step 2: confirmation) ── --}}
@if (in_array($__part, ['all', 'modal'], true) && $__showModal)
    <div class="fixed inset-0 flex items-center justify-center p-4"
        style="z-index:60;{{ $__font }}"
        role="dialog" aria-modal="true" aria-labelledby="cancel-order-title" aria-describedby="cancel-order-desc"
        wire:key="cancel-order-modal-{{ $__modalUuid }}-{{ $__step }}"
        x-data
        x-init="document.body.style.overflow = 'hidden'; $nextTick(() => $el.querySelector('[data-autofocus]')?.focus())"
        x-on:keydown.escape.window="document.body.style.overflow = ''; $wire.closeCancelModal()"
        x-trap="true">

        <div class="absolute inset-0" style="background:rgba(0,0,0,.45)" aria-hidden="true"
            wire:click="closeCancelModal" x-on:click="document.body.style.overflow = ''"></div>

        <div class="relative w-full bg-white shadow-xl overflow-hidden"
            style="max-width:480px;border-radius:{{ $__ui['card_radius'] }};color:{{ $__ui['text'] }}">

            <div class="flex items-center justify-between gap-3 px-6 pt-5 pb-4" style="border-bottom:1px solid {{ $__ui['border'] }}">
                <h2 id="cancel-order-title" class="text-lg font-semibold">
                    {{ $__step === 2 ? __('Confirm cancellation') : __('Cancel order') }}
                </h2>
                <button type="button" wire:click="closeCancelModal" x-on:click="document.body.style.overflow = ''"
                    class="w-8 h-8 flex items-center justify-center rounded-full transition-colors hover:bg-gray-100"
                    style="border:1px solid {{ $__ui['border'] }};color:{{ $__ui['muted'] }}"
                    aria-label="{{ __('Close') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            @if ($__step === 1)
                <form wire:submit.prevent="continueCancel" class="px-6 pt-5 pb-6 flex flex-col gap-4" novalidate>
                    <p id="cancel-order-desc" class="text-sm" style="color:{{ $__ui['muted'] }}">
                        {{ __('Order #:order', ['order' => $__modalUuid]) }} — {{ __('Tell us why you want to cancel this order.') }}
                    </p>

                    <div class="flex flex-col gap-1.5">
                        <label for="cancel-order-reason" class="text-sm font-medium">
                            {{ __('Reason for cancelling') }} <span style="color:#DC2626" aria-hidden="true">*</span>
                        </label>
                        <select id="cancel-order-reason" wire:model.live="cancelReason" data-autofocus required
                            aria-required="true" @error('cancelReason') aria-invalid="true" aria-describedby="cancel-order-reason-error" @enderror
                            class="w-full px-4 py-2.5 text-sm bg-white focus:outline-none"
                            style="border:1px solid {{ $errors->has('cancelReason') ? '#DC2626' : '#D1D5DC' }};border-radius:12px">
                            <option value="">{{ __('Select a reason') }}</option>
                            @foreach ($__reasons as $__value => $__label)
                                <option value="{{ $__value }}">{{ $__label }}</option>
                            @endforeach
                        </select>
                        @error('cancelReason')
                            <p id="cancel-order-reason-error" class="text-xs" style="color:#DC2626" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="cancel-order-note" class="text-sm font-medium">
                            {{ __('Note') }}
                            @if ($__noteRequired)
                                <span style="color:#DC2626" aria-hidden="true">*</span>
                            @else
                                <span class="font-normal" style="color:{{ $__ui['muted'] }}">({{ __('optional') }})</span>
                            @endif
                        </label>
                        <textarea id="cancel-order-note" wire:model="cancelNote" rows="3" maxlength="1000"
                            @if ($__noteRequired) required aria-required="true" @endif
                            @error('cancelNote') aria-invalid="true" aria-describedby="cancel-order-note-error" @enderror
                            placeholder="{{ $__noteRequired ? __('Please tell us more about the reason for cancelling.') : __('Anything else we should know? (optional)') }}"
                            class="w-full px-4 py-3 text-sm resize-none focus:outline-none"
                            style="border:1px solid {{ $errors->has('cancelNote') ? '#DC2626' : '#D1D5DC' }};border-radius:12px"></textarea>
                        @error('cancelNote')
                            <p id="cancel-order-note-error" class="text-xs" style="color:#DC2626" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="button" wire:click="closeCancelModal" x-on:click="document.body.style.overflow = ''"
                            class="flex-1 py-3 text-sm font-medium transition-colors hover:bg-gray-50"
                            style="border:1px solid #D1D5DC;border-radius:{{ $__ui['button_radius'] }}">
                            {{ __('Keep order') }}
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="continueCancel"
                            class="flex-1 py-3 text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-60"
                            style="background:{{ $__ui['dark'] }};border-radius:{{ $__ui['button_radius'] }}">
                            {{ __('Continue') }}
                        </button>
                    </div>
                </form>
            @else
                <div class="px-6 pt-5 pb-6 flex flex-col gap-4">
                    <div class="flex items-start gap-3">
                        <span class="w-10 h-10 shrink-0 rounded-full flex items-center justify-center" style="background:#FEE2E2;color:#DC2626" aria-hidden="true">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            </svg>
                        </span>
                        <p id="cancel-order-desc" class="text-sm leading-6">
                            {{ __('Are you sure you want to cancel order #:order? This cannot be undone.', ['order' => $__modalUuid]) }}
                        </p>
                    </div>

                    <dl class="text-sm flex flex-col gap-1 px-4 py-3" style="background:{{ $__ui['surface'] }};border:1px solid {{ $__ui['border'] }};border-radius:12px">
                        <div class="flex gap-2">
                            <dt style="color:{{ $__ui['muted'] }}">{{ __('Reason') }}:</dt>
                            <dd class="font-medium">{{ $__reasons[$__selectedReason] ?? '—' }}</dd>
                        </div>
                        @if (filled($cancelNote ?? null))
                            <div class="flex gap-2">
                                <dt style="color:{{ $__ui['muted'] }}">{{ __('Note') }}:</dt>
                                <dd class="break-words min-w-0">{{ $cancelNote }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="flex gap-3 pt-1">
                        <button type="button" wire:click="backToCancelReason" data-autofocus
                            class="flex-1 py-3 text-sm font-medium transition-colors hover:bg-gray-50"
                            style="border:1px solid #D1D5DC;border-radius:{{ $__ui['button_radius'] }}">
                            {{ __('Back') }}
                        </button>
                        <button type="button" wire:click="confirmCancelOrder" x-on:click="document.body.style.overflow = ''"
                            wire:loading.attr="disabled" wire:target="confirmCancelOrder"
                            class="flex-1 py-3 text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-60"
                            style="background:#DC2626;border-radius:{{ $__ui['button_radius'] }}">
                            <span wire:loading.remove wire:target="confirmCancelOrder">{{ __('Confirm cancellation') }}</span>
                            <span wire:loading wire:target="confirmCancelOrder">{{ __('Cancelling…') }}</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
