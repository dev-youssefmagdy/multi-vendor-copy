{{--
    Shared return / exchange request form (RequestReturnForm). Every theme's pages/order-return
    includes this partial.

    Variables from RequestReturnForm: $order, $item, $reasons, $validationErrors and (Phase 5, all
    optional with fallbacks) $remaining, $returnMethods, $exchangeEnabled, $exchangeOptions,
    $selectedReason, $photosRequired, $descriptionRequired, $refundEstimate, plus the component's
    public properties ($quantity, $type, $returnMethod, $reason, $description, $customerNote,
    $replacementVariantId, $photos, $video).
--}}
@php
    $__ui = \App\Support\Tenant\Storefront\AfterSalesUi::tokens();
    $__font = $__ui['font'] !== 'inherit' ? 'font-family:'.$__ui['font'].';' : '';
    $product = $item?->product ?? $item?->variant?->product;
    $productName = $product?->translationValue('name') ?? $product?->slug ?? __('Item');
    $variantLabel = $item?->variant?->display_label;

    $__remaining = (int) ($remaining ?? ($item?->qty ?? 1));
    $__methods = $returnMethods ?? \App\Enums\ReturnMethod::cases();
    $__exchangeOptions = $exchangeOptions ?? [];
    $__exchangeEnabled = (bool) ($exchangeEnabled ?? false) && $__exchangeOptions !== [];
    $__photosRequired = (bool) ($photosRequired ?? false);
    $__descriptionRequired = (bool) ($descriptionRequired ?? false);
    $__estimate = $refundEstimate ?? null;
    $__isExchange = ($type ?? 'return') === \App\Enums\ReturnType::Exchange->value;
    $__qty = (int) ($quantity ?? 1);

    $__currency = $currentCurrency ?? null;
    $__symbol = $__currency?->symbol ?? '$';
    $__rate = (float) ($__currency?->conversion_rate ?? 1.0);
    $__money = fn (float $v): string => $__symbol.number_format($v * $__rate, 2);

    $__input = 'width:100%;border:1px solid #D1D5DC;border-radius:12px;background:#fff;';
    $__required = '<span style="color:#DC2626" aria-hidden="true">*</span><span style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">'.e(__('(required)')).'</span>';
    $__optional = '<span class="font-normal" style="color:'.$__ui['muted'].'">('.e(__('optional')).')</span>';
@endphp

