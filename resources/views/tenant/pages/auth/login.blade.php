@extends('tenant.layouts.guest')

@section('title', 'Sign in')

@section('content')
    @php
        $tenant = tenant();
        $panelTitle = ($tenant?->data['shop_name'] ?? $tenant?->name ?? 'Tenant').' Vendor Panel';
    @endphp

    <main class="auth-page">
        <section class="auth-card card">
            <div class="auth-copy">
                <span class="page-badge">Tenant Workspace</span>
                <h1 class="D page-title auth-title">{{ $panelTitle }}</h1>
                <p class="page-copy auth-description">Sign in to manage products, orders, storefront settings, and vendor operations for this tenant.</p>
                <div class="auth-note">Use a tenant admin email and password created inside this tenant workspace.</div>
            </div>

            <div class="auth-panel">
                <x-tenant::form action="{{ route('tenant.login.attempt') }}" method="POST"
                    validate="{{ route('tenant.login.validate') }}" success="redirect" class="auth-form">
                    <x-tenant::input type="email" name="email" label="Email" autocomplete="email" id="login-email" />
                    <x-tenant::input type="password" name="password" label="Password" toggle autocomplete="current-password" />
                    <x-tenant::checkbox name="remember">Keep me signed in on this device</x-tenant::checkbox>

                    <x-tenant::submit class="auth-submit">Login to Vendor Panel</x-tenant::submit>
                </x-tenant::form>
            </div>
        </section>
    </main>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/auth/login.js')
@endpush
