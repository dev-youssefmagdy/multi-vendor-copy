@extends('tenant.layouts.guest')

@section('title', __('Reset password'))

@section('content')
    <main class="ta-page">
        @include('tenant.pages.auth._brand')

        <section class="ta-card" aria-labelledby="ta-form-title">
            <div class="ta-card-head">
                <h1 class="ta-card-title" id="ta-form-title">{{ __('Set a new password') }}</h1>
                <p class="ta-card-sub">{{ __('Choose a password with at least 8 characters.') }}</p>
            </div>

            <x-tenant::form action="{{ route('tenant.password.update') }}" method="POST" success="redirect" class="ta-form">
                <input type="hidden" name="token" value="{{ $token }}">
                <x-tenant::input type="email" name="email" :label="__('Email')" :value="$email" autocomplete="email" id="login-email" />
                <x-tenant::input type="password" name="password" :label="__('New password')" :placeholder="__('Enter new password')" toggle autocomplete="new-password" />
                <x-tenant::input type="password" name="password_confirmation" :label="__('Confirm password')" :placeholder="__('Repeat new password')" toggle autocomplete="new-password" />

                <x-tenant::submit class="btn-lg ta-submit">{{ __('Reset password') }}</x-tenant::submit>
            </x-tenant::form>

            <a href="{{ route('tenant.login') }}" class="ta-link ta-back">{{ __('Back to login') }}</a>
        </section>
    </main>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/auth/login.js')
@endpush
