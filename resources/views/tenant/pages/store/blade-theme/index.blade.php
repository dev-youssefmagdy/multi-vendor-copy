@extends('tenant.layouts.app')

@section('title', __('Blade Theme'))

@section('content')
    <x-tenant::page-header :title="__('Blade Theme')" :badge="__('Storefront')" :description="__('Upload your own Blade storefront theme instead of using a system theme.')" />

    <div class="card form-card section-gap bt-starter-card">
        <div class="bt-starter-row">
            <div>
                <h3 class="panel-title bt-starter-title">{{ __('New to Blade themes?') }}</h3>
                <p class="panel-copy bt-starter-copy">
                    {{ __('Download the starter kit — a working theme with header, footer, and all home sections wired to real store data — and customize it as your own.') }}
                </p>
            </div>
            <a href="{{ route('tenant.store.blade-theme.starter-kit') }}" class="btn btn-secondary bt-starter-btn">
                {{ __('Download Starter Kit') }}
            </a>
        </div>
    </div>

    <x-tenant::card :title="__('Upload a Theme ZIP')">
        <p class="panel-copy">
            {{ __('The ZIP must contain') }} <code>layout/app.blade.php</code> {{ __('and') }}
            <code>pages/home/index.blade.php</code>{{ __('. Blade (') }}<code>.blade.php</code>{{ __('), CSS, JS, and common image/font files are allowed. Obvious PHP-execution patterns are rejected up front, but this is only a first pass — every upload is queued for admin review, and your theme cannot go live on your storefront until it is approved.') }}
        </p>

        <x-tenant::form id="bt-upload-form"
            action="{{ route('tenant.store.blade-theme.upload') }}"
            method="POST"
            :validate="route('tenant.store.blade-theme.upload.validate')"
            :files="true"
            success="reload-page">
            <div class="form-grid gs-grid">
                <x-tenant::file name="theme_zip" :label="__('Theme ZIP')" accept=".zip" required />

                <div id="bt-upload-progress" class="bt-upload-progress" hidden>
                    <x-tenant::progress :value="0" color="cyan" show-value />
                </div>

                <div class="gs-actions">
                    <button type="submit" class="btn btn-primary" data-bt-submit>{{ __('Upload theme') }}</button>
                </div>
            </div>
        </x-tenant::form>
    </x-tenant::card>

    <x-tenant::datatable id="blade-theme-table"
        mode="client"
        :rows="$rows"
        :columns="$columns"
        :title="__('Uploaded Themes')"
        :description="__('Approved themes are activated from Storefront → Online Store → Themes, alongside your other storefront theme options.')"
        :empty-title="__('No themes uploaded yet')"
        :empty-copy="__('Upload a theme ZIP above to get started.')" />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/blade-theme.js')
@endpush
