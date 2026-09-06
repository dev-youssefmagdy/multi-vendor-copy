<main>
    <div class="page-head">
        <div>
            <h1 class="D page-title">{{ __('My Links & Codes') }}</h1>
            <p class="page-copy">{{ __('Share your referral link or promo codes to earn commissions on sign-ups.') }}</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="g-stats section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">{{ __('Affiliate Code') }}</div>
                    <div class="D stat-value">{{ $affiliate->code }}</div>
                </div>
            </div>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">{{ __('Total Clicks') }}</div>
                    <div class="D stat-value">{{ $clickCount }}</div>
                </div>
            </div>
        </div>
        <div class="card card-glow-amber">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">{{ __('Promo Codes') }}</div>
                    <div class="D stat-value">{{ $coupons->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Referral URL --}}
    <div class="card section-gap">
        <h3 class="D section-title">{{ __('Referral URL') }}</h3>
        <p class="section-copy">{{ __('Anyone who signs up through this URL is tracked as your referral. Commission is earned when they purchase a paid plan.') }}</p>

        <div style="display:flex;gap:8px;margin-top:16px;">
            <input type="text" id="referral-url-input" readonly value="{{ $referralUrl }}"
                   class="field-control" style="flex:1;">
            <button type="button" class="btn btn-primary" onclick="
                window.copyToClipboard(document.getElementById('referral-url-input').value);
                this.innerText = '{{ __('Copied!') }}';
                setTimeout(() => this.innerText = '{{ __('Copy') }}', 1500);
            ">{{ __('Copy') }}</button>
        </div>

        <div class="notice-row" style="margin-top:14px;display:flex;gap:8px;padding:10px 14px;border-radius:9px;background:rgba(0,229,255,0.06);border:1px solid rgba(0,229,255,0.18);">
            <svg width="14" height="14" fill="none" stroke="var(--cyan)" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <p class="panel-copy" style="margin:0;font-size:12px;">
                {{ __('Note: If the user applies a promo code at checkout, the promo code commission takes priority. You will not earn both a URL referral commission and a promo code commission on the same order.') }}
            </p>
        </div>
    </div>

    {{-- Promo Codes --}}
    <div class="card section-gap">
        <h3 class="D section-title">{{ __('Your Promo Codes') }}</h3>
        <p class="section-copy">{{ __('These coupon codes are assigned to your account. Share them with customers — when someone uses your code to purchase a plan, you earn a commission.') }}</p>

        @if ($coupons->isNotEmpty())
            <div class="tw" style="margin-top:16px;">
                <table class="tb">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Discount') }}</th>
                            <th>{{ __('Your Commission') }}</th>
                            <th>{{ __('Valid Until') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coupons as $coupon)
                            <tr>
                                <td>
                                    <strong style="font-size:15px;letter-spacing:1px;color:var(--cyan);">{{ $coupon->code }}</strong>
                                </td>
                                <td>
                                    {{ $coupon->discountLabel() }}
                                </td>
                                <td>
                                    @if ($coupon->commission_value !== null)
                                        <strong>{{ $coupon->commission_value }}%</strong>
                                        <span class="panel-copy"> of sale</span>
                                    @else
                                        <span class="panel-copy">{{ __('Your default rate') }}
                                        ({{ $affiliate->commission_type === 'percentage'
                                            ? $affiliate->commission_value . '%'
                                            : '$' . number_format((float) $affiliate->commission_value, 2) }})</span>
                                    @endif
                                </td>
                                <td>{{ $coupon->end_date ? $coupon->end_date->format('M d, Y') : __('No expiry') }}</td>
                                <td>
                                    <span class="chip {{ $coupon->active ? 'c-g' : 'c-r' }}">
                                        {{ $coupon->active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-secondary btn-sm"
                                            onclick="window.copyToClipboard('{{ $coupon->code }}');this.innerText='{{ __('Copied!') }}';setTimeout(()=>this.innerText='{{ __('Copy Code') }}',1500);">
                                        {{ __('Copy Code') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state" style="margin-top:16px;">
                <div class="empty-state-title">{{ __('No promo codes yet') }}</div>
                <p class="empty-state-copy">{{ __('The admin team will assign promo codes to your account. Check back soon.') }}</p>
            </div>
        @endif
    </div>

    <script>
        if (!window.copyToClipboard) {
            window.copyToClipboard = function (text) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).catch(() => window.copyToClipboardFallback(text));
                } else {
                    window.copyToClipboardFallback(text);
                }
            };

            window.copyToClipboardFallback = function (text) {
                const el = document.createElement('textarea');
                el.value = text;
                el.style.position = 'fixed';
                el.style.opacity = '0';
                document.body.appendChild(el);
                el.focus();
                el.select();
                try {
                    document.execCommand('copy');
                } catch (e) {}
                document.body.removeChild(el);
            };
        }
    </script>
</main>
