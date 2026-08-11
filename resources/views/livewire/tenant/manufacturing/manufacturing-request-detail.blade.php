<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Request #{{ $request->id }} — {{ $request->product_name }}</h1>
                <span class="page-badge">Manufacturing</span>
            </div>
            <p class="page-copy">{{ $pageDescription }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('tenant.manufacturing.index') }}" class="btn btn-secondary">← Back to Requests</a>
        </div>
    </div>

    @if (session('mf_payment_success'))
        <div class="card section-gap notice-success">{{ session('mf_payment_success') }}</div>
    @endif
    @if (session('mf_payment_error'))
        <div class="card section-gap notice-error">{{ session('mf_payment_error') }}</div>
    @endif
    @if ($errors->has('payment'))
        <div class="card section-gap notice-error">{{ $errors->first('payment') }}</div>
    @endif

    {{-- ── Request Details ──────────────────────────────────────────────── --}}
    <div class="card fu d1 section-gap" style="padding:24px;">
        <h3 class="panel-title" style="margin-bottom:16px;">Request Details</h3>
        <dl style="display:grid;grid-template-columns:130px 1fr;gap:10px 16px;font-size:13px;">
            <dt class="entity-subtitle">Product</dt>
            <dd class="entity-title">{{ $request->product_name }}</dd>

            <dt class="entity-subtitle">Quantity</dt>
            <dd class="entity-title">{{ number_format($request->quantity) }}</dd>

            <dt class="entity-subtitle">Status</dt>
            <dd><span class="{{ $request->status->badgeClass() }}">{{ $request->status->label() }}</span></dd>

            <dt class="entity-subtitle">Submitted</dt>
            <dd class="entity-subtitle">{{ $request->created_at->format('M d, Y H:i') }}</dd>

            @if ($request->description)
                <dt class="entity-subtitle" style="padding-top:4px;">Description</dt>
                <dd class="entity-subtitle" style="white-space:pre-wrap;">{{ $request->description }}</dd>
            @endif

            @if ($request->admin_notes)
                <dt class="entity-subtitle" style="padding-top:4px;">Admin Notes</dt>
                <dd class="entity-subtitle" style="white-space:pre-wrap;background:var(--elevated);padding:8px 12px;border-radius:6px;">{{ $request->admin_notes }}</dd>
            @endif
        </dl>
    </div>

    {{-- ── Payment Requests ─────────────────────────────────────────────── --}}
    <section class="card fu d2 table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Payment Requests</h3>
                <p class="panel-copy">Review and pay outstanding invoices issued by the admin team.</p>
            </div>
        </div>

        @if ($paymentRequests->isNotEmpty())
        <x-table :headers="['#', 'Label', 'Amount', 'Status', 'Notes', 'Action']">
            @foreach ($paymentRequests as $pr)
            <tr wire:key="pr-{{ $pr->id }}">
                <td><span class="entity-subtitle">#{{ $pr->id }}</span></td>
                <td><div class="entity-title">{{ $pr->label }}</div></td>
                <td>
                    <div class="entity-title">{{ $pr->currency }} {{ number_format((float)$pr->amount, 2) }}</div>
                    @if ($pr->paid_at)
                        <div class="entity-subtitle">Paid {{ $pr->paid_at->format('M d, Y') }} via {{ strtoupper($pr->gateway_code ?? '') }}</div>
                    @endif
                </td>
                <td><span class="{{ $pr->status->badgeClass() }}">{{ $pr->status->label() }}</span></td>
                <td>
                    <span class="entity-subtitle">{{ $pr->notes ?: '—' }}</span>
                </td>
                <td>
                    @if ($pr->status->value === 'pending')
                        <button type="button" class="btn btn-primary btn-sm"
                            wire:click="openPaymentModal({{ $pr->id }})">
                            Pay Now
                        </button>
                    @else
                        <span class="entity-subtitle">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </x-table>
        @else
        <div class="empty-state">
            <div class="empty-state-title">No payment requests</div>
            <p class="empty-state-copy">The admin hasn't issued any payment requests yet.</p>
        </div>
        @endif
    </section>

    {{-- ── Chat ──────────────────────────────────────────────────────────── --}}
    <section class="card fu d3 section-gap" style="padding:0;overflow:hidden;">
        <div class="table-header-shell" style="padding:16px 20px;">
            <div>
                <h3 class="panel-title">Messages</h3>
                <p class="panel-copy">Communicate with the admin team about this request.</p>
            </div>
        </div>

        <div id="mf-chat-messages"
             style="max-height:400px;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:12px;"
             x-data
             x-init="$el.scrollTop = $el.scrollHeight"
             @chat-scrolled.window="setTimeout(() => $el.scrollTop = $el.scrollHeight, 80)">
            @forelse ($messages as $msg)
                <div wire:key="msg-{{ $msg->id }}"
                     style="display:flex;flex-direction:column;align-items:{{ $msg->sender_type === 'tenant' ? 'flex-end' : 'flex-start' }};">
                    <div style="max-width:75%;padding:10px 14px;border-radius:12px;font-size:13px;line-height:1.5;
                                background:{{ $msg->sender_type === 'tenant' ? 'var(--accent)' : 'var(--elevated)' }};
                                color:{{ $msg->sender_type === 'tenant' ? '#fff' : 'var(--t1)' }};">
                        {{ $msg->message }}
                    </div>
                    <div style="font-size:11px;color:var(--t3);margin-top:3px;padding:0 4px;">
                        {{ $msg->sender_name }} · {{ $msg->created_at->format('M d, H:i') }}
                    </div>
                </div>
            @empty
                <div class="empty-state" style="padding:24px 0;">
                    <div class="empty-state-title">No messages yet</div>
                    <p class="empty-state-copy">Start a conversation with the admin team.</p>
                </div>
            @endforelse
        </div>

        <div style="border-top:1px solid var(--border);padding:14px 20px;">
            <div style="display:flex;gap:10px;align-items:flex-end;">
                <div style="flex:1;">
                    <x-textarea wire:model.defer="chatMessage" rows="2"
                        placeholder="Type a message to the admin…"
                        style="resize:none;"
                        x-on:keydown.ctrl.enter="$wire.sendMessage()" />
                    @error('chatMessage') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <x-btn type="button" wire:click="sendMessage" wire:loading.attr="disabled" wire:target="sendMessage"
                       style="align-self:flex-end;">
                    <span wire:loading.remove wire:target="sendMessage">Send</span>
                    <span wire:loading wire:target="sendMessage">…</span>
                </x-btn>
            </div>
            <p class="entity-subtitle" style="margin-top:6px;">Ctrl+Enter to send</p>
        </div>
    </section>

    {{-- ── Payment Modal ─────────────────────────────────────────────────── --}}
    @if ($showPaymentModal && $payingPaymentRequest)
        <x-modal wire:model="showPaymentModal"
                 title='Pay — {{ $payingPaymentRequest->label }}'
                 closeAction="closePaymentModal"
                 maxWidth="md">
            <div class="page-stack">

                {{-- Order summary --}}
                <div class="locale-fields-group" style="margin-bottom:4px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;">
                        <div>
                            <div class="entity-title">{{ $payingPaymentRequest->label }}</div>
                            <div class="entity-subtitle">Manufacturing Request #{{ $request->id }} · {{ $request->product_name }}</div>
                        </div>
                        <div style="font-size:18px;font-weight:700;color:var(--accent);white-space:nowrap;">
                            {{ $payingPaymentRequest->currency }} {{ number_format((float)$payingPaymentRequest->amount, 2) }}
                        </div>
                    </div>
                </div>

                {{-- Gateway selection --}}
                <div>
                    <label class="field-label">Payment Gateway</label>
                    @if ($gateways->isNotEmpty())
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                            @foreach ($gateways as $gateway)
                                <label wire:key="mf-gw-{{ $gateway['id'] }}"
                                       style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;cursor:pointer;
                                              border:1.5px solid {{ $selectedGateway === $gateway['code'] ? 'var(--accent)' : 'var(--border)' }};
                                              background:{{ $selectedGateway === $gateway['code'] ? 'color-mix(in srgb,var(--accent) 8%,transparent)' : 'var(--elevated)' }};
                                              transition:border-color .15s,background .15s;">
                                    <input type="radio"
                                           wire:click="selectGateway('{{ $gateway['code'] }}')"
                                           name="mf_payment_choice"
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

                {{-- Inline card panel (Stripe / AuthorizeNet / 2Checkout) --}}
                @if ($activeInlineGateway)
                    <div id="mf-inline-card-form"
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
                                <div id="mf-stripe-card-element"
                                     style="border:1.5px solid var(--border);border-radius:8px;padding:11px 14px;background:var(--input-bg,var(--card));min-height:42px;">
                                </div>
                                <p id="mf-stripe-card-errors" class="field-error" style="display:none;margin-top:5px;"></p>
                                <input type="hidden" wire:model="stripeToken">
                            </div>
                        @else
                            <div class="form-grid form-grid-1" style="gap:10px;">
                                <div>
                                    <label class="field-label">Card number</label>
                                    <x-input type="text" id="mf-card-number" inputmode="numeric" autocomplete="cc-number"
                                             placeholder="1234  5678  9012  3456" />
                                </div>
                                <div class="form-grid form-grid-2" style="gap:10px;">
                                    <div>
                                        <label class="field-label">Expiry</label>
                                        <x-input type="text" id="mf-card-expiry" inputmode="numeric" autocomplete="cc-exp"
                                                 placeholder="MM / YY" maxlength="7" />
                                    </div>
                                    <div>
                                        <label class="field-label">CVC</label>
                                        <x-input type="text" id="mf-card-cvc" inputmode="numeric" autocomplete="cc-csc"
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
                            <p id="mf-card-errors" class="field-error" style="display:none;"></p>
                        @endif
                    </div>
                @endif

                {{-- Actions --}}
                <div class="page-actions compact-actions justify-end">
                    <x-btn type="button" variant="secondary" wire:click="closePaymentModal">Cancel</x-btn>
                    @if ($gateways->isNotEmpty())
                        <x-btn type="button" id="mf-pay-btn"
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
        let _mfStripe     = null;
        let _mfStripeCard = null;

        function mfInitPaymentForm() {
            const container = document.getElementById('mf-inline-card-form');
            if (!container || container.dataset.initialized) return;

            const gateway = container.dataset.gateway;

            if (gateway === 'stripe') {
                const stripeKey = container.dataset.stripeKey;
                if (!stripeKey || typeof Stripe === 'undefined') return;

                _mfStripe     = Stripe(stripeKey);
                const elms    = _mfStripe.elements();
                _mfStripeCard = elms.create('card', {
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
                _mfStripeCard.mount('#mf-stripe-card-element');
                _mfStripeCard.on('change', (e) => {
                    const errEl = document.getElementById('mf-stripe-card-errors');
                    if (errEl) {
                        errEl.textContent = e.error?.message ?? '';
                        errEl.style.display = e.error ? '' : 'none';
                    }
                });
                container.dataset.initialized = '1';
            } else {
                mfSetupCardFormatting();
                container.dataset.initialized = '1';
            }
        }

        function mfSetupCardFormatting() {
            const numInput = document.getElementById('mf-card-number');
            if (numInput) {
                numInput.addEventListener('input', () => {
                    let v = numInput.value.replace(/\D/g, '').substring(0, 16);
                    numInput.value = v.replace(/(.{4})/g, '$1  ').trim();
                });
            }
            const expInput = document.getElementById('mf-card-expiry');
            if (expInput) {
                expInput.addEventListener('input', () => {
                    let v = expInput.value.replace(/\D/g, '').substring(0, 4);
                    if (v.length > 2) v = v.substring(0, 2) + ' / ' + v.substring(2);
                    expInput.value = v;
                });
            }
        }

        $wire.on('mfPaymentMethodChanged', () => {
            _mfStripe     = null;
            _mfStripeCard = null;
            setTimeout(mfInitPaymentForm, 50);
        });

        // Init on first render if a gateway is already selected
        setTimeout(mfInitPaymentForm, 50);

        // Pay button click handler
        document.addEventListener('click', async (e) => {
            const button = e.target.closest('#mf-pay-btn');
            if (!button) return;

            e.preventDefault();
            e.stopPropagation();
            button.disabled = true;

            const inlineForm = document.getElementById('mf-inline-card-form');
            const gateway    = inlineForm?.dataset?.gateway;

            if (!gateway) {
                $wire.call('initiatePayment');
                return;
            }

            // ── Stripe ────────────────────────────────────────────────────
            if (gateway === 'stripe') {
                if (!_mfStripe || !_mfStripeCard) {
                    alert('Stripe is still initialising. Please wait a moment.');
                    button.disabled = false;
                    return;
                }
                const { token, error } = await _mfStripe.createToken(_mfStripeCard);
                if (error) {
                    const errEl = document.getElementById('mf-stripe-card-errors');
                    if (errEl) { errEl.textContent = error.message; errEl.style.display = ''; }
                    button.disabled = false;
                    return;
                }
                await $wire.set('stripeToken', token.id);
                $wire.call('initiatePayment');
                return;
            }

            // ── Authorize.Net ─────────────────────────────────────────────
            if (gateway === 'authorize_net') {
                if (typeof Accept === 'undefined') {
                    alert('Authorize.Net Accept.js is still loading. Please try again.');
                    button.disabled = false;
                    return;
                }
                const rawExp = document.getElementById('mf-card-expiry')?.value.replace(/\s/g, '') ?? '';
                const [mon, yr] = rawExp.split('/');
                const secureData = {
                    authData: {
                        apiLoginID: inlineForm.dataset.authLogin,
                        clientKey:  inlineForm.dataset.authClient,
                    },
                    cardData: {
                        cardNumber: document.getElementById('mf-card-number')?.value.replace(/\s/g, '') ?? '',
                        month:      (mon ?? '').trim(),
                        year:       '20' + (yr ?? '').trim(),
                        cardCode:   document.getElementById('mf-card-cvc')?.value ?? '',
                    },
                };
                Accept.dispatchData(secureData, async (response) => {
                    if (response.messages.resultCode === 'Error') {
                        const errEl = document.getElementById('mf-card-errors');
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

            // ── 2Checkout ─────────────────────────────────────────────────
            if (gateway === '2checkout') {
                if (typeof TCO === 'undefined') {
                    alert('2Pay.js is still loading. Please try again.');
                    button.disabled = false;
                    return;
                }
                const rawExp = document.getElementById('mf-card-expiry')?.value.replace(/\s/g, '') ?? '';
                const [mon, yr] = rawExp.split('/');
                TCO.requestToken({
                    sellerId:       inlineForm.dataset['2coSeller'],
                    publishableKey: inlineForm.dataset['2coSeller'],
                    ccNo:           document.getElementById('mf-card-number')?.value.replace(/\s/g, '') ?? '',
                    cvv:            document.getElementById('mf-card-cvc')?.value ?? '',
                    expMonth:       (mon ?? '').trim(),
                    expYear:        '20' + (yr ?? '').trim(),
                }, async (data) => {
                    if (data.errorCode > 0) {
                        const errEl = document.getElementById('mf-card-errors');
                        if (errEl) { errEl.textContent = data.errorMsg ?? 'Card error'; errEl.style.display = ''; }
                        button.disabled = false;
                        return;
                    }
                    await $wire.set('twocoToken', data.token.token);
                    $wire.call('initiatePayment');
                });
                return;
            }

            // Fallback: redirect gateway
            $wire.call('initiatePayment');
        }, { capture: true });
    </script>
    @endscript
</main>
