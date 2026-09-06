<main>
    <div class="page-head">
        <div>
            <h1 class="D page-title">{{ __('Dashboard') }}</h1>
            <p class="page-copy">{{ __('Welcome back, :name.', ['name' => $affiliate->name]) }}</p>
        </div>
    </div>

    <div class="g-stats section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head"><div><div class="eyebrow">{{ __('Balance') }}</div><div class="D stat-value">${{ $stats['balance'] }}</div></div><div class="mini-stat-dot dot-cyan"></div></div>
            <p class="section-copy">{{ __('Available for payout.') }}</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head"><div><div class="eyebrow">{{ __('Total Earned') }}</div><div class="D stat-value">${{ $stats['total_earned'] }}</div></div><div class="mini-stat-dot dot-green"></div></div>
            <p class="section-copy">{{ __('Lifetime commissions earned.') }}</p>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head"><div><div class="eyebrow">{{ __('Total Paid') }}</div><div class="D stat-value">${{ $stats['total_paid'] }}</div></div><div class="mini-stat-dot dot-violet"></div></div>
            <p class="section-copy">{{ __('Already paid out to you.') }}</p>
        </div>
        <div class="card card-glow-amber">
            <div class="stat-head"><div><div class="eyebrow">{{ __('Referrals') }}</div><div class="D stat-value">{{ $stats['total_referrals'] }}</div></div><div class="mini-stat-dot dot-amber"></div></div>
            <p class="section-copy">{{ __('Total tracked clicks/referrals.') }}</p>
        </div>
        <div class="card">
            <div class="stat-head"><div><div class="eyebrow">{{ __('Conversions') }}</div><div class="D stat-value">{{ $stats['conversions'] }}</div></div></div>
            <p class="section-copy">{{ __('Approved or paid conversions.') }}</p>
        </div>
        <div class="card">
            <div class="stat-head"><div><div class="eyebrow">{{ __('Pending') }}</div><div class="D stat-value">{{ $stats['pending'] }}</div></div></div>
            <p class="section-copy">{{ __('Awaiting approval.') }}</p>
        </div>
    </div>

    <div class="card section-gap">
        <div class="transactions-head">
            <div>
                <h3 class="D section-title">{{ __('Recent Conversions') }}</h3>
                <p class="section-copy">{{ __('Your latest tracked sales.') }}</p>
            </div>
        </div>
        <div class="tw">
            <table class="tb">
                <thead>
                    <tr>
                        <th>{{ __('Package') }}</th>
                        <th>{{ __('Sale Amount') }}</th>
                        <th>{{ __('Commission') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentConversions as $conversion)
                        <tr>
                            <td>{{ $conversion->package?->name ?? __('No package') }}</td>
                            <td>${{ number_format((float) $conversion->sale_amount, 2) }}</td>
                            <td>${{ number_format((float) $conversion->commission_amount, 2) }}</td>
                            <td>
                                <span class="chip {{ $conversion->status === 'paid' ? 'c-g' : ($conversion->status === 'rejected' ? 'c-r' : 'c-a') }}">
                                    {{ ucfirst($conversion->status) }}
                                </span>
                            </td>
                            <td>{{ $conversion->created_at?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-state-title">{{ __('No conversions yet') }}</div>
                                    <p class="empty-state-copy">{{ __('Share your referral link to start earning commissions.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>
