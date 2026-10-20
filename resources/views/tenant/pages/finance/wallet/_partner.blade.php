{{-- Wallet "Partner Program" tab. Partner balance is separate from vendor order/subscription money. --}}
@php
    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $partnerCards = [
        ['label' => 'Available reward', 'value' => $money($partnerStats['balance']), 'caption' => 'Requestable: ' . $money($partnerStats['requestable']), 'dot' => 'dot-green'],
        ['label' => 'Total earned', 'value' => $money($partnerStats['total_earned']), 'dot' => 'dot-cyan'],
        ['label' => 'Total paid', 'value' => $money($partnerStats['total_paid']), 'dot' => 'dot-cyan'],
        ['label' => 'Pending requests', 'value' => $money($partnerStats['pending_requested']), 'dot' => 'dot-orange'],
        ['label' => 'Link visits', 'value' => number_format((int) $partnerStats['visits']), 'caption' => '+' . number_format((int) $partnerStats['visits_30d']) . ' this month', 'dot' => 'dot-cyan'],
        ['label' => 'Registered traders', 'value' => number_format((int) $partnerStats['registered']), 'caption' => number_format((int) $partnerStats['paying']) . ' paying', 'dot' => 'dot-cyan'],
    ];
@endphp

<x-tenant::stats-grid :stats="$partnerCards" :columns="3" />

<x-tenant::card title="Your referral link" subtitle="Share this link with other traders. You earn a commission on every paid subscription.">
    <div class="db-partner-link">
        <span class="db-partner-url"><span>{{ $partnerLink }}</span></span>
        <x-tenant::copy :value="$partnerLink" />
        <button type="button" class="btn btn-primary btn-sm" data-modal-open="partner-withdraw"
            @disabled((float) $partnerStats['requestable'] <= 0)>Withdraw</button>
    </div>
</x-tenant::card>

<x-tenant::datatable id="partner-conversions-table" :url="route('tenant.finance.wallet.partner.conversions.data')" :columns="$partnerConversionColumns"
    title="Referral conversions" description="Merchants who subscribed through your link and the commission earned." :order="[[0, 'asc']]" />

<x-tenant::datatable id="partner-payouts-table" :url="route('tenant.finance.wallet.partner.payouts.data')" :columns="$partnerPayoutColumns"
    title="Payouts & requests" description="Your withdrawal requests and the payouts issued for them." :order="[[0, 'asc']]" />

<x-tenant::modal id="partner-withdraw" title="Withdraw reward" size="md">
    <x-tenant::form :action="route('tenant.finance.wallet.partner.withdraw')" success="redirect">
        <x-tenant::input name="amount" type="number" label="Amount (USD)" required
            :value="number_format((float) $partnerStats['requestable'], 2, '.', '')"
            :help="'Available to request: ' . $money($partnerStats['requestable']) . ' · Minimum: ' . $money($partnerStats['min_payout'])" />
        <x-tenant::textarea name="note" label="Note (optional)" />
        <div class="modal-footer-actions">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <x-tenant::submit loading-text="Submitting…">Request withdrawal</x-tenant::submit>
        </div>
    </x-tenant::form>
</x-tenant::modal>
