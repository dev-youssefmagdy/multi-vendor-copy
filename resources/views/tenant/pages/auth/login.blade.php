@extends('tenant.layouts.guest')

@section('title', 'Sign in')

@section('content')
    <main class="ta-page">
        @include('tenant.pages.auth._brand')

        <section class="ta-card" aria-labelledby="ta-form-title">
            <div class="ta-card-head">
                <h1 class="ta-card-title" id="ta-form-title">Login to vendor panel</h1>
                <p class="ta-card-sub">Enter your email and password.</p>
            </div>

            <x-tenant::form action="{{ route('tenant.login.attempt') }}" method="POST"
                validate="{{ route('tenant.login.validate') }}" success="redirect" class="ta-form">
                <x-tenant::input type="email" name="email" label="Email" placeholder="you@store.com" autocomplete="email" id="login-email" />
                <x-tenant::input type="password" name="password" label="Password" placeholder="Enter your password" toggle autocomplete="current-password" />

                <div class="ta-row">
                    <x-tenant::checkbox name="remember" wrapper-class="ds-check">Remember me</x-tenant::checkbox>
                    <a href="{{ route('tenant.password.request') }}" class="ta-link">Forgot password?</a>
                </div>

                <x-tenant::submit class="btn-lg ta-submit">Login</x-tenant::submit>
            </x-tenant::form>
        </section>
    </main>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/auth/login.js')
@endpush
