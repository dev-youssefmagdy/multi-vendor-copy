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
        <div style="display:flex;gap:10px;align-items:center;flex-shrink:0;">
            <button type="button" class="btn btn-secondary btn-sm" wire:click="openUpgradeModal">
                Upgrade Plan
            </button>
            <button type="button" class="btn btn-primary btn-sm" wire:click="openRenewModal">
                Renew Plan
            </button>
        </div>
    </div>

    @if (session('subscription_success'))
        <div class="card section-gap notice-success">{{ session('subscription_success') }}</div>
    @endif
    @if (session('subscription_cancelled'))
        <div class="card section-gap notice-error">{{ session('subscription_cancelled') }}</div>
    @endif
    @if ($errors->has('payment'))
        <div class="card section-gap notice-error">{{ $errors->first('payment') }}</div>
    @endif

    <div class="g-stats4 section-gap">
        @foreach ($cards as $card)
            <div class="card {{ $card['glow'] ?? '' }}">
                <div class="stat-head">
                    <div>
                        <div class="eyebrow">{{ $card['label'] }}</div>
                        <div class="D stat-value">{{ $card['value'] }}</div>
                    </div>
                    <div class="mini-stat-dot {{ $card['dot'] ?? 'dot-cyan' }}"></div>
                </div>
                <p class="panel-copy">{{ $card['caption'] }}</p>
            </div>
        @endforeach
    </div>

    <section class="card table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Tenant Subscriptions</h3>
                <p class="panel-copy">Subscription records stored for the current tenant, including linked transaction
                    references.</p>
            </div>
        </div>

        <x-table :headers="['Subscription', 'Status', 'Term', 'Gateway', 'Price', 'Window', 'Transaction']">
            @forelse ($subscriptions as $subscription)
                <tr>
                    <td>
                        <div class="entity-title">Subscription #{{ $subscription->id }}</div>
                        <div class="entity-subtitle">Package {{ $subscription->package_id ?: 'N/A' }}</div>
                    </td>
                    <td>
                        <span
                            class="badge {{ $subscription->status->value === 'active' ? 'badge-green' : ($subscription->status->value === 'trial' ? 'badge-cyan' : 'badge-amber') }}">
                            {{ $subscription->status->label() }}
                        </span>
                    </td>
                    <td>{{ str($subscription->term->value)->headline()->toString() }}</td>
                    <td>
                        <div class="entity-title">
                            {{ str($subscription->payment_gateway_type->value)->headline()->toString() }}</div>
                        <div class="entity-subtitle">Gateway ID {{ $subscription->payment_gateway_id ?: 'N/A' }}</div>
                    </td>
                    <td>${{ number_format((float) $subscription->price, 2) }}</td>
                    <td>{{ optional($subscription->start_date)->format('M d, Y') ?: '-' }} -
                        {{ optional($subscription->end_date)->format('M d, Y') ?: '-' }}</td>
                    <td>
                        @if ($subscription->transaction)
                            <div class="entity-title">{{ $subscription->transaction->uuid }}</div>
                            <div class="entity-subtitle">${{ number_format((float) $subscription->transaction->amount, 2) }}
                            </div>
                        @else
                            <span class="panel-copy">No linked transaction</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-title">No subscriptions yet</div>
                            <p class="empty-state-copy">Tenant subscription records will appear here once the tenant billing
                                tables receive entries.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        <div class="table-footer-shell">
            <div class="panel-copy">
                Showing {{ $subscriptions->firstItem() ?? 0 }} to {{ $subscriptions->lastItem() ?? 0 }} of
                {{ $subscriptions->total() }} subscriptions.
            </div>
            <div class="pagination-shell"><x-pagination :paginator="$subscriptions" /></div>
        </div>
    </section>

    <section class="card table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Tenant Transactions</h3>
                <p class="panel-copy">Transaction records linked to tenant subscription activity and balance movements.
                </p>
            </div>
        </div>

        <x-table :headers="['Reference', 'Direction', 'Amount', 'Admin Profit', 'Linked Model', 'Details', 'Created At']">
            @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->uuid }}</td>
                            <td>
                                <span class="badge {{ $transaction->type->value === 'credit' ? 'badge-green' : 'badge-amber' }}">
                                    {{ $transaction->type->label() }}
                                </span>
                            </td>
                            <td>${{ number_format((float) $transaction->amount, 2) }}</td>
                            <td>${{ number_format((float) ($transaction->admin_profit ?? 0), 2) }}</td>
                            <td>
                                @if ($transaction->model)
                                    <div class="entity-title">{{ class_basename($transaction->model_type) }}</div>
                                    <div class="entity-subtitle">#{{ $transaction->model_id }}</div>
                                @else
                                    <span class="panel-copy">No linked model</span>
                                @endif
                            </td>
                            <td>
                                {!! view('components.json-collapse', [
                    'title' => 'Transaction details',
                    'summary' => $transaction->description ? 'Open stored transaction metadata' : 'No detail payload stored',
                    'data' => $transaction->description,
                ])->render() !!}
                            </td>
                            <td>{{ $transaction->created_at?->format('M d, Y H:i') }}</td>
                        </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-title">No transactions yet</div>
                            <p class="empty-state-copy">Tenant transaction records will appear here as soon as subscription
                                billing movements are stored.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        <div class="table-footer-shell">
            <div class="panel-copy">
                Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of
                {{ $transactions->total() }} transactions.
            </div>
            <div class="pagination-shell"><x-pagination :paginator="$transactions" /></div>
        </div>
    </section>

    {{-- Renew / Upgrade payment modal --}}
    @if ($showSubscriptionModal)
        <x-modal wire:model="showSubscriptionModal"
                 :title="$modalType === 'upgrade' ? 'Upgrade Your Plan' : 'Renew Your Plan'"
                 closeAction="closeModal"
                 maxWidth="md">

            <div class="page-stack">

                {{-- Plan selection (upgrade mode only) --}}
                @if ($modalType === 'upgrade')
                    <div>
                        <label class="field-label">Select a Plan</label>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                            @foreach ($packages as $pkg)
                                <label wire:key="sp-pkg-{{ $pkg->id }}"
                                       style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;cursor:pointer;border:1.5px solid {{ $selectedPackageId === $pkg->id ? 'var(--accent)' : 'var(--border)' }};background:{{ $selectedPackageId === $pkg->id ? 'color-mix(in srgb,var(--accent) 8%,transparent)' : 'var(--elevated)' }};transition:border-color .15s,background .15s;">
                                    <input type="radio"
                                           wire:click="$set('selectedPackageId', {{ $pkg->id }})"
                                           name="sp_package_choice"
                                           value="{{ $pkg->id }}"
                                           @checked($selectedPackageId === $pkg->id)
                                           style="accent-color:var(--accent);width:15px;height:15px;flex-shrink:0;">
                                    <div style="flex:1;min-width:0;">
                                        <div class="entity-title">{{ $pkg->name }}</div>
                                        <div class="entity-subtitle">{{ str($pkg->term->value)->headline() }} · ${{ number_format((float) $pkg->price, 2) }}</div>
                                    </div>
                                    @if ($currentPackage && $currentPackage->id === $pkg->id)
                                        <span class="badge badge-cyan" style="font-size:10px;">Current</span>
                                    @endif
                                    @if ($selectedPackageId === $pkg->id)
                                        <svg style="width:15px;height:15px;color:var(--accent);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @error('selectedPackageId')
                            <div class="field-error" style="margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>
                @else
                    {{-- Renewal: show current plan summary --}}
                    @if ($currentPackage)
                        <div class="locale-fields-group" style="margin-bottom:4px;">
                            <div style="display:flex;align-items:center;gap:14px;">
                                <div style="flex:1;min-width:0;">
                                    <div class="entity-title">{{ $currentPackage->name }}</div>
                                    <div class="entity-subtitle">{{ str($currentPackage->term->value)->headline() }} · renewing for another period</div>
                                </div>
                                <div style="font-size:18px;font-weight:700;color:var(--accent);white-space:nowrap;">
                                    ${{ number_format((float) $currentPackage->price, 2) }}
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="panel-copy">No active plan found. Please select an upgrade instead.</p>
                    @endif
                @endif

                {{-- Gateway selection --}}
                <div>
                    <label class="field-label">Payment Gateway</label>
                    @if ($gateways->isNotEmpty())
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                            @foreach ($gateways as $gateway)
                                <label wire:key="sp-gw-{{ $gateway['id'] }}"
                                       style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;cursor:pointer;border:1.5px solid {{ $selectedGateway === $gateway['code'] ? 'var(--accent)' : 'var(--border)' }};background:{{ $selectedGateway === $gateway['code'] ? 'color-mix(in srgb,var(--accent) 8%,transparent)' : 'var(--elevated)' }};transition:border-color .15s,background .15s;">
                                    <input type="radio"
                                           wire:click="selectGateway('{{ $gateway['code'] }}')"
                                           name="sp_payment_choice"
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
                    <div id="sp-inline-card-form"
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
                                <div id="sp-stripe-card-element"
                                     style="border:1.5px solid var(--border);border-radius:8px;padding:11px 14px;background:var(--input-bg,var(--card));min-height:42px;">
                                </div>
                                <p id="sp-stripe-card-errors" class="field-error" style="display:none;margin-top:5px;"></p>
                                <input type="hidden" wire:model="stripeToken">
                            </div>
                        @else
                            <div class="form-grid form-grid-1" style="gap:10px;">
                                <div>
                                    <label class="field-label">Card number</label>
                                    <x-input type="text" id="sp-card-number" inputmode="numeric" autocomplete="cc-number"
                                             placeholder="1234  5678  9012  3456" />
                                </div>
                                <div class="form-grid form-grid-2" style="gap:10px;">
                                    <div>
                                        <label class="field-label">Expiry</label>
                                        <x-input type="text" id="sp-card-expiry" inputmode="numeric" autocomplete="cc-exp"
                                                 placeholder="MM / YY" maxlength="7" />
                                    </div>
                                    <div>
                                        <label class="field-label">CVC</label>
                                        <x-input type="text" id="sp-card-cvc" inputmode="numeric" autocomplete="cc-csc"
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
                            <p id="sp-card-errors" class="field-error" style="display:none;"></p>
                        @endif
                    </div>
                @endif

                {{-- Actions --}}
                <div class="page-actions compact-actions justify-end">
                    <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                    @if ($gateways->isNotEmpty())
                        <x-btn type="button" id="sp-pay-btn"
                               wire:loading.attr="disabled"
                               wire:loading.class="opacity-60 cursor-not-allowed"
                               wire:target="initiatePayment">
                            <span wire:loading.remove wire:target="initiatePayment">
                                {{ $modalType === 'upgrade' ? 'Upgrade Plan' : 'Renew Plan' }}
                            </span>
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
        let _spStripe     = null;
        let _spStripeCard = null;

        function spInitPaymentForm() {
            const container = document.getElementById('sp-inline-card-form');
            if (!container || container.dataset.initialized) return;

            const gateway = container.dataset.gateway;

            if (gateway === 'stripe') {
                const stripeKey = container.dataset.stripeKey;
                if (!stripeKey || typeof Stripe === 'undefined') return;

                const isDark = document.documentElement.classList.contains('dark')
                    || document.body.classList.contains('dark')
                    || window.matchMedia('(prefers-color-scheme: dark)').matches;

                _spStripe     = Stripe(stripeKey);
                const elms    = _spStripe.elements();
                _spStripeCard = elms.create('card', {
                    style: {
                        base: {
                            fontSize: '13px',
                            color: isDark ? '#e5e7eb' : '#1f2937',
                            fontFamily: 'inherit',
                            backgroundColor: 'transparent',
                            '::placeholder': { color: isDark ? '#6b7280' : '#9ca3af' },
                            iconColor: isDark ? '#9ca3af' : '#6b7280',
                        },
                        invalid: { color: '#f87171' },
                    },
                    hidePostalCode: true,
                });
                _spStripeCard.mount('#sp-stripe-card-element');
                _spStripeCard.on('change', (e) => {
                    const errEl = document.getElementById('sp-stripe-card-errors');
                    if (errEl) {
                        errEl.textContent = e.error?.message ?? '';
                        errEl.style.display = e.error ? '' : 'none';
                    }
                });
                container.dataset.initialized = '1';
            } else {
                spSetupCardFormatting();
                container.dataset.initialized = '1';
            }
        }

        function spSetupCardFormatting() {
            const numInput = document.getElementById('sp-card-number');
            if (numInput && !numInput.dataset.fmt) {
                numInput.dataset.fmt = '1';
                numInput.addEventListener('input', () => {
                    let v = numInput.value.replace(/\D/g, '').slice(0, 16);
                    numInput.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
                });
            }
            const expInput = document.getElementById('sp-card-expiry');
            if (expInput && !expInput.dataset.fmt) {
                expInput.dataset.fmt = '1';
                expInput.addEventListener('input', () => {
                    let v = expInput.value.replace(/\D/g, '').slice(0, 4);
                    if (v.length > 2) v = v.slice(0, 2) + ' / ' + v.slice(2);
                    expInput.value = v;
                });
            }
        }

        spInitPaymentForm();

        Livewire.on('subscriptionPaymentMethodChanged', () => {
            setTimeout(spInitPaymentForm, 30);
        });

        document.addEventListener('livewire:updated', () => {
            setTimeout(spInitPaymentForm, 30);
        });

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('#sp-pay-btn');
            if (!button) return;

            const inlineForm = document.getElementById('sp-inline-card-form');

            // No inline-card gateway active → call Livewire directly
            if (!inlineForm) {
                $wire.call('initiatePayment');
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            const gateway = inlineForm.dataset.gateway;
            button.disabled = true;

            // ── Stripe ──────────────────────────────────────────────────────
            if (gateway === 'stripe') {
                if (!_spStripe || !_spStripeCard) {
                    alert('Stripe.js is still loading. Please try again in a moment.');
                    button.disabled = false;
                    return;
                }
                const { token, error } = await _spStripe.createToken(_spStripeCard);
                if (error) {
                    const errEl = document.getElementById('sp-stripe-card-errors');
                    if (errEl) { errEl.textContent = error.message; errEl.style.display = ''; }
                    button.disabled = false;
                    return;
                }
                await $wire.set('stripeToken', token.id);
                $wire.call('initiatePayment');
                return;
            }

            // ── Authorize.Net Accept.js ──────────────────────────────────────
            if (gateway === 'authorize_net') {
                if (typeof Accept === 'undefined') {
                    alert('Accept.js is still loading. Please try again.');
                    button.disabled = false;
                    return;
                }
                const rawExp = document.getElementById('sp-card-expiry')?.value.replace(/\s/g, '') ?? '';
                const [mon, yr] = rawExp.split('/');
                const secureData = {
                    authData: {
                        apiLoginID: inlineForm.dataset.authLogin,
                        clientKey:  inlineForm.dataset.authClient,
                    },
                    cardData: {
                        cardNumber: document.getElementById('sp-card-number')?.value.replace(/\s/g, '') ?? '',
                        month:      (mon ?? '').trim(),
                        year:       '20' + (yr ?? '').trim(),
                        cardCode:   document.getElementById('sp-card-cvc')?.value ?? '',
                    },
                };
                Accept.dispatchData(secureData, async (response) => {
                    if (response.messages.resultCode === 'Error') {
                        const errEl = document.getElementById('sp-card-errors');
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

            // ── 2Checkout 2Pay.js ────────────────────────────────────────────
            if (gateway === '2checkout') {
                if (typeof TCO === 'undefined') {
                    alert('2Pay.js is still loading. Please try again.');
                    button.disabled = false;
                    return;
                }
                const rawExp = document.getElementById('sp-card-expiry')?.value.replace(/\s/g, '') ?? '';
                const [mon, yr] = rawExp.split('/');
                TCO.requestToken({
                    sellerId:       inlineForm.dataset['2coSeller'],
                    publishableKey: inlineForm.dataset['2coSeller'],
                    ccNo:           document.getElementById('sp-card-number')?.value.replace(/\s/g, '') ?? '',
                    cvv:            document.getElementById('sp-card-cvc')?.value ?? '',
                    expMonth:       (mon ?? '').trim(),
                    expYear:        '20' + (yr ?? '').trim(),
                }, async (data) => {
                    if (data.errorCode > 0) {
                        const errEl = document.getElementById('sp-card-errors');
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
