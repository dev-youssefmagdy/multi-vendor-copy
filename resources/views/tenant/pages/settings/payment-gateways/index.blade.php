@extends('tenant.layouts.app')

@section('title', 'Payment Gateways')

@section('content')
    <x-tenant::page-header title="Payment Gateways" badge="Settings" description="Configure tenant payment gateways. Activation is synced from central settings." />

    <x-tenant::stats-grid :stats="$stats" />

    @if(!empty($recommendations))
        <section class="card fu d2 section-gap" style="padding:20px 24px;">
            <h3 class="panel-title" style="margin-bottom:4px;">Recommended Gateways for Your Target Markets</h3>
            <p class="panel-copy" style="margin-bottom:14px;">Based on your target countries, these gateways would maximize your customer coverage.</p>
            <div class="t-recommendation-grid">
                @foreach($recommendations as $rec)
                    <div class="t-recommendation-card">
                        <div class="entity-title">{{ $rec['name'] }}</div>
                        <div class="entity-subtitle" style="margin-bottom:8px;">Covers {{ $rec['score'] }} of your target countries</div>
                        @if(!empty($rec['meta']['payment_methods']))
                            <x-payment-method-badges :methods="$rec['meta']['payment_methods']" size="xs" />
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <x-tenant::datatable id="gateways-table" :url="route('tenant.settings.payment-gateways.data')" :columns="$columns" title="Gateway Configurations" />

    <x-tenant::modal id="gateway-modal" title="Configure Gateway" size="2xl">
        <x-tenant::form id="gateway-form" action="" method="PUT" success="close-modal reload-table:#gateways-table emit:tenant:setup-progress:refresh redirect">
            <input type="hidden" name="from" value="{{ $fromOnboarding ? 'onboarding' : '' }}">

            <div class="form-grid form-grid-2">
                <x-tenant::switch name="use_own" label="Use tenant credentials" description="Use your own configuration" />
                <x-tenant::switch name="sandbox_mode" label="Sandbox / test mode" description="Enable sandbox (test) mode" />
            </div>

            <x-tenant::field label="Webhook URL" wrapper-class="section-gap">
                <div class="t-input-wrap has-suffix">
                    <input type="text" class="field-control" data-webhook-url readonly>
                </div>
                <x-tenant::copy data-tenant-copy label="Copy webhook URL" />
            </x-tenant::field>

            <x-tenant::field name="required_fields" label="Gateway Credentials" wrapper-class="section-gap">
                <div class="form-grid form-grid-2" data-credential-fields hidden></div>
            </x-tenant::field>

            <template data-credential-field-template>
                <div class="t-field">
                    <label class="field-label" data-field-label></label>
                    <input class="field-control" data-field-input>
                </div>
            </template>

            <div class="page-actions compact-actions justify-end" style="margin-top:20px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary" data-gateway-submit-label>Save Gateway</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/payment-gateways.js')
@endpush
