@extends('tenant.layouts.app')

@section('title', 'Subscriptions & Balance')

@section('content')
    <x-tenant::page-header title="Subscriptions & Balance" badge="Finance" description="Review tenant subscription records, the current subscription-backed balance, and tenant transaction history in one place.">
        <x-slot:actions>
            <button type="button" class="btn btn-secondary btn-sm" data-modal-open="subscription-payment">Upgrade Plan</button>
            <button type="button" class="btn btn-primary btn-sm" data-modal-open="subscription-payment">Renew Plan</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$cards" />

    <x-tenant::datatable id="subscriptions-table" :url="route('tenant.finance.wallet.subscriptions.data')" :columns="$subscriptionColumns"
        title="Tenant Subscriptions" description="Subscription records stored for the current tenant, including linked transaction references."
        :order="[[1, 'asc']]" />

    <x-tenant::datatable id="transactions-table" :url="route('tenant.finance.wallet.transactions.data')" :columns="$transactionColumns"
        title="Tenant Transactions" description="Transaction records linked to tenant subscription activity and balance movements."
        :order="[[1, 'asc']]" />

    <x-tenant::payment.gateway-modal
        id="subscription-payment"
        title="Renew or Upgrade Your Plan"
        :action="route('tenant.finance.wallet.subscribe')"
        :validate="route('tenant.finance.wallet.subscribe.validate')"
        :presented="$presented"
        submit-label="Confirm Payment"
    >
        <x-slot:fields>
            <x-tenant::radio-group name="package_id" label="Select a Plan" variant="cards" :columns="1"
                :value="$currentPackage?->id"
                :options="$packages->map(fn ($pkg) => [
                    'value' => $pkg->id,
                    'label' => $pkg->name . ' — ' . str($pkg->term->value)->headline() . ' · $' . number_format((float) $pkg->price, 2) . ($currentPackage && $currentPackage->id === $pkg->id ? ' (Current plan)' : ''),
                ])->all()" />
        </x-slot:fields>
    </x-tenant::payment.gateway-modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/wallet.js')
@endpush
