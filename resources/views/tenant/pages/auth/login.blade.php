@extends('tenant.layouts.guest')

@section('title', 'Sign in')

@section('content')
    @php
        $tenant = tenant();
        $panelTitle = ($tenant?->data['shop_name'] ?? $tenant?->name ?? 'Tenant').' Vendor Panel';
    @endphp

    {{-- Sign-in (design system): black brand panel + white form card; stacks on mobile. --}}
    <main class="ta-page">
        <section class="ta-brand" aria-labelledby="ta-title">
            <img src="{{ asset('tenant-panel/mobile-logo.svg') }}" alt="NOGRGR" width="153" height="40" class="ta-logo">

            <div class="ta-brand-copy">
                <span class="ta-badge">Tenant Workspace</span>
                <h1 class="ta-title" id="ta-title">{{ $panelTitle }}</h1>
                <p class="ta-description">Sign in to manage products, orders, storefront settings, and vendor operations for this tenant.</p>
                <p class="ta-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.75"/><path d="M12 16v-4.5M12 8.01V8"/></svg>
                    Use a tenant admin email and password created inside this tenant workspace.
                </p>
            </div>
        </section>

        <section class="ta-card" aria-labelledby="ta-form-title">
            <div class="ta-card-head">
                <h2 class="ta-card-title" id="ta-form-title">Sign in</h2>
                <p class="ta-card-sub">Welcome back! Enter your details to continue.</p>
            </div>

            <x-tenant::form action="{{ route('tenant.login.attempt') }}" method="POST"
                validate="{{ route('tenant.login.validate') }}" success="redirect" class="ta-form">
                <x-tenant::input type="email" name="email" label="Email" placeholder="you@store.com" autocomplete="email" id="login-email" />
                <x-tenant::input type="password" name="password" label="Password" placeholder="Enter your password" toggle autocomplete="current-password" />
                <x-tenant::checkbox name="remember" wrapper-class="ds-check">Keep me signed in on this device</x-tenant::checkbox>

                <x-tenant::submit class="btn-lg ta-submit">Login to Vendor Panel</x-tenant::submit>
            </x-tenant::form>
        </section>
    </main>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/auth/login.js')
@endpush
