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
    </div>

    @if (session('purchase_error'))
        <div class="card section-gap notice-error">{{ session('purchase_error') }}</div>
    @endif
    @if ($errors->has('payment'))
        <div class="card section-gap notice-error">{{ $errors->first('payment') }}</div>
    @endif

    <section class="card table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Available Language Add-ons</h3>
                <p class="panel-copy">Purchase a language to unlock it for your storefront and admin panel.</p>
            </div>
        </div>

        @if ($availableLanguages->isNotEmpty())
            <x-table :headers="['Language', 'Direction', 'Price', 'Action']">
                @foreach ($availableLanguages as $language)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($language->imageFile)
                                    <img src="{{ $language->imageFile->full_path }}" alt="{{ $language->name }}"
                                        style="width:36px;height:24px;object-fit:cover;border-radius:4px;" />
                                @endif
                                <div>
                                    <div class="entity-title">{{ $language->name }}</div>
                                    <div class="entity-subtitle">{{ $language->native_name }} ·
                                        {{ strtoupper($language->code) }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ strtoupper($language->direction->value) }}</td>
                        <td>
                            <div class="entity-title">${{ number_format((float) $language->price, 2) }}</div>
                            <div class="entity-subtitle">One-time payment</div>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm"
                                wire:click="selectLanguage({{ $language->id }})">
                                Buy Language
                            </button>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="empty-state">
                <div class="empty-state-title">No languages available for purchase</div>
                <p class="empty-state-copy">You have already purchased all available paid language add-ons, or none have
                    been added by the platform yet.</p>
            </div>
        @endif
    </section>

    {{-- Payment gateway modal --}}
    @if ($showPaymentModal && $selectedLanguage)
        <x-modal wire:model="showPaymentModal"
                 title='Buy "{{ $selectedLanguage->name }}"'
                 closeAction="closeModal"
                 maxWidth="md">

            <div class="page-stack">

                {{-- ── Order summary banner ─────────────────────────────── --}}
                <div class="locale-fields-group" style="margin-bottom:4px;">
                    <div style="display:flex;align-items:center;gap:14px;">
                        @if ($selectedLanguage->imageFile)
                            <img src="{{ $selectedLanguage->imageFile->full_path }}"
                                 alt="{{ $selectedLanguage->name }}"
                                 style="width:44px;height:30px;object-fit:cover;border-radius:6px;flex-shrink:0;">
                        @else
                            <div style="width:44px;height:30px;border-radius:6px;background:var(--elevated);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                                <svg style="width:16px;height:16px;color:var(--t3);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                </svg>
                            </div>
                        @endif
                        <div style="flex:1;min-width:0;">
                            <div class="entity-title">{{ $selectedLanguage->name }}
                                <span class="badge badge-green" style="margin-left:6px;vertical-align:middle;">{{ strtoupper($selectedLanguage->code) }}</span>
                            </div>
                            <div class="entity-subtitle">One-time payment · {{ $selectedLanguage->native_name }}</div>
                        </div>
                        <div style="font-size:18px;font-weight:700;color:var(--accent);white-space:nowrap;">
                            ${{ number_format((float) $selectedLanguage->price, 2) }}
                        </div>
                    </div>
                </div>

                {{-- ── Gateway selection ────────────────────────────────── --}}
                <div>
                    <label class="field-label">Payment Gateway</label>
                    @if ($gateways->isNotEmpty())
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                            @foreach ($gateways as $gateway)
                                <label wire:key="bl-gw-{{ $gateway['id'] }}"
                                       class="bl-gw-option {{ $selectedGateway === $gateway['code'] ? 'is-selected' : '' }}"
                                       style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;cursor:pointer;border:1.5px solid {{ $selectedGateway === $gateway['code'] ? 'var(--accent)' : 'var(--border)' }};background:{{ $selectedGateway === $gateway['code'] ? 'color-mix(in srgb,var(--accent) 8%,transparent)' : 'var(--elevated)' }};transition:border-color .15s,background .15s;">
                                    <input type="radio"
                                           wire:click="selectGateway('{{ $gateway['code'] }}')"
                                           name="bl_payment_choice"
                                           value="{{ $gateway['code'] }}"
                                           @checked($selectedGateway === $gateway['code'])
                                           style="accent-color:var(--accent);width:15px;height:15px;flex-shrink:0;">
                                    <svg style="width:16px;height:16px;color:var(--t2);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    <span style="font-size:13px;font-weight:600;color:var(--t1);flex:1;">{{ $gateway['name'] }}</span>
                                    @if ($selectedGateway === $gateway['code'])
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
                            <p class="empty-state-copy">Please contact the platform administrator.</p>
                        </div>
                    @endif
                </div>

                {{-- ── Inline card panel (Stripe / AuthorizeNet / 2Checkout) ── --}}
                @if ($activeInlineGateway)
                    <div id="bl-inline-card-form"
                         class="locale-fields-group"
                         style="margin-top:0;"
                         data-gateway="{{ $activeInlineGateway['code'] }}"
                         @if ($activeInlineGateway['code'] === 'stripe')
                             data-stripe-key="{{ $activeInlineGateway['creds']['key'] ?? '' }}"
                         @elseif ($activeInlineGateway['code'] === 'authorize_net')
                             data-auth-login="{{ $activeInlineGateway['creds']['login_id'] ?? '' }}"
                             data-auth-client="{{ $activeInlineGateway['creds']['client_key'] ?? $activeInlineGateway['creds']['transaction_key'] ?? '' }}"
                             data-sandbox="{{ $activeInlineGateway['mode'] === 'test' ? '1' : '0' }}"
                         @elseif ($activeInlineGateway['code'] === '2checkout')
                             data-2co-seller="{{ $activeInlineGateway['creds']['seller_id'] ?? '' }}"
                         @endif
                    >
                        <div class="locale-badge" style="display:flex;align-items:center;gap:5px;">
                            <svg style="width:12px;height:12px;color:var(--green);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Secure card details
                        </div>

                        @if ($activeInlineGateway['code'] === 'stripe')
                            <div>
                                <label class="field-label">Card</label>
                                <div id="bl-stripe-card-element"
                                     style="border:1.5px solid var(--border);border-radius:8px;padding:11px 14px;background:var(--input-bg,var(--card));min-height:42px;">
                                </div>
                                <p id="bl-stripe-card-errors" class="field-error" style="display:none;margin-top:5px;"></p>
                                <input type="hidden" wire:model="stripeToken">
                            </div>
                        @else
                            <div class="form-grid form-grid-1" style="gap:10px;">
                                <div>
                                    <label class="field-label">Card number</label>
                                    <x-input type="text" id="bl-card-number" inputmode="numeric" autocomplete="cc-number"
                                             placeholder="1234  5678  9012  3456" />
                                </div>
                                <div class="form-grid form-grid-2" style="gap:10px;">
                                    <div>
                                        <label class="field-label">Expiry</label>
                                        <x-input type="text" id="bl-card-expiry" inputmode="numeric" autocomplete="cc-exp"
                                                 placeholder="MM / YY" maxlength="7" />
                                    </div>
                                    <div>
                                        <label class="field-label">CVC</label>
                                        <x-input type="text" id="bl-card-cvc" inputmode="numeric" autocomplete="cc-csc"
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
                            <p id="bl-card-errors" class="field-error" style="display:none;"></p>
                        @endif
                    </div>
                @endif

                {{-- ── Actions ──────────────────────────────────────────── --}}
                <div class="page-actions compact-actions justify-end">
                    <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                    @if ($gateways->isNotEmpty())
                        <x-btn type="button" id="bl-pay-btn"
                               wire:loading.attr="disabled"
                               wire:loading.class="opacity-60 cursor-not-allowed"
                               wire:target="initiatePayment">
                            <span wire:loading.remove wire:target="initiatePayment">Proceed to Payment</span>
                            <span wire:loading wire:target="initiatePayment">Processing…</span>
                        </x-btn>
                    @endif
                </div>

            </div>
        </x-modal>
    @endif

    {{-- External payment SDK scripts --}}
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
        let _blStripe     = null;
        let _blStripeCard = null;

        function blInitPaymentForm() {
            const container = document.getElementById('bl-inline-card-form');
            if (!container || container.dataset.initialized) return;

            const gateway = container.dataset.gateway;

            if (gateway === 'stripe') {
                const stripeKey = container.dataset.stripeKey;
                if (!stripeKey || typeof Stripe === 'undefined') return;

                _blStripe     = Stripe(stripeKey);
                const elms    = _blStripe.elements();
                _blStripeCard = elms.create('card', {
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
                _blStripeCard.mount('#bl-stripe-card-element');
                _blStripeCard.on('change', (e) => {
                    const errEl = document.getElementById('bl-stripe-card-errors');
                    if (errEl) {
                        errEl.textContent = e.error?.message ?? '';
                        errEl.style.display = e.error ? '' : 'none';
                    }
                });
                container.dataset.initialized = '1';
            } else {
                blSetupCardFormatting();
                container.dataset.initialized = '1';
            }
        }

        function blSetupCardFormatting() {
            const numInput = document.getElementById('bl-card-number');
            if (numInput && !numInput.dataset.fmt) {
                numInput.dataset.fmt = '1';
                numInput.addEventListener('input', () => {
                    let v = numInput.value.replace(/\D/g, '').slice(0, 16);
                    numInput.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
                });
            }
            const expInput = document.getElementById('bl-card-expiry');
            if (expInput && !expInput.dataset.fmt) {
                expInput.dataset.fmt = '1';
                expInput.addEventListener('input', () => {
                    let v = expInput.value.replace(/\D/g, '').slice(0, 4);
                    if (v.length > 2) v = v.slice(0, 2) + ' / ' + v.slice(2);
                    expInput.value = v;
                });
            }
        }

        blInitPaymentForm();

        Livewire.on('buyLanguagePaymentMethodChanged', () => {
            setTimeout(blInitPaymentForm, 30);
        });

        document.addEventListener('livewire:updated', () => {
            setTimeout(blInitPaymentForm, 30);
        });

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('#bl-pay-btn');
            if (!button) return;

            const inlineForm = document.getElementById('bl-inline-card-form');

            // No inline-card gateway active → call Livewire directly
            if (!inlineForm) {
                $wire.call('initiatePayment');
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            const gateway = inlineForm.dataset.gateway;
            button.disabled = true;

            // ── Stripe ────────────────────────────────────────────────────
            if (gateway === 'stripe') {
                if (!_blStripe || !_blStripeCard) {
                    alert('Stripe.js is still loading. Please try again in a moment.');
                    button.disabled = false;
                    return;
                }
                const { token, error } = await _blStripe.createToken(_blStripeCard);
                if (error) {
                    const errEl = document.getElementById('bl-stripe-card-errors');
                    if (errEl) { errEl.textContent = error.message; errEl.style.display = ''; }
                    button.disabled = false;
                    return;
                }
                await $wire.set('stripeToken', token.id);
                $wire.call('initiatePayment');
                return;
            }

            // ── Authorize.Net Accept.js ───────────────────────────────────
            if (gateway === 'authorize_net') {
                if (typeof Accept === 'undefined') {
                    alert('Accept.js is still loading. Please try again.');
                    button.disabled = false;
                    return;
                }
                const rawExp  = document.getElementById('bl-card-expiry')?.value.replace(/\s/g, '') ?? '';
                const [mon, yr] = rawExp.split('/');
                const secureData = {
                    authData: {
                        apiLoginID: inlineForm.dataset.authLogin,
                        clientKey:  inlineForm.dataset.authClient,
                    },
                    cardData: {
                        cardNumber: document.getElementById('bl-card-number')?.value.replace(/\s/g, '') ?? '',
                        month:      (mon ?? '').trim(),
                        year:       '20' + (yr ?? '').trim(),
                        cardCode:   document.getElementById('bl-card-cvc')?.value ?? '',
                    },
                };
                Accept.dispatchData(secureData, async (response) => {
                    if (response.messages.resultCode === 'Error') {
                        const errEl = document.getElementById('bl-card-errors');
                        if (errEl) { errEl.textContent = response.messages.message?.[0]?.text ?? 'Card error'; errEl.style.display = ''; }
                        button.disabled = false;
                        return;
                    }
                    await $wire.set('authnetDesc',  response.opaqueData.dataDescriptor);
                    await $wire.set('authnetValue', response.opaqueData.dataValue);
                    $wire.call('initiatePayment');
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
                const rawExp = document.getElementById('bl-card-expiry')?.value.replace(/\s/g, '') ?? '';
                const [mon, yr] = rawExp.split('/');
                TCO.requestToken({
                    sellerId:       inlineForm.dataset['2coSeller'],
                    publishableKey: inlineForm.dataset['2coSeller'],
                    ccNo:           document.getElementById('bl-card-number')?.value.replace(/\s/g, '') ?? '',
                    cvv:            document.getElementById('bl-card-cvc')?.value ?? '',
                    expMonth:       (mon ?? '').trim(),
                    expYear:        '20' + (yr ?? '').trim(),
                }, async (data) => {
                    if (data.errorCode > 0) {
                        const errEl = document.getElementById('bl-card-errors');
                        if (errEl) { errEl.textContent = data.errorMsg ?? 'Card error'; errEl.style.display = ''; }
                        button.disabled = false;
                        return;
                    }
                    await $wire.set('twocoToken', data.token.token);
                    $wire.call('initiatePayment');
                });
                return;
            }

            // Fallback: no token needed (redirect gateway)
            $wire.call('initiatePayment');
        }, { capture: true });
    </script>
    @endscript
</main>
