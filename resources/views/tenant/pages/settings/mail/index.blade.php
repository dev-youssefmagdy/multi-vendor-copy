@extends('tenant.layouts.app')

@section('title', 'Mail Configurations')

@section('content')
    <x-tenant::page-header title="Mail Configurations" badge="Settings" description="Store tenant-specific outbound mail overrides for storefront notifications.">
        <x-slot:actions>
            <button type="button" class="btn btn-secondary" data-modal-open="test-email-modal">Send Test Email</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::form id="mail-form" :action="route('tenant.settings.mail.update')" method="PUT" :validate="route('tenant.settings.mail.validate')">
        <x-tenant::schema-fields :groups="$groups" :values="$values" />

        <div class="page-actions compact-actions justify-end" style="margin-top:20px">
            <button type="submit" class="btn btn-primary">Save Mail Settings</button>
        </div>
    </x-tenant::form>

    <x-tenant::modal id="test-email-modal" title="Send Test Email">
        <x-tenant::form id="test-email-form" :action="route('tenant.settings.mail.test')" method="POST" :validate="route('tenant.settings.mail.test.validate')" success="close-modal">
            <x-tenant::input type="email" name="email" label="Recipient Email" required placeholder="you@example.com"
                help="A test email will be sent using the saved settings above, falling back to the central mail configuration for any blank fields." />

            <div class="page-actions compact-actions justify-end" style="margin-top:20px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Send Test Email</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/mail.js')
@endpush
