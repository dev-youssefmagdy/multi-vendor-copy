@extends('tenant.layouts.guest')

@section('title', __('Forgot password'))

@section('content')
    <main class="ta-page">
        @include('tenant.pages.auth._brand')

        <section class="ta-card" aria-labelledby="ta-form-title">
            <div class="ta-card-head">
                <h1 class="ta-card-title" id="ta-form-title">{{ __('Forgot password') }}</h1>
                <p class="ta-card-sub">{{ __('Enter your email and we\'ll send you a link to reset your password.') }}</p>
            </div>

            <x-tenant::form action="{{ route('tenant.password.email') }}" method="POST" success="reset" class="ta-form">
                <x-tenant::input type="email" name="email" :label="__('Email')" placeholder="you@store.com" autocomplete="email" id="login-email" />

                <x-tenant::submit class="btn-lg ta-submit">{{ __('Send reset link') }}</x-tenant::submit>
            </x-tenant::form>

            <a href="{{ route('tenant.login') }}" class="ta-link ta-back">{{ __('Back to login') }}</a>
        </section>
    </main>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/auth/login.js')
@endpush
