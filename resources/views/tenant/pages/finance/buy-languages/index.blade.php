@extends('tenant.layouts.app')

@section('title', 'Buy Languages')

@section('content')
    <x-tenant::page-header title="Buy Languages" badge="Finance" description="Purchase premium language add-ons to expand your store's multilingual reach." />

    <x-tenant::datatable id="buy-languages-table" :url="route('tenant.finance.buy-languages.data')" :columns="$columns"
        title="Available Language Add-ons" description="Purchase a language to unlock it for your storefront and admin panel."
        empty-title="No languages available for purchase"
        empty-copy="You have already purchased all available paid language add-ons, or none have been added by the platform yet."
        :order="[[1, 'asc']]" />

    <x-tenant::payment.gateway-modal
        id="buy-language-modal"
        title="Buy a Language"
        :action="route('tenant.finance.buy-languages.purchase')"
        :validate="route('tenant.finance.buy-languages.purchase.validate')"
        :presented="$presented"
        submit-label="Proceed to Payment"
    >
        <x-slot:summary>
            <div class="locale-fields-group" data-buy-language-summary style="margin-bottom:4px;">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="flex:1;min-width:0;">
                        <div class="entity-title">
                            <span data-buy-language-summary-name>—</span>
                            <span class="badge badge-green" data-buy-language-summary-code style="margin-left:6px;vertical-align:middle;"></span>
                        </div>
                        <div class="entity-subtitle">One-time payment</div>
                    </div>
                    <div style="font-size:18px;font-weight:700;color:var(--accent);white-space:nowrap;" data-buy-language-summary-price>$0.00</div>
                </div>
            </div>
        </x-slot:summary>
        <x-slot:fields>
            <input type="hidden" name="language_id" value="" data-buy-language-field>
        </x-slot:fields>
    </x-tenant::payment.gateway-modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/buy-languages.js')
@endpush
