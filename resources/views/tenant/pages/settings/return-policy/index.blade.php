@extends('tenant.layouts.app')

@section('title', 'Return Policy')

@section('content')
    <x-tenant::page-header title="Return Policy" badge="Store" description="Return policy applied to your own products (not the central Neozena catalog)." />

    <x-tenant::form id="return-policy-form" :action="route('tenant.settings.return-policy.update')" method="PUT" :validate="route('tenant.settings.return-policy.validate')">
        <x-tenant::schema-fields :groups="$groups" :values="$values" />

        <x-tenant::card class="form-card">
            <div class="form-grid form-grid-2">
                <div class="span-2">
                    <x-tenant::select2
                        name="non_returnable_ids"
                        label="Non-returnable Products"
                        multiple
                        :ajax-url="route('tenant.store.flash-sales.products.search')"
                        :selected="$nonReturnableSelected"
                        :value="$values['non_returnable_ids']"
                        help="Products that cannot be returned by customers."
                    />
                </div>
                <div class="span-2">
                    <x-tenant::checkbox-group
                        name="video_required_reasons"
                        label="Reasons Requiring Video"
                        :options="$videoReasonOptions"
                        :value="$values['video_required_reasons']"
                        help="Return reasons that require a video from the customer."
                    />
                </div>
            </div>
        </x-tenant::card>

        <div class="page-actions compact-actions justify-end" style="margin-top:20px">
            <button type="submit" class="btn btn-primary">Save Policy</button>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/return-policy.js')
@endpush
