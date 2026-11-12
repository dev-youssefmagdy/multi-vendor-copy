@extends('tenant.layouts.app')

@section('title', __('New Support Ticket'))

@section('content')
    <x-tenant::page-header :title="__('New Support Ticket')" :badge="__('Help Desk')" :description="__('Describe your issue and the marketplace admin team will get back to you.')">
        <x-slot:actions>
            <a class="btn btn-secondary" href="{{ route('tenant.support.index') }}">{{ __('← Tickets') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <section class="card form-card fu d1">
        <div class="panel-head mb-5">
            <div>
                <h3 class="panel-title">{{ __('Ticket Details') }}</h3>
                <p class="panel-copy">{{ __('Give as much context as possible so the admin team can help quickly.') }}</p>
            </div>
        </div>

        <x-tenant::form
            action="{{ route('tenant.support.store') }}"
            validate="{{ route('tenant.support.validate') }}"
            success="redirect"
            gate-submit
        >
            <div class="form-grid">
                <x-tenant::input name="subject" :label="__('Subject')" required maxlength="255" :placeholder="__('Short summary of your issue')" />

                <x-tenant::select name="category" :label="__('Category')" required :options="$categoryOptions" value="general" />

                <div class="form-grid-full">
                    <x-tenant::textarea name="body" :label="__('Message')" rows="6" required maxlength="5000" counter :help="__('At least 10 characters.')" :placeholder="__('Describe your issue in detail')" />
                </div>
            </div>

            <div class="page-actions compact-actions justify-end">
                <a class="btn btn-secondary" href="{{ route('tenant.support.index') }}">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Submit Ticket') }}</button>
            </div>
        </x-tenant::form>
    </section>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/support/ticket-create.js')
@endpush
