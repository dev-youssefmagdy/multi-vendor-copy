{{-- Partner Program page content. Partner balance is separate from vendor order/subscription money. --}}
@php
    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $partnerCards = [
        ['label' => __('Available reward'), 'value' => $money($partnerStats['balance']), 'caption' => __('Requestable: :amount', ['amount' => $money($partnerStats['requestable'])]), 'dot' => 'dot-green'],
        ['label' => __('Total earned'), 'value' => $money($partnerStats['total_earned']), 'dot' => 'dot-cyan'],
        ['label' => __('Total paid'), 'value' => $money($partnerStats['total_paid']), 'dot' => 'dot-cyan'],
        ['label' => __('Pending requests'), 'value' => $money($partnerStats['pending_requested']), 'dot' => 'dot-orange'],
        ['label' => __('Link visits'), 'value' => number_format((int) $partnerStats['visits']), 'caption' => '+' . number_format((int) $partnerStats['visits_30d']) . ' this month', 'dot' => 'dot-cyan'],
        ['label' => __('Registered traders'), 'value' => number_format((int) $partnerStats['registered']), 'caption' => number_format((int) $partnerStats['paying']) . ' paying', 'dot' => 'dot-cyan'],
    ];
@endphp

<x-tenant::stats-grid :stats="$partnerCards" :columns="3" />

<x-tenant::card :title="__('Your referral link')" :subtitle="__('Share this link with other traders. You earn a commission on every paid subscription.')">
    <div class="db-partner-link">
        <span class="db-partner-url"><span>{{ $partnerLink }}</span></span>
        <x-tenant::copy :value="$partnerLink" />
        <form method="POST" action="{{ route('tenant.finance.wallet.partner.sso') }}" target="_blank" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm">{{ __('Open affiliate dashboard') }}</button>
        </form>
        <button type="button" class="btn btn-primary btn-sm" data-modal-open="partner-withdraw"
            @disabled(! $partnerStats['can_request'])
            @unless ($partnerStats['can_request']) title="{{ __('Minimum withdrawal amount is :amount.', ['amount' => $money($partnerStats['min_payout'])]) }}" @endunless>{{ __('Withdraw') }}</button>
    </div>
    @unless ($partnerStats['can_request'])
        <p class="field-hint" style="margin-top:8px;">
            {{ __('You can request a withdrawal once your available balance reaches the minimum withdrawal amount of :amount.', ['amount' => $money($partnerStats['min_payout'])]) }}
        </p>
    @endunless
</x-tenant::card>

<x-tenant::datatable id="partner-conversions-table" :url="route('tenant.finance.wallet.partner.conversions.data')" :columns="$partnerConversionColumns"
    :title="__('Referral conversions')" :description="__('Merchants who subscribed through your link and the commission earned.')" :order="[[0, 'asc']]" />

<x-tenant::datatable id="partner-payouts-table" :url="route('tenant.finance.wallet.partner.payouts.data')" :columns="$partnerPayoutColumns"
    :title="__('Payouts & requests')" :description="__('Your withdrawal requests and the payouts issued for them.')" :order="[[0, 'asc']]" />

<x-tenant::modal id="partner-withdraw" :title="__('Withdraw reward')" size="md">
    <x-tenant::form :action="route('tenant.finance.wallet.partner.withdraw')" success="redirect">
        <x-tenant::input name="amount" type="number" :label="__('Amount (USD)')" required step="0.01"
            :min="number_format(max((float) $partnerStats['min_payout'], 0.01), 2, '.', '')"
            :max="number_format((float) $partnerStats['requestable'], 2, '.', '')"
            :value="number_format((float) $partnerStats['requestable'], 2, '.', '')"
            :help="__('Available to request: :available · Minimum withdrawal amount: :minimum', ['available' => $money($partnerStats['requestable']), 'minimum' => $money($partnerStats['min_payout'])])" />
        <x-tenant::textarea name="note" :label="__('Note (optional)')" />
        <div class="modal-footer-actions">
            <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
            <x-tenant::submit :loading-text="__('Submitting…')">{{ __('Request withdrawal') }}</x-tenant::submit>
        </div>
    </x-tenant::form>
</x-tenant::modal>