<div class="max-w-[720px] mx-auto px-4 sm:px-6 py-10" style="{{ $__font }}color:{{ $__ui['text'] }}">
    <div class="mb-6">
        <a href="{{ route('tenant.storefront.order-status', $order->uuid) }}" class="text-sm hover:underline" style="color:{{ $__ui['muted'] }}">
            <span aria-hidden="true">&larr;</span> {{ __('Back to Order') }}
        </a>
        <h1 class="text-2xl font-semibold mt-3">{{ __('Request a Return') }}</h1>
        <p class="text-sm mt-1" style="color:{{ $__ui['muted'] }}">
            {{ __('Order #:uuid', ['uuid' => $order->uuid]) }} — {{ $productName }}@if ($variantLabel) · {{ $variantLabel }}@endif
        </p>
    </div>

    @if (! $item)
        <div class="text-sm px-4 py-3" role="alert" style="background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C;border-radius:12px">
            {{ __('Please choose an item from your order to return.') }}
        </div>
    @else
        @if ($errors->any())
            <div class="text-sm px-4 py-3 mb-4" role="alert" style="background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C;border-radius:12px">
                <ul class="list-disc" style="padding-inline-start:1rem">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($validationErrors))
            <div class="text-sm px-4 py-3 mb-4" role="alert" aria-live="polite" style="background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C;border-radius:12px">
                <ul class="list-disc" style="padding-inline-start:1rem">
                    @foreach ($validationErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form wire:submit.prevent="submit" class="bg-white p-6 flex flex-col gap-6" novalidate
            style="border:1px solid {{ $__ui['border'] }};border-radius:{{ $__ui['card_radius'] }}">

            {{-- Quantity --}}
            <div class="flex flex-col gap-2">
                <label for="return-quantity" class="text-sm font-medium">{{ __('Quantity to return') }} {!! $__required !!}</label>
                <div class="flex items-center gap-3">
                    <div class="inline-flex items-center" style="border:1px solid #D1D5DC;border-radius:{{ $__ui['button_radius'] }}">
                        <button type="button" wire:click="decrementQuantity" @disabled($__qty <= 1)
                            class="w-10 h-10 flex items-center justify-center text-lg disabled:opacity-40"
                            aria-label="{{ __('Decrease quantity') }}" aria-controls="return-quantity">&minus;</button>
                        <input id="return-quantity" type="number" inputmode="numeric" min="1" max="{{ max(1, $__remaining) }}"
                            wire:model.live.debounce.400ms="quantity"
                            class="w-14 h-10 text-center text-sm focus:outline-none" style="border:0;background:transparent"
                            aria-describedby="return-quantity-help">
                        <button type="button" wire:click="incrementQuantity" @disabled($__qty >= $__remaining)
                            class="w-10 h-10 flex items-center justify-center text-lg disabled:opacity-40"
                            aria-label="{{ __('Increase quantity') }}" aria-controls="return-quantity">+</button>
                    </div>
                    <span id="return-quantity-help" class="text-xs" style="color:{{ $__ui['muted'] }}">
                        {{ __('Up to :count unit(s) can be returned.', ['count' => $__remaining]) }}
                    </span>
                </div>
            </div>

            {{-- Resolution --}}
            <fieldset class="flex flex-col gap-2">
                <legend class="text-sm font-medium mb-2">{{ __('What would you like?') }} {!! $__required !!}</legend>
                <div class="grid gap-3" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
                    <label class="flex items-start gap-3 p-3 cursor-pointer"
                        style="border:1px solid {{ ! $__isExchange ? $__ui['accent'] : '#D1D5DC' }};border-radius:12px;background:{{ ! $__isExchange ? $__ui['accent_soft'] : '#fff' }}">
                        <input type="radio" wire:model.live="type" value="return" class="mt-1" style="accent-color:{{ $__ui['accent'] }}">
                        <span>
                            <span class="block text-sm font-medium">{{ __('Refund') }}</span>
                            <span class="block text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Send the item back and get your money back.') }}</span>
                        </span>
                    </label>
                    @if ($__exchangeEnabled)
                        <label class="flex items-start gap-3 p-3 cursor-pointer"
                            style="border:1px solid {{ $__isExchange ? $__ui['accent'] : '#D1D5DC' }};border-radius:12px;background:{{ $__isExchange ? $__ui['accent_soft'] : '#fff' }}">
                            <input type="radio" wire:model.live="type" value="exchange" class="mt-1" style="accent-color:{{ $__ui['accent'] }}">
                            <span>
                                <span class="block text-sm font-medium">{{ __('Exchange') }}</span>
                                <span class="block text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Swap it for another option of the same product.') }}</span>
                            </span>
                        </label>
                    @endif
                </div>

                @if ($__isExchange && $__exchangeEnabled)
                    <div class="flex flex-col gap-1.5 mt-2">
                        <label for="return-replacement" class="text-sm font-medium">{{ __('Replacement option') }} {!! $__required !!}</label>
                        <select id="return-replacement" wire:model.live="replacementVariantId" required aria-required="true"
                            class="px-3 py-2.5 text-sm focus:outline-none" style="{{ $__input }}">
                            <option value="">{{ __('Choose an option') }}</option>
                            @foreach ($__exchangeOptions as $__option)
                                <option value="{{ $__option['id'] }}">
                                    {{ $__option['label'] }} —
                                    {{ $__option['stock'] === null ? __('In stock') : __(':count in stock', ['count' => $__option['stock']]) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Only options with the same price are available for exchange.') }}</p>
                    </div>
                @endif
            </fieldset>

            {{-- Return method --}}
            <fieldset class="flex flex-col gap-2">
                <legend class="text-sm font-medium mb-2">{{ __('How will you return the item?') }} {!! $__required !!}</legend>
                <div class="flex flex-col gap-2">
                    @foreach ($__methods as $__method)
                        <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer"
                            style="border:1px solid {{ ($returnMethod ?? '') === $__method->value ? $__ui['accent'] : '#E5E7EB' }};border-radius:12px">
                            <input type="radio" wire:model.live="returnMethod" value="{{ $__method->value }}" style="accent-color:{{ $__ui['accent'] }}">
                            <span class="text-sm">{{ $__method->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            {{-- Reason --}}
            <div class="flex flex-col gap-1.5">
                <label for="return-reason" class="text-sm font-medium">{{ __('Reason') }} {!! $__required !!}</label>
                <select id="return-reason" wire:model.live="reason" required aria-required="true"
                    class="px-3 py-2.5 text-sm focus:outline-none" style="{{ $__input }}">
                    <option value="">{{ __('Select a reason') }}</option>
                    @foreach ($reasons as $r)
                        <option value="{{ $r->value }}">{{ $r->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Description --}}
            <div class="flex flex-col gap-1.5">
                <label for="return-description" class="text-sm font-medium">
                    {{ __('Description') }} {!! $__descriptionRequired ? $__required : $__optional !!}
                </label>
                <textarea id="return-description" wire:model.live.debounce.500ms="description" rows="4" maxlength="2000"
                    @if ($__descriptionRequired) required aria-required="true" @endif
                    aria-describedby="return-description-help"
                    class="px-3 py-2.5 text-sm focus:outline-none" style="{{ $__input }}"
                    placeholder="{{ __('Tell us more about the issue…') }}"></textarea>
                <p id="return-description-help" class="text-xs" style="color:{{ $__ui['muted'] }}">
                    {{ $__descriptionRequired ? __('Please describe the problem (at least 10 characters).') : __('Optional for this reason.') }}
                </p>
            </div>

            {{-- Photos --}}
            <div class="flex flex-col gap-1.5">
                <label for="return-photos" class="text-sm font-medium">
                    {{ __('Photos') }} {!! $__photosRequired ? $__required : $__optional !!}
                </label>
                <input id="return-photos" type="file" wire:model.live="photos" multiple accept="image/*" class="w-full text-sm"
                    @if ($__photosRequired) aria-required="true" @endif aria-describedby="return-photos-help">
                <p id="return-photos-help" class="text-xs" style="color:{{ $__ui['muted'] }}">
                    {{ $__photosRequired ? __('At least one photo is required as evidence.') : __('Photos help us process your request faster.') }}
                </p>
                <div wire:loading wire:target="photos" class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Uploading…') }}</div>
                @if (! empty($photos))
                    <div class="flex gap-2 mt-1 flex-wrap">
                        @foreach ($photos as $__i => $photo)
                            <div class="relative" wire:key="return-photo-{{ $__i }}">
                                <img src="{{ $photo->temporaryUrl() }}" alt="{{ __('Photo :n', ['n' => $__i + 1]) }}"
                                    class="w-16 h-16 object-cover" style="border:1px solid #E5E7EB;border-radius:10px">
                                <button type="button" wire:click="removePhoto({{ $__i }})"
                                    class="absolute w-5 h-5 rounded-full bg-white flex items-center justify-center text-xs shadow"
                                    style="top:-8px;inset-inline-end:-8px"
                                    aria-label="{{ __('Remove photo :n', ['n' => $__i + 1]) }}">&times;</button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Video --}}
            <div class="flex flex-col gap-1.5">
                <label for="return-video" class="text-sm font-medium">{{ __('Video') }} {!! $__optional !!}</label>
                <input id="return-video" type="file" wire:model.live="video" accept="video/*" class="w-full text-sm">
                <div wire:loading wire:target="video" class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('Uploading…') }}</div>
            </div>

            {{-- Additional notes --}}
            <div class="flex flex-col gap-1.5">
                <label for="return-note" class="text-sm font-medium">{{ __('Additional notes') }} {!! $__optional !!}</label>
                <textarea id="return-note" wire:model.live.debounce.500ms="customerNote" rows="2" maxlength="1000"
                    class="px-3 py-2.5 text-sm focus:outline-none" style="{{ $__input }}"
                    placeholder="{{ __('Pickup times, packaging details…') }}"></textarea>
            </div>

            {{-- Estimated refund --}}
            @if (! $__isExchange)
                <div class="px-4 py-3 flex flex-col gap-1.5 text-sm" aria-live="polite"
                    style="background:{{ $__ui['surface'] }};border:1px solid {{ $__ui['border'] }};border-radius:12px">
                    <p class="font-medium">{{ __('Estimated refund') }}</p>
                    @if ($__estimate)
                        <div class="flex justify-between gap-3">
                            <span style="color:{{ $__ui['muted'] }}">{{ __('Items') }}</span>
                            <span>{{ $__money($__estimate->itemsAmount) }}</span>
                        </div>
                        @if ($__estimate->shippingAmount > 0)
                            <div class="flex justify-between gap-3">
                                <span style="color:{{ $__ui['muted'] }}">{{ __('Shipping') }}</span>
                                <span>{{ $__money($__estimate->shippingAmount) }}</span>
                            </div>
                        @endif
                        @if ($__estimate->returnFee > 0)
                            <div class="flex justify-between gap-3">
                                <span style="color:{{ $__ui['muted'] }}">{{ __('Return fee') }}</span>
                                <span>-{{ $__money($__estimate->returnFee) }}</span>
                            </div>
                        @elseif ($__estimate->feeWaived)
                            <p class="text-xs" style="color:#15803D">{{ __('The return fee is waived for this reason.') }}</p>
                        @endif
                        <div class="flex justify-between gap-3 pt-1.5 mt-0.5 font-semibold" style="border-top:1px solid {{ $__ui['border'] }}">
                            <span>{{ __('Total') }}</span>
                            <span>{{ $__money($__estimate->amount) }}</span>
                        </div>
                        <p class="text-xs" style="color:{{ $__ui['muted'] }}">{{ __('The final amount is confirmed after the store inspects the returned item.') }}</p>
                    @else
                        <p class="text-xs" style="color:{{ $__ui['muted'] }}">
                            {{ $order->isPaymentCollected() ? __('Choose a reason to see your estimated refund.') : __('No payment was collected for this order, so there is nothing to refund.') }}
                        </p>
                    @endif
                </div>
            @endif

            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                class="text-white font-medium text-base px-8 py-3 transition-opacity hover:opacity-90 disabled:opacity-60"
                style="background:{{ $__ui['accent'] }};border-radius:{{ $__ui['button_radius'] }}">
                <span wire:loading.remove wire:target="submit">{{ $__isExchange ? __('Submit Exchange Request') : __('Submit Return Request') }}</span>
                <span wire:loading wire:target="submit">{{ __('Submitting…') }}</span>
            </button>
        </form>
    @endif

    @include('livewire.tenant.storefront.partials.after-sales-toast', ['toastEvent' => 'order-status-swal'])
</div>
