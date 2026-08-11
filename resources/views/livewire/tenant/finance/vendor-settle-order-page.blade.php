<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div>
            <a href="{{ route('tenant.finance.vendor-purchases') }}" class="btn btn-secondary btn-sm">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Purchases
            </a>
        </div>
    </div>

    <div class="vso-layout">

        {{-- ── Left: order info + breakdown ───────────────────────────── --}}
        <div class="page-stack">

            {{-- Order summary --}}
            <section class="card section-gap">
                <div class="locale-badge" style="margin-bottom:10px;">Order Summary</div>
                <div class="form-grid form-grid-2" style="gap:8px 16px;">
                    <div>
                        <div class="entity-subtitle">Order UUID</div>
                        <div class="entity-title" style="font-family:monospace;font-size:12px;word-break:break-all;">
                            {{ $order->uuid }}
                        </div>
                    </div>
                    <div>
                        <div class="entity-subtitle">Order Date</div>
                        <div class="entity-title">{{ $order->created_at?->format('M d, Y') }}</div>
                    </div>
                    <div>
                        <div class="entity-subtitle">Customer</div>
                        <div class="entity-title">{{ $order->customer?->full_name ?? 'Guest' }}</div>
                        @if ($order->customer?->email)
                            <div class="entity-subtitle">{{ $order->customer->email }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="entity-subtitle">Items</div>
                        <div class="entity-title">{{ $order->items_count }}</div>
                    </div>
                </div>
            </section>

            {{-- Cost breakdown --}}
            <section class="card section-gap">
                <div class="locale-badge" style="margin-bottom:10px;">Settlement Breakdown</div>
                <div class="vso-breakdown">
                    <div class="vso-row">
                        <span class="vso-label">Product Cost</span>
                        <span class="vso-value">${{ number_format((float) data_get($breakdown, 'product_cost', 0), 2) }}</span>
                    </div>
                    <div class="vso-row">
                        <span class="vso-label">Shipping Cost</span>
                        <span class="vso-value">${{ number_format((float) data_get($breakdown, 'shipping_cost', 0), 2) }}</span>
                    </div>
                    <div class="vso-row vso-subtotal">
                        <span class="vso-label">Subtotal</span>
                        <span class="vso-value">${{ number_format((float) data_get($breakdown, 'subtotal', 0), 2) }}</span>
                    </div>
                    <div class="vso-row {{ data_get($breakdown, 'gateway_fee', 0) > 0 ? 'vso-fee' : 'vso-fee vso-fee-zero' }}">
                        <span class="vso-label">
                            Gateway Fee
                            @if (!$selectedGateway)
                                <span style="font-size:11px;font-weight:400;color:var(--t3);margin-left:4px;">(select a gateway)</span>
                            @endif
                        </span>
                        <span class="vso-value">+${{ number_format((float) data_get($breakdown, 'gateway_fee', 0), 2) }}</span>
                    </div>
                    <div class="vso-row vso-total">
                        <span class="vso-label">Total to Pay</span>
                        <span class="vso-value">${{ number_format((float) data_get($breakdown, 'total', 0), 2) }}</span>
                    </div>
                </div>
            </section>

        </div>

        {{-- ── Right: gateway selection + card form + pay button ───────── --}}
        <div class="page-stack">

            <section class="card section-gap">
                <div class="locale-badge" style="margin-bottom:10px;">Payment Gateway</div>

                @if (!empty($gateways))
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        @foreach ($gateways as $gw)
                            <label wire:key="vso-gw-{{ $gw['id'] }}"
                                   style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;cursor:pointer;
                                          border:1.5px solid {{ $selectedGateway === $gw['code'] ? 'var(--accent)' : 'var(--border)' }};
                                          background:{{ $selectedGateway === $gw['code'] ? 'color-mix(in srgb,var(--accent) 8%,transparent)' : 'var(--elevated)' }};
                                          transition:border-color .15s,background .15s;">
                                <input type="radio"
                                       wire:click="selectGateway('{{ $gw['code'] }}')"
                                       name="vso_gateway_choice"
                                       value="{{ $gw['code'] }}"
                                       @checked($selectedGateway === $gw['code'])
                                       style="accent-color:var(--accent);width:15px;height:15px;flex-shrink:0;">
                                <svg style="width:16px;height:16px;color:var(--t2);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                <span style="font-size:13px;font-weight:600;color:var(--t1);flex:1;">{{ $gw['name'] }}</span>
                                @php
                                    $feeParts = [];
                                    if ($gw['fee_fixed'] > 0) $feeParts[] = '$' . number_format($gw['fee_fixed'], 2);
                                    if ($gw['fee_pct']   > 0) $feeParts[] = number_format($gw['fee_pct'], 2) . '%';
                                @endphp
                                @if (!empty($feeParts))
                                    <span style="font-size:11px;color:var(--t3);">{{ implode(' + ', $feeParts) }}</span>
                                @endif
                                @if ($selectedGateway === $gw['code'])
                                    <svg style="width:15px;height:15px;color:var(--accent);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    @error('selectedGateway')
                        <div class="field-error" style="margin-top:6px;">{{ $message }}</div>
                    @enderror
                @else
                    <div class="empty-state" style="padding:16px 0;">
                        <div class="empty-state-title">No payment gateways configured</div>
                        <p class="empty-state-copy">Please contact the platform administrator to enable payment gateways.</p>
                    </div>
                @endif
            </section>

            {{-- Inline card form --}}
            @if ($activeInlineGateway)
                <section class="card section-gap">
                    <div id="vso-inline-card-form"
                         data-gateway="{{ $activeInlineGateway['code'] }}"
                         @if ($activeInlineGateway['code'] === 'stripe')
                             data-stripe-key="{{ $activeInlineGateway['creds']['key'] ?? '' }}"
                         @elseif ($activeInlineGateway['code'] === 'authorize_net')
                             data-auth-login="{{ $activeInlineGateway['creds']['login_id'] ?? '' }}"
                             data-auth-client="{{ $activeInlineGateway['creds']['client_key'] ?? $activeInlineGateway['creds']['transaction_key'] ?? '' }}"
                             data-sandbox="{{ ($activeInlineGateway['mode'] ?? 'live') === 'test' ? '1' : '0' }}"
                         @elseif ($activeInlineGateway['code'] === '2checkout')
                             data-2co-seller="{{ $activeInlineGateway['creds']['seller_id'] ?? '' }}"
                         @endif
                    >
                        <div class="locale-badge" style="display:flex;align-items:center;gap:5px;margin-bottom:14px;">
                            <svg style="width:12px;height:12px;color:var(--green);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Secure card details
                        </div>

                        @if ($activeInlineGateway['code'] === 'stripe')
                            <div>
                                <label class="field-label">Card</label>
                                <div id="vso-stripe-card-element"
                                     style="border:1.5px solid var(--border);border-radius:8px;padding:11px 14px;background:var(--input-bg,var(--card));min-height:42px;">
                                </div>
                                <p id="vso-stripe-card-errors" class="field-error" style="display:none;margin-top:5px;"></p>
                                <input type="hidden" wire:model="stripeToken">
                            </div>
                        @else
                            <div class="page-stack" style="gap:10px;">
                                <div>
                                    <label class="field-label">Card number</label>
                                    <x-input type="text" id="vso-card-number" inputmode="numeric" autocomplete="cc-number"
                                             placeholder="1234  5678  9012  3456" />
                                </div>
                                <div class="form-grid form-grid-2" style="gap:10px;">
                                    <div>
                                        <label class="field-label">Expiry</label>
                                        <x-input type="text" id="vso-card-expiry" inputmode="numeric" autocomplete="cc-exp"
                                                 placeholder="MM / YY" maxlength="7" />
                                    </div>
                                    <div>
                                        <label class="field-label">CVC</label>
                                        <x-input type="text" id="vso-card-cvc" inputmode="numeric" autocomplete="cc-csc"
                                                 placeholder="•••" maxlength="4" />
                                    </div>
                                </div>
                            </div>
                            @if ($activeInlineGateway['code'] === 'authorize_net')
                                <input type="hidden" wire:model="authnetDesc">
                                <input type="hidden" wire:model="authnetValue">
                            @elseif ($activeInlineGateway['code'] === '2checkout')
                                <input type="hidden" wire:model="twocoToken">
                            @endif
                            <p id="vso-card-errors" class="field-error" style="display:none;margin-top:6px;"></p>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Pay button --}}
            @if (!empty($gateways))
                <div class="page-actions compact-actions justify-end" style="margin-top:0;">
                    <a href="{{ route('tenant.finance.vendor-purchases') }}" class="btn btn-secondary">Cancel</a>
                    <x-btn type="button" id="vso-pay-btn"
                           wire:loading.attr="disabled"
                           wire:loading.class="opacity-60 cursor-not-allowed"
                           wire:target="initiateGatewayPayment">
                        <span wire:loading.remove wire:target="initiateGatewayPayment">Proceed to Payment</span>
                        <span wire:loading wire:target="initiateGatewayPayment">Processing…</span>
                    </x-btn>
                </div>
            @endif

        </div>
    </div>

    {{-- External payment SDK scripts (loaded only when the gateway is available) --}}
    @if ($hasStripe)
        <script src="https://js.stripe.com/v3/"></script>
    @endif
    @if ($hasAuthorizeNet)
        <script src="{{ $authNetSandbox ? 'https://jstest.authorize.net/v1/Accept.js' : 'https://js.authorize.net/v1/Accept.js' }}" charset="utf-8"></script>
    @endif
    @if ($has2Checkout)
        <script src="https://2pay-js.2checkout.com/v1/2pay.js"></script>
    @endif

    @script
    <script>
        let _vsoStripe     = null;
        let _vsoStripeCard = null;

        function vsoInitPaymentForm() {
            const container = document.getElementById('vso-inline-card-form');
            if (!container || container.dataset.initialized) return;

            const gateway = container.dataset.gateway;

            if (gateway === 'stripe') {
                const stripeKey = container.dataset.stripeKey;
                if (!stripeKey || typeof Stripe === 'undefined') return;

                _vsoStripe     = Stripe(stripeKey);
                const elms     = _vsoStripe.elements();
                _vsoStripeCard = elms.create('card', {
                    style: {
                        base: {
                            fontSize: '13px',
                            color: '#333333',
                            fontFamily: 'inherit',
                            '::placeholder': { color: '#aaaaaa' },
                        },
                        invalid: { color: '#dc2626' },
                    },
                    hidePostalCode: true,
                });
                _vsoStripeCard.mount('#vso-stripe-card-element');
                _vsoStripeCard.on('change', (e) => {
                    const errEl = document.getElementById('vso-stripe-card-errors');
                    if (errEl) {
                        errEl.textContent = e.error?.message ?? '';
                        errEl.style.display = e.error ? '' : 'none';
                    }
                });
                container.dataset.initialized = '1';
            } else {
                vsoSetupCardFormatting();
                container.dataset.initialized = '1';
            }
        }

        function vsoSetupCardFormatting() {
            const numInput = document.getElementById('vso-card-number');
            if (numInput && !numInput.dataset.fmt) {
                numInput.dataset.fmt = '1';
                numInput.addEventListener('input', () => {
                    let v = numInput.value.replace(/\D/g, '').slice(0, 16);
                    numInput.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
                });
            }
            const expInput = document.getElementById('vso-card-expiry');
            if (expInput && !expInput.dataset.fmt) {
                expInput.dataset.fmt = '1';
                expInput.addEventListener('input', () => {
                    let v = expInput.value.replace(/\D/g, '').slice(0, 4);
                    if (v.length > 2) v = v.slice(0, 2) + ' / ' + v.slice(2);
                    expInput.value = v;
                });
            }
        }

        vsoInitPaymentForm();

        Livewire.on('vendorSettleGatewayChanged', () => {
            setTimeout(vsoInitPaymentForm, 30);
        });

        document.addEventListener('livewire:updated', () => {
            setTimeout(vsoInitPaymentForm, 30);
        });

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('#vso-pay-btn');
            if (!button) return;

            const inlineForm = document.getElementById('vso-inline-card-form');

            // No inline-card gateway active → call Livewire directly
            if (!inlineForm) {
                $wire.call('initiateGatewayPayment');
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            const gateway = inlineForm.dataset.gateway;
            button.disabled = true;

            // ── Stripe ────────────────────────────────────────────────────
            if (gateway === 'stripe') {
                if (!_vsoStripe || !_vsoStripeCard) {
                    alert('Stripe.js is still loading. Please try again in a moment.');
                    button.disabled = false;
                    return;
                }
                const { token, error } = await _vsoStripe.createToken(_vsoStripeCard);
                if (error) {
                    const errEl = document.getElementById('vso-stripe-card-errors');
                    if (errEl) { errEl.textContent = error.message; errEl.style.display = ''; }
                    button.disabled = false;
                    return;
                }
                await $wire.set('stripeToken', token.id);
                $wire.call('initiateGatewayPayment');
                return;
            }

            // ── Authorize.Net Accept.js ───────────────────────────────────
            if (gateway === 'authorize_net') {
                if (typeof Accept === 'undefined') {
                    alert('Accept.js is still loading. Please try again.');
                    button.disabled = false;
                    return;
                }
                const rawExp     = document.getElementById('vso-card-expiry')?.value.replace(/\s/g, '') ?? '';
                const [mon, yr]  = rawExp.split('/');
                const secureData = {
                    authData: {
                        apiLoginID: inlineForm.dataset.authLogin,
                        clientKey:  inlineForm.dataset.authClient,
                    },
                    cardData: {
                        cardNumber: document.getElementById('vso-card-number')?.value.replace(/\s/g, '') ?? '',
                        month:      (mon ?? '').trim(),
                        year:       '20' + (yr ?? '').trim(),
                        cardCode:   document.getElementById('vso-card-cvc')?.value ?? '',
                    },
                };
                Accept.dispatchData(secureData, async (response) => {
                    if (response.messages.resultCode === 'Error') {
                        const errEl = document.getElementById('vso-card-errors');
                        if (errEl) { errEl.textContent = response.messages.message?.[0]?.text ?? 'Card error'; errEl.style.display = ''; }
                        button.disabled = false;
                        return;
                    }
                    await $wire.set('authnetDesc',  response.opaqueData.dataDescriptor);
                    await $wire.set('authnetValue', response.opaqueData.dataValue);
                    $wire.call('initiateGatewayPayment');
                });
                return;
            }

            // ── 2Checkout 2Pay.js ─────────────────────────────────────────
            if (gateway === '2checkout') {
                if (typeof TCO === 'undefined') {
                    alert('2Pay.js is still loading. Please try again.');
                    button.disabled = false;
                    return;
                }
                const rawExp    = document.getElementById('vso-card-expiry')?.value.replace(/\s/g, '') ?? '';
                const [mon, yr] = rawExp.split('/');
                TCO.requestToken({
                    sellerId:       inlineForm.dataset['2coSeller'],
                    publishableKey: inlineForm.dataset['2coSeller'],
                    ccNo:           document.getElementById('vso-card-number')?.value.replace(/\s/g, '') ?? '',
                    cvv:            document.getElementById('vso-card-cvc')?.value ?? '',
                    expMonth:       (mon ?? '').trim(),
                    expYear:        '20' + (yr ?? '').trim(),
                }, async (data) => {
                    if (data.errorCode > 0) {
                        const errEl = document.getElementById('vso-card-errors');
                        if (errEl) { errEl.textContent = data.errorMsg ?? 'Card error'; errEl.style.display = ''; }
                        button.disabled = false;
                        return;
                    }
                    await $wire.set('twocoToken', data.token.token);
                    $wire.call('initiateGatewayPayment');
                });
                return;
            }

            // Fallback: redirect gateway
            $wire.call('initiateGatewayPayment');
        }, { capture: true });
    </script>
    @endscript

    <style>
        .vso-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            align-items: start;
        }
        @media (min-width: 900px) {
            .vso-layout { grid-template-columns: 1fr 1.2fr; }
        }
        .vso-breakdown { border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
        .vso-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            font-size: 13px;
            border-bottom: 1px solid var(--border);
        }
        .vso-row:last-child { border-bottom: none; }
        .vso-label { color: var(--t2); }
        .vso-value { font-weight: 600; color: var(--t1); font-variant-numeric: tabular-nums; }
        .vso-subtotal { background: var(--elevated); }
        .vso-fee .vso-value { color: var(--amber, #f59e0b); }
        .vso-fee-zero .vso-value { color: var(--t3); }
        .vso-total { background: color-mix(in srgb, var(--accent) 6%, transparent); }
        .vso-total .vso-label,
        .vso-total .vso-value { font-weight: 700; font-size: 14px; color: var(--t1); }
    </style>
</main>
