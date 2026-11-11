@extends('tenant.layouts.app')

@section('title', 'Mail Configurations')

@section('content')
    <x-tenant::page-header title="Mail Configurations" badge="Settings" description="{{ __('Send your store emails from your own mail server, with the platform mail server as a safety net.') }}">
        <x-slot:actions>
            <button type="button" class="btn btn-secondary" data-modal-open="test-email-modal">Send Test Email</button>
        </x-slot:actions>
    </x-tenant::page-header>

    @include('tenant.pages.settings.mail.partials.status', ['mailStatus' => $mailStatus])

    <x-tenant::form id="mail-form" :action="route('tenant.settings.mail.update')" method="PUT" :validate="route('tenant.settings.mail.validate')">
        <x-tenant::schema-fields :groups="$groups" :values="$values" />

        <div class="page-actions compact-actions justify-end">
            <button type="submit" class="btn btn-primary">Save Mail Settings</button>
        </div>
    </x-tenant::form>

    <x-tenant::modal id="test-email-modal" title="Send Test Email">
        <x-tenant::form id="test-email-form" :action="route('tenant.settings.mail.test')" method="POST" :validate="route('tenant.settings.mail.test.validate')" success="close-modal">
            <x-tenant::input type="email" name="email" label="Recipient Email" required placeholder="you@example.com"
                help="{{ __('Sent with the saved settings: through your mail server when configured, otherwise through the platform mail server. A successful test re-activates a failed mail server.') }}" />

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Send Test Email</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/mail.js')
@endpush
