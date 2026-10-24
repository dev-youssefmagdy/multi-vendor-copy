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
    @if (session('purchase_success'))
        <div class="card section-gap notice-success">{{ session('purchase_success') }}</div>
    @endif
    @if ($errors->has('payment'))
        <div class="card section-gap notice-error">{{ $errors->first('payment') }}</div>
    @endif

    @if (!$canUseAi)
        <div class="card section-gap notice-warning">
            AI translation is not enabled on your current plan. Upgrade to access this feature.
        </div>
    @else
        <div class="section-gap" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px" @if($polling) wire:poll.3s="$refresh" @endif>
            @foreach ($cards as $card)
                <div class="card fu d2" style="padding:20px;display:flex;flex-direction:column;gap:12px" wire:key="ai-lang-{{ $card['id'] }}">
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="font-size:22px;font-weight:700;color:var(--accent)">{{ strtoupper($card['code']) }}</div>
                        <div>
                            <div class="entity-title">{{ $card['name'] }}</div>
                            <div class="entity-subtitle" style="font-size:11px">{{ $card['native_name'] }}</div>
                        </div>
                        @if (!$card['is_active'])
                            <span class="badge badge-gray" style="margin-left:auto;font-size:10px">Not active</span>
                        @endif
                    </div>

                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                        @if ($card['is_free'])
                            <span class="badge badge-green">Free</span>
                        @else
                            <span class="badge badge-violet">${{ number_format((float) $card['price'], 2) }}</span>
                        @endif

                        @if ($card['last_status'] === 'completed')
                            <span class="badge badge-blue" style="font-size:10px">Last run: {{ $card['last_run'] }}</span>
                        @elseif ($card['last_status'] === 'pending')
                            <span class="badge badge-amber" style="font-size:10px">Running…</span>
                        @elseif ($card['last_status'] === 'failed')
                            <span class="badge badge-red" style="font-size:10px">Last run failed</span>
                        @endif
                    </div>

                    <p class="panel-copy" style="font-size:11px;margin:0">
                        Translates all products, categories, banners and UI strings into {{ $card['name'] }},
                        adapted to your store brand.
                    </p>

                    @if (in_array($card['translation_status'], ['queued', 'running']))
                        <div>
                            <div class="progress-track" style="height:8px;border-radius:999px;background:var(--border);overflow:hidden;">
                                <div style="height:100%;width:{{ $card['translation_progress'] }}%;background:var(--primary,#FF4B2B);transition:width .3s;"></div>
                            </div>
                            <p class="panel-copy" style="margin:4px 0 0;font-size:11px;">
                                {{ ucfirst($card['translation_status']) }} — {{ $card['translation_progress'] }}%
                            </p>
                        </div>
                    @elseif ($card['translation_status'] === 'completed')
                        @php $summary = json_decode((string) $card['translation_summary'], true); @endphp
                        <span class="badge badge-green" style="font-size:10px">Completed — {{ $summary['items_translated'] ?? 0 }} items translated</span>
                    @elseif ($card['translation_status'] === 'failed')
                        <span class="badge badge-red" style="font-size:10px">Translation failed</span>
                    @endif

                    <x-btn type="button" wire:click="openModal({{ $card['id'] }})"
                           :disabled="!$card['is_active'] || in_array($card['translation_status'], ['queued', 'running'])">
                        @if (in_array($card['translation_status'], ['queued', 'running']))
                            Translating…
                        @elseif ($card['is_free'])
                            Run AI Translation
                        @else
                            Buy AI Translation (${{ number_format((float) $card['price'], 2) }})
                        @endif
                    </x-btn>
                </div>
            @endforeach

            @if ($cards->isEmpty())
                <div class="card fu d2" style="grid-column:1/-1">
                    <div class="empty-state">
                        <div class="empty-state-title">No AI translation available</div>
                        <p class="empty-state-copy">The platform admin has not configured AI translation pricing for any language yet.</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Confirmation / Payment Modal --}}
        @if ($showPaymentModal && $selectedCard)
            <x-modal wire:model="showPaymentModal"
                     title='AI Translation — {{ $selectedCard["name"] }}'
                     closeAction="closeModal"
                     maxWidth="md">

                <div class="page-stack">
                    <p class="panel-copy">
                        This will translate your entire store (products, categories, banners, UI strings) into
                        <strong>{{ $selectedCard['name'] }}</strong>, customized to your brand and product categories.
                    </p>

                    @if ($selectedCard['is_free'])
                        <div class="card" style="padding:12px 16px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;">
                            <span style="font-size:13px;color:var(--green)">
                                This AI translation is included in your plan — no charge.
                            </span>
                        </div>

                        <div class="page-actions compact-actions justify-end">
                            <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                            <x-btn type="button" wire:click="runFree"
                                   wire:loading.attr="disabled" wire:target="runFree">
                                <span wire:loading.remove wire:target="runFree">Start AI Translation</span>
                                <span wire:loading wire:target="runFree">Starting…</span>
                            </x-btn>
                        </div>
                    @else
                        <div class="locale-fields-group" style="margin-bottom:4px;">
                            <div style="display:flex;align-items:center;gap:14px;">
                                <div style="flex:1;min-width:0;">
                                    <div class="entity-title">{{ $selectedCard['name'] }}
                                        <span class="badge badge-violet" style="margin-left:6px;vertical-align:middle;">{{ strtoupper($selectedCard['code']) }}</span>
                                    </div>
                                    <div class="entity-subtitle">One-time payment · {{ $selectedCard['native_name'] }}</div>
                                </div>
                                <div style="font-size:18px;font-weight:700;color:var(--accent);white-space:nowrap;">
                                    ${{ number_format((float) $selectedCard['price'], 2) }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Payment Gateway</label>
                            @if ($gateways->isNotEmpty())
                                <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                                    @foreach ($gateways as $gateway)
                                        <label wire:key="ai-gw-{{ $gateway['id'] }}"
                                               style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;cursor:pointer;border:1.5px solid {{ $selectedGateway === $gateway['code'] ? 'var(--accent)' : 'var(--border)' }};background:{{ $selectedGateway === $gateway['code'] ? 'color-mix(in srgb,var(--accent) 8%,transparent)' : 'var(--elevated)' }};transition:border-color .15s,background .15s;">
                                            <input type="radio"
                                                   wire:click="selectGateway('{{ $gateway['code'] }}')"
                                                   name="ai_payment_choice"
                                                   value="{{ $gateway['code'] }}"
                                                   @checked($selectedGateway === $gateway['code'])
                                                   style="accent-color:var(--accent);width:15px;height:15px;flex-shrink:0;">
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

                        @if ($activeInlineGateway)
                            <div id="ai-inline-card-form"
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
                                @if ($activeInlineGateway['code'] === 'stripe')
                                    <div>
                                        <label class="field-label">Card</label>
                                        <div id="ai-stripe-card-element"
                                             style="border:1.5px solid var(--border);border-radius:8px;padding:11px 14px;background:var(--input-bg,var(--card));min-height:42px;">
                                        </div>
                                        <p id="ai-stripe-card-errors" class="field-error" style="display:none;margin-top:5px;"></p>
                                        <input type="hidden" wire:model="stripeToken">
                                    </div>
                                @else
                                    <div class="form-grid form-grid-1" style="gap:10px;">
                                        <div>
                                            <label class="field-label">Card number</label>
                                            <x-input type="text" id="ai-card-number" inputmode="numeric" autocomplete="cc-number"
                                                     placeholder="1234  5678  9012  3456" />
                                        </div>
                                        <div class="form-grid form-grid-2" style="gap:10px;">
                                            <div>
                                                <label class="field-label">Expiry</label>
                                                <x-input type="text" id="ai-card-expiry" inputmode="numeric" autocomplete="cc-exp"
                                                         placeholder="MM / YY" maxlength="7" />
                                            </div>
                                            <div>
                                                <label class="field-label">CVC</label>
                                                <x-input type="text" id="ai-card-cvc" inputmode="numeric" autocomplete="cc-csc"
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
                                    <p id="ai-card-errors" class="field-error" style="display:none;"></p>
                                @endif
                            </div>
                        @endif

                        <div class="page-actions compact-actions justify-end">
                            <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                            @if ($gateways->isNotEmpty())
                                <x-btn type="button" id="ai-pay-btn"
                                       wire:loading.attr="disabled"
                                       wire:target="initiatePayment">
                                    <span wire:loading.remove wire:target="initiatePayment">Proceed to Payment</span>
                                    <span wire:loading wire:target="initiatePayment">Processing…</span>
                                </x-btn>
                            @endif
                        </div>
                    @endif
                </div>
            </x-modal>
        @endif

        {{-- History --}}
        @if ($history->isNotEmpty())
            <div class="card fu d2 table-card-shell section-gap">
                <div class="table-header-shell">
                    <h3 class="panel-title">Translation History</h3>
                </div>
                @foreach ($history as $run)
                    <div class="details-kv" style="padding:12px 20px;border-bottom:1px solid rgba(255,255,255,.06)">
                        <div>
                            <div class="entity-title">{{ $run->language->name ?? 'Unknown' }}</div>
                            <div class="entity-subtitle">{{ $run->created_at->format('M d, Y H:i') }}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px">
                            @if ($run->amount > 0)
                                <span class="entity-subtitle">${{ number_format((float) $run->amount, 2) }}</span>
                            @else
                                <span class="entity-subtitle">Free</span>
                            @endif
                            <span class="badge {{ match($run->status) { 'completed' => 'badge-green', 'pending' => 'badge-amber', 'failed' => 'badge-red', default => 'badge-gray' } }}">
                                {{ ucfirst($run->status) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
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
            let _aiStripe = null;
            let _aiStripeCard = null;

            function aiInitPaymentForm() {
                const container = document.getElementById('ai-inline-card-form');
                if (!container || container.dataset.initialized) return;

                const gateway = container.dataset.gateway;

                if (gateway === 'stripe') {
                    const stripeKey = container.dataset.stripeKey;
                    if (!stripeKey || typeof Stripe === 'undefined') return;

                    _aiStripe = Stripe(stripeKey);
                    const elms = _aiStripe.elements();
                    _aiStripeCard = elms.create('card', {
                        style: {
                            base: { fontSize: '13px', color: '#333333', fontFamily: 'inherit', '::placeholder': { color: '#aaaaaa' } },
                            invalid: { color: '#dc2626' },
                        },
                        hidePostalCode: true,
                    });
                    _aiStripeCard.mount('#ai-stripe-card-element');
                    _aiStripeCard.on('change', (e) => {
                        const errEl = document.getElementById('ai-stripe-card-errors');
                        if (errEl) {
                            errEl.textContent = e.error?.message ?? '';
                            errEl.style.display = e.error ? '' : 'none';
                        }
                    });
                    container.dataset.initialized = '1';
                } else {
                    aiSetupCardFormatting();
                    container.dataset.initialized = '1';
                }
            }

            function aiSetupCardFormatting() {
                const numInput = document.getElementById('ai-card-number');
                if (numInput && !numInput.dataset.fmt) {
                    numInput.dataset.fmt = '1';
                    numInput.addEventListener('input', () => {
                        let v = numInput.value.replace(/\D/g, '').slice(0, 16);
                        numInput.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
                    });
                }
                const expInput = document.getElementById('ai-card-expiry');
                if (expInput && !expInput.dataset.fmt) {
                    expInput.dataset.fmt = '1';
                    expInput.addEventListener('input', () => {
                        let v = expInput.value.replace(/\D/g, '').slice(0, 4);
                        if (v.length > 2) v = v.slice(0, 2) + ' / ' + v.slice(2);
                        expInput.value = v;
                    });
                }
            }

            aiInitPaymentForm();

            Livewire.on('aiTranslationPaymentMethodChanged', () => {
                setTimeout(aiInitPaymentForm, 30);
            });

            document.addEventListener('livewire:updated', () => {
                setTimeout(aiInitPaymentForm, 30);
            });

            document.addEventListener('click', async (event) => {
                const button = event.target.closest('#ai-pay-btn');
                if (!button) return;

                const inlineForm = document.getElementById('ai-inline-card-form');

                if (!inlineForm) {
                    $wire.call('initiatePayment');
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                const gateway = inlineForm.dataset.gateway;
                button.disabled = true;

                if (gateway === 'stripe') {
                    if (!_aiStripe || !_aiStripeCard) {
                        alert('Stripe.js is still loading. Please try again in a moment.');
                        button.disabled = false;
                        return;
                    }
                    const { token, error } = await _aiStripe.createToken(_aiStripeCard);
                    if (error) {
                        const errEl = document.getElementById('ai-stripe-card-errors');
                        if (errEl) { errEl.textContent = error.message; errEl.style.display = ''; }
                        button.disabled = false;
                        return;
                    }
                    await $wire.set('stripeToken', token.id);
                    $wire.call('initiatePayment');
                    return;
                }

                if (gateway === 'authorize_net') {
                    if (typeof Accept === 'undefined') {
                        alert('Accept.js is still loading. Please try again.');
                        button.disabled = false;
                        return;
                    }
                    const rawExp = document.getElementById('ai-card-expiry')?.value.replace(/\s/g, '') ?? '';
                    const [mon, yr] = rawExp.split('/');
                    const secureData = {
                        authData: {
                            apiLoginID: inlineForm.dataset.authLogin,
                            clientKey: inlineForm.dataset.authClient,
                        },
                        cardData: {
                            cardNumber: document.getElementById('ai-card-number')?.value.replace(/\s/g, '') ?? '',
                            month: (mon ?? '').trim(),
                            year: '20' + (yr ?? '').trim(),
                            cardCode: document.getElementById('ai-card-cvc')?.value ?? '',
                        },
                    };
                    Accept.dispatchData(secureData, async (response) => {
                        if (response.messages.resultCode === 'Error') {
                            const errEl = document.getElementById('ai-card-errors');
                            if (errEl) { errEl.textContent = response.messages.message?.[0]?.text ?? 'Card error'; errEl.style.display = ''; }
                            button.disabled = false;
                            return;
                        }
                        await $wire.set('authnetDesc', response.opaqueData.dataDescriptor);
                        await $wire.set('authnetValue', response.opaqueData.dataValue);
                        $wire.call('initiatePayment');
                    });
                    return;
                }

                if (gateway === '2checkout') {
                    if (typeof TCO === 'undefined') {
                        alert('2Pay.js is still loading. Please try again.');
                        button.disabled = false;
                        return;
                    }
                    const rawExp = document.getElementById('ai-card-expiry')?.value.replace(/\s/g, '') ?? '';
                    const [mon, yr] = rawExp.split('/');
                    TCO.requestToken({
                        sellerId: inlineForm.dataset['2coSeller'],
                        publishableKey: inlineForm.dataset['2coSeller'],
                        ccNo: document.getElementById('ai-card-number')?.value.replace(/\s/g, '') ?? '',
                        cvv: document.getElementById('ai-card-cvc')?.value ?? '',
                        expMonth: (mon ?? '').trim(),
                        expYear: '20' + (yr ?? '').trim(),
                    }, async (data) => {
                        if (data.errorCode > 0) {
                            const errEl = document.getElementById('ai-card-errors');
                            if (errEl) { errEl.textContent = data.errorMsg ?? 'Card error'; errEl.style.display = ''; }
                            button.disabled = false;
                            return;
                        }
                        await $wire.set('twocoToken', data.token.token);
                        $wire.call('initiatePayment');
                    });
                    return;
                }

                $wire.call('initiatePayment');
            }, { capture: true });
        </script>
        @endscript
    @endif
</main>
