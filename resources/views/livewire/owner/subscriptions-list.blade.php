<div>
    <div class="page-head">
        <div>
            <div class="page-title D">{{ __('Subscriptions') }}</div>
            <div class="page-copy">
                {{ __('Plan history and active subscription for') }}
                <span class="text-strong">{{ $tenant->data['shop_name'] ?? $tenant->name }}</span>.
            </div>
        </div>
    </div>

    {{-- Current plan summary --}}
    @if($tenant->package)
        <div class="card section-gap" style="display:flex;align-items:center;gap:16px;">
            <div class="si-box tone-bg-violet" style="width:48px;height:48px;border-radius:14px;flex-shrink:0;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="text-violet">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="eyebrow">{{ __('Current Plan') }}</div>
                <div class="stat-value">{{ $tenant->package->name }}</div>
                @if($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture())
                    <div style="font-size:11px;color:var(--amber);margin-top:3px;">
                        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        {{ __('Trial ends') }} {{ $tenant->trial_ends_at->format('M d, Y') }}
                    </div>
                @endif
            </div>
            <a href="{{ route('owner.domains') }}" class="btn btn-secondary xs-hide">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
                {{ __('Manage Domains') }}
            </a>
        </div>
    @endif

    {{-- Subscriptions table --}}
    <div class="card table-card-shell">
        <div class="table-header-shell">
            <div>
                <div class="section-title">{{ __('Subscription History') }}</div>
                <div class="section-copy">{{ __('All billing periods for this store.') }}</div>
            </div>
        </div>

        @forelse($subscriptions as $sub)
            @php
                $statusMap = [
                    'active'    => ['c-g',  'dot-green',  __('Active')],
                    'expired'   => ['',     'dot-muted',  __('Expired')],
                    'cancelled' => ['c-r',  'dot-red',    __('Cancelled')],
                    'pending'   => ['c-a',  'dot-amber',  __('Pending')],
                ];
                $statusVal = $sub->status instanceof \App\Enums\Tenant\SubscriptionStatus ? $sub->status->value : (string) $sub->status;
                [$chipCls, $dotCls, $statusLabel] = $statusMap[$statusVal] ?? ['', 'dot-muted', $statusVal];

                $termMap = ['monthly' => __('Monthly'), 'quarterly' => __('Quarterly'), 'yearly' => __('Yearly')];
                $termVal = $sub->term instanceof \App\Enums\PackageTerm ? $sub->term->value : (string) $sub->term;
                $termLabel = $termMap[$termVal] ?? $termVal;
            @endphp
            <div class="tw">
                <table class="tb">
                    <tbody>
                        <tr>
                            <td>
                                <div class="text-strong" style="font-size:13px;">
                                    {{ $termLabel }} — {{ number_format((float) $sub->price, 2) }}
                                </div>
                                <div class="td-date" style="margin-top:3px;">
                                    {{ $sub->start_date?->format('M d, Y') }}
                                    <span style="color:var(--t3);">→</span>
                                    {{ $sub->end_date?->format('M d, Y') ?? __('No expiry') }}
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <span class="chip {{ $chipCls }}" style="display:inline-flex;align-items:center;gap:5px;">
                                    <span class="dot {{ $dotCls }}" style="width:6px;height:6px;"></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @empty
            <div class="empty-state">
                <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4" style="color:var(--t3);opacity:.4;">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                <div class="empty-state-title">{{ __('No subscription records found') }}</div>
                <div class="empty-state-copy">{{ __('Your billing history will appear here once you subscribe to a plan.') }}</div>
            </div>
        @endforelse

        @if($subscriptions->isNotEmpty())
            <div class="table-footer-shell">
                <span style="font-size:12px;color:var(--t3);">{{ $subscriptions->count() }} {{ __('record(s)') }}</span>
            </div>
        @endif
    </div>
</div>
