<main>
    <div class="page-head">
        <div>
            <h1 class="D page-title">{{ __('My Links') }}</h1>
            <p class="page-copy">{{ __('Share this link to earn commissions on referred sign-ups.') }}</p>
        </div>
    </div>

    <div class="g-stats section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head"><div><div class="eyebrow">{{ __('Affiliate Code') }}</div><div class="D stat-value">{{ $affiliate->code }}</div></div></div>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head"><div><div class="eyebrow">{{ __('Total Clicks') }}</div><div class="D stat-value">{{ $clickCount }}</div></div></div>
        </div>
    </div>

    <div class="card section-gap">
        <h3 class="D section-title">{{ __('Your Referral URL') }}</h3>
        <p class="section-copy">{{ __('Anyone who signs up through this link is tracked as your referral.') }}</p>

        <div style="display:flex;gap:8px;margin-top:16px;">
            <input type="text" id="referral-url-input" readonly value="{{ $referralUrl }}" class="field-control" style="flex:1;">
            <button type="button" class="btn btn-primary" onclick="
                navigator.clipboard.writeText(document.getElementById('referral-url-input').value);
                this.innerText = '{{ __('Copied!') }}';
                setTimeout(() => this.innerText = '{{ __('Copy') }}', 1500);
            ">{{ __('Copy') }}</button>
        </div>
    </div>
</main>
