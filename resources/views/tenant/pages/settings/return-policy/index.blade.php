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

        <x-tenant::card class="form-card">
            <div class="panel-head mb-5">
                <div>
                    <h3 class="panel-title">{{ __('Cancellation & Refunds') }}</h3>
                    <p class="panel-copy">{{ __('Rules for cancelling orders, refunding payments and handling exchanges.') }}</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <x-tenant::checkbox name="cancellation_allow_processing" :checked="$orderPolicy['cancellation_allow_processing']" :label="__('Let customers cancel orders being processed')" :description="__('Pending orders can always be cancelled by the customer.')" />
                <x-tenant::input name="cancellation_window_hours" type="number" :label="__('Cancellation window (hours)')" :value="$orderPolicy['cancellation_window_hours']" min="0" max="8760" step="1" :help="__('Hours after placing an order during which a processing order can be cancelled. 0 means no limit.')" />
                <x-tenant::checkbox name="auto_refund_on_cancel" :checked="$orderPolicy['auto_refund_on_cancel']" :label="__('Refund cancelled paid orders automatically')" :description="__('Sends the refund to the original payment method when the gateway supports it.')" />
                <x-tenant::checkbox name="exchange_enabled" :checked="$orderPolicy['exchange_enabled']" :label="__('Allow exchanges')" :description="__('Customers can ask to swap an item for another variant of the same price.')" />
                <x-tenant::checkbox name="restock_returned_items" :checked="$orderPolicy['restock_returned_items']" :label="__('Restock returned items by default')" :description="__('Default for the restock option when you inspect a returned item.')" />
            </div>
        </x-tenant::card>

        <div class="page-actions compact-actions justify-end">
            <button type="submit" class="btn btn-primary">Save Policy</button>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/return-policy.js')
@endpush
