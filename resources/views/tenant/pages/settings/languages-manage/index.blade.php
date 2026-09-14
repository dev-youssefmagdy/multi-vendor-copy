@extends('tenant.layouts.app')

@section('title', 'Languages')

@section('content')
    <x-tenant::page-header title="Languages" badge="Settings" description="Manage your storefront languages, set the default locale, and purchase new language add-ons." />

    <x-tenant::datatable id="languages-manage-table" :url="route('tenant.settings.languages-manage.data')" :columns="$installedColumns"
        title="Installed Languages" description="Languages currently available in your store. Activate, disable, or set the default locale."
        empty-title="No languages installed"
        empty-copy="No languages have been set up in your tenant store yet."
        mode="server" :order="[[0, 'asc']]" />

    <x-tenant::datatable id="languages-available-table" :url="route('tenant.settings.languages-manage.available.data')" :columns="$availableColumns"
        title="Available Language Add-ons" description="Purchase a language once to unlock it permanently for your storefront and admin panel."
        empty-title="No languages available for purchase"
        empty-copy="You have already purchased all available paid language add-ons, or none have been added by the platform yet."
        mode="server" :order="[[1, 'asc']]" />

    <x-tenant::payment.gateway-modal
        id="buy-language-modal"
        title="Buy a Language"
        :action="route('tenant.settings.languages-manage.purchase')"
        :validate="route('tenant.settings.languages-manage.purchase.validate')"
        :presented="$presented"
        submit-label="Proceed to Payment"
    >
        <x-slot:summary>
            <div class="locale-fields-group lm-summary" data-buy-language-summary>
                <div class="lm-summary-row">
                    <div class="lm-summary-info">
                        <div class="entity-title">
                            <span data-buy-language-summary-name>—</span>
                            <span class="badge badge-green lm-summary-code" data-buy-language-summary-code></span>
                        </div>
                        <div class="entity-subtitle">One-time payment</div>
                    </div>
                    <div class="lm-summary-price" data-buy-language-summary-price>$0.00</div>
                </div>
            </div>
        </x-slot:summary>
        <x-slot:fields>
            <input type="hidden" name="language_id" value="" data-buy-language-field>
        </x-slot:fields>
    </x-tenant::payment.gateway-modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/languages-manage.js')
@endpush
