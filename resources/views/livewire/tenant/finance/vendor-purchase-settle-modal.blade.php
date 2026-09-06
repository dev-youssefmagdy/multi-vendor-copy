{{--
Vendor Purchase – payment gateway settle modal content.

Variables injected via modalContentData:
  $breakdown           array { product_cost, shipping_cost, subtotal, gateway_fee, total }
  $gateways            array of central gateway data (id, code, name, mode, creds, fee_pct, fee_fixed)
  $selectedGateway     string  currently selected gateway code
  $activeInlineGateway array|null  gateway data when an inline-card gateway is active
  $authNetSandbox      bool
--}}

<div id="vps-modal-root" class="page-stack">

    {{-- ── Cost breakdown ──────────────────────────────────────────────── --}}
    <div class="settle-breakdown">
        <div class="settle-row">
            <span class="settle-label">Product Cost</span>
            <span class="settle-value">${{ number_format((float) data_get($breakdown, 'product_cost', 0), 2) }}</span>
        </div>
        <div class="settle-row">
            <span class="settle-label">Shipping Cost</span>
            <span class="settle-value">${{ number_format((float) data_get($breakdown, 'shipping_cost', 0), 2) }}</span>
        </div>
        <div class="settle-row settle-subtotal">
            <span class="settle-label">Subtotal</span>
            <span class="settle-value">${{ number_format((float) data_get($breakdown, 'subtotal', 0), 2) }}</span>
        </div>
        <div class="settle-row settle-fee {{ data_get($breakdown, 'gateway_fee', 0) > 0 ? '' : 'settle-fee-zero' }}">
            <span class="settle-label">Gateway Fee</span>
            <span class="settle-value">+${{ number_format((float) data_get($breakdown, 'gateway_fee', 0), 2) }}</span>
        </div>
        <div class="settle-row settle-total">
            <span class="settle-label">Total to Pay</span>
            <span class="settle-value">${{ number_format((float) data_get($breakdown, 'total', 0), 2) }}</span>
        </div>
    </div>

    {{-- ── Gateway selection ──────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Payment Gateway <span class="field-required">*</span></label>
        @if (!empty($gateways))
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                @foreach ($gateways as $gw)
                    <label wire:key="vps-gw-{{ $gw['id'] }}"
                           style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;cursor:pointer;
                                  border:1.5px solid {{ $selectedGateway === $gw['code'] ? 'var(--accent)' : 'var(--border)' }};
                                  background:{{ $selectedGateway === $gw['code'] ? 'color-mix(in srgb,var(--accent) 8%,transparent)' : 'var(--elevated)' }};
                                  transition:border-color .15s,background .15s;">
                        <input type="radio"
                               wire:click="selectSettleGateway('{{ $gw['code'] }}')"
                               name="vps_gateway_choice"
                               value="{{ $gw['code'] }}"
                               @checked($selectedGateway === $gw['code'])
                               style="accent-color:var(--accent);width:15px;height:15px;flex-shrink:0;">
                        <svg style="width:16px;height:16px;color:var(--t2);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span style="font-size:13px;font-weight:600;color:var(--t1);flex:1;">{{ $gw['name'] }}</span>
                        @if ($gw['fee_pct'] > 0 || $gw['fee_fixed'] > 0)
                            @php
                                $feeParts = [];
                                if ($gw['fee_fixed'] > 0) $feeParts[] = '$' . number_format($gw['fee_fixed'], 2);
                                if ($gw['fee_pct']  > 0) $feeParts[] = number_format($gw['fee_pct'], 2) . '%';
                            @endphp
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
            @error('settleGatewayCode')
                <div class="field-error" style="margin-top:6px;">{{ $message }}</div>
            @enderror
            <p class="text-xs text-gray-400 mt-1" style="font-size:12px;color:var(--t3);margin-top:6px;">
                Fee is recalculated automatically when you pick a gateway.
            </p>
        @else
            <div class="empty-state" style="padding:16px 0;">
                <div class="empty-state-title">No payment gateways configured</div>
                <p class="empty-state-copy">Please contact the platform administrator to enable payment gateways.</p>
            </div>
        @endif
    </div>

    {{-- ── Inline card panel (Stripe / AuthorizeNet / 2Checkout) ─────── --}}
    @if ($activeInlineGateway)
        <div id="vps-inline-card-form"
             class="locale-fields-group"
             style="margin-top:0;"
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
            <div class="locale-badge" style="display:flex;align-items:center;gap:5px;">
                <svg style="width:12px;height:12px;color:var(--green);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Secure card details
            </div>

            @if ($activeInlineGateway['code'] === 'stripe')
                <div>
                    <label class="field-label">Card</label>
                    <div id="vps-stripe-card-element"
                         style="border:1.5px solid var(--border);border-radius:8px;padding:11px 14px;background:var(--input-bg,var(--card));min-height:42px;">
                    </div>
                    <p id="vps-stripe-card-errors" class="field-error" style="display:none;margin-top:5px;"></p>
                    <input type="hidden" wire:model="settleStripeToken">
                </div>
            @else
                <div class="form-grid form-grid-1" style="gap:10px;">
                    <div>
                        <label class="field-label">Card number</label>
                        <x-input type="text" id="vps-card-number" inputmode="numeric" autocomplete="cc-number"
                                 placeholder="1234  5678  9012  3456" />
                    </div>
                    <div class="form-grid form-grid-2" style="gap:10px;">
                        <div>
                            <label class="field-label">Expiry</label>
                            <x-input type="text" id="vps-card-expiry" inputmode="numeric" autocomplete="cc-exp"
                                     placeholder="MM / YY" maxlength="7" />
                        </div>
                        <div>
                            <label class="field-label">CVC</label>
                            <x-input type="text" id="vps-card-cvc" inputmode="numeric" autocomplete="cc-csc"
                                     placeholder="•••" maxlength="4" />
                        </div>
                    </div>
                </div>
                @if ($activeInlineGateway['code'] === 'authorize_net')
                    <input type="hidden" wire:model="settleAuthnetDesc">
                    <input type="hidden" wire:model="settleAuthnetValue">
                @elseif ($activeInlineGateway['code'] === '2checkout')
                    <input type="hidden" wire:model="settleTwocoToken">
                @endif
                <p id="vps-card-errors" class="field-error" style="display:none;"></p>
            @endif
        </div>
    @endif

</div>{{-- #vps-modal-root --}}

@once
    @push('styles')
        <style>
            .settle-breakdown {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 10px;
                overflow: hidden;
            }
            .settle-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 16px;
                font-size: 13px;
                border-bottom: 1px solid var(--border);
            }
            .settle-row:last-child { border-bottom: none; }
            .settle-label { color: var(--t2); }
            .settle-value { font-weight: 600; color: var(--t1); }
            .settle-subtotal { background: var(--elevated); }
            .settle-fee .settle-value { color: var(--amber, #f59e0b); }
            .settle-fee-zero .settle-value { color: var(--t3); }
            .settle-total { background: color-mix(in srgb, var(--accent) 6%, transparent); }
            .settle-total .settle-label,
            .settle-total .settle-value { font-weight: 700; font-size: 14px; color: var(--t1); }
        </style>
    @endpush

    @push('scripts')
        <script>
        (function () {
            let _vpsStripe = null;
            let _vpsStripeCard = null;

            function vpsInitForm() {
                const container = document.getElementById('vps-inline-card-form');
                if (!container || container.dataset.initialized === '1') return;

                const gateway = container.dataset.gateway;

                if (gateway === 'stripe') {
                    const stripeKey = container.dataset.stripeKey;
                    if (!stripeKey || typeof Stripe === 'undefined') return;
                    _vpsStripe    = Stripe(stripeKey);
                    _vpsStripeCard = _vpsStripe.elements().create('card', {
                        style: {
                            base: { fontSize: '13px', color: '#333333', fontFamily: 'inherit', '::placeholder': { color: '#aaaaaa' } },
                            invalid: { color: '#dc2626' },
                        },
                        hidePostalCode: true,
                    });
                    _vpsStripeCard.mount('#vps-stripe-card-element');
                    _vpsStripeCard.on('change', (e) => {
                        const el = document.getElementById('vps-stripe-card-errors');
                        if (el) { el.textContent = e.error?.message ?? ''; el.style.display = e.error ? '' : 'none'; }
                    });
                } else {
                    vpsSetupCardFormatting();
                }

                container.dataset.initialized = '1';
            }

            function vpsSetupCardFormatting() {
                const numInput = document.getElementById('vps-card-number');
                if (numInput && !numInput.dataset.fmt) {
                    numInput.dataset.fmt = '1';
                    numInput.addEventListener('input', () => {
                        let v = numInput.value.replace(/\D/g, '').slice(0, 16);
                        numInput.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
                    });
                }
                const expInput = document.getElementById('vps-card-expiry');
                if (expInput && !expInput.dataset.fmt) {
                    expInput.dataset.fmt = '1';
                    expInput.addEventListener('input', () => {
                        let v = expInput.value.replace(/\D/g, '').slice(0, 4);
                        if (v.length > 2) v = v.slice(0, 2) + ' / ' + v.slice(2);
                        expInput.value = v;
                    });
                }
            }

            vpsInitForm();

            Livewire.on('vendorSettleGatewayChanged', () => {
                setTimeout(vpsInitForm, 30);
            });

            document.addEventListener('livewire:updated', () => {
                const container = document.getElementById('vps-inline-card-form');
                if (container) { container.removeAttribute('data-initialized'); }
                setTimeout(vpsInitForm, 30);
            });

            // Intercept form submit inside #vps-modal-root for inline-card gateways
            document.addEventListener('submit', async (event) => {
                const root = event.target.closest
                    ? event.target : null;
                // Only intercept if the form contains our modal root (list-page wraps content in form)
                if (!root || !root.querySelector('#vps-modal-root')) return;

                const inlineForm = document.getElementById('vps-inline-card-form');
                if (!inlineForm) return; // redirect gateway – let Livewire handle normally

                const gateway = inlineForm.dataset.gateway;
                const $wire = Livewire.find(root.closest('[wire\\:id]')?.getAttribute('wire:id'));
                if (!$wire) return;

                event.preventDefault();
                event.stopImmediatePropagation();

                // ── Stripe ─────────────────────────────────────────────
                if (gateway === 'stripe') {
                    if (!_vpsStripe || !_vpsStripeCard) {
                        alert('Stripe.js is still loading. Please try again in a moment.');
                        return;
                    }
                    const { token, error } = await _vpsStripe.createToken(_vpsStripeCard);
                    if (error) {
                        const el = document.getElementById('vps-stripe-card-errors');
                        if (el) { el.textContent = error.message; el.style.display = ''; }
                        return;
                    }
                    await $wire.set('settleStripeToken', token.id);
                    $wire.call('initiateGatewayPayment');
                    return;
                }

                // ── Authorize.Net ──────────────────────────────────────
                if (gateway === 'authorize_net') {
                    if (typeof Accept === 'undefined') { alert('Accept.js is still loading.'); return; }
                    const rawExp = document.getElementById('vps-card-expiry')?.value.replace(/\s/g, '') ?? '';
                    const [mon, yr] = rawExp.split('/');
                    const secureData = {
                        authData: { apiLoginID: inlineForm.dataset.authLogin, clientKey: inlineForm.dataset.authClient },
                        cardData: {
                            cardNumber: document.getElementById('vps-card-number')?.value.replace(/\s/g, '') ?? '',
                            month: (mon ?? '').trim(),
                            year: '20' + (yr ?? '').trim(),
                            cardCode: document.getElementById('vps-card-cvc')?.value ?? '',
                        },
                    };
                    Accept.dispatchData(secureData, async (response) => {
                        if (response.messages.resultCode === 'Error') {
                            const el = document.getElementById('vps-card-errors');
                            if (el) { el.textContent = response.messages.message?.[0]?.text ?? 'Card error'; el.style.display = ''; }
                            return;
                        }
                        await $wire.set('settleAuthnetDesc',  response.opaqueData.dataDescriptor);
                        await $wire.set('settleAuthnetValue', response.opaqueData.dataValue);
                        $wire.call('initiateGatewayPayment');
                    });
                    return;
                }

                // ── 2Checkout ──────────────────────────────────────────
                if (gateway === '2checkout') {
                    if (typeof TCO === 'undefined') { alert('2Pay.js is still loading.'); return; }
                    const rawExp = document.getElementById('vps-card-expiry')?.value.replace(/\s/g, '') ?? '';
                    const [mon, yr] = rawExp.split('/');
                    TCO.requestToken({
                        sellerId: inlineForm.dataset['2coSeller'],
                        publishableKey: inlineForm.dataset['2coSeller'],
                        ccNo: document.getElementById('vps-card-number')?.value.replace(/\s/g, '') ?? '',
                        cvv: document.getElementById('vps-card-cvc')?.value ?? '',
                        expMonth: (mon ?? '').trim(),
                        expYear: '20' + (yr ?? '').trim(),
                    }, async (data) => {
                        if (data.errorCode > 0) {
                            const el = document.getElementById('vps-card-errors');
                            if (el) { el.textContent = data.errorMsg ?? 'Card error'; el.style.display = ''; }
                            return;
                        }
                        await $wire.set('settleTwocoToken', data.token.token);
                        $wire.call('initiateGatewayPayment');
                    });
                    return;
                }

                // Fallback: just call Livewire directly
                $wire.call('initiateGatewayPayment');
            });
        })();
        </script>
    @endpush
@endonce
