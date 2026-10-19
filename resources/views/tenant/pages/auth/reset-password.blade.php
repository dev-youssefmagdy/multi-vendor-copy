@extends('tenant.layouts.guest')

@section('title', 'Reset password')

@section('content')
    <main class="ta-page">
        @include('tenant.pages.auth._brand')

        <section class="ta-card" aria-labelledby="ta-form-title">
            <div class="ta-card-head">
                <h1 class="ta-card-title" id="ta-form-title">Set a new password</h1>
                <p class="ta-card-sub">Choose a password with at least 8 characters.</p>
            </div>

            <x-tenant::form action="{{ route('tenant.password.update') }}" method="POST" success="redirect" class="ta-form">
                <input type="hidden" name="token" value="{{ $token }}">
                <x-tenant::input type="email" name="email" label="Email" :value="$email" autocomplete="email" id="login-email" />
                <x-tenant::input type="password" name="password" label="New password" placeholder="Enter new password" toggle autocomplete="new-password" />
                <x-tenant::input type="password" name="password_confirmation" label="Confirm password" placeholder="Repeat new password" toggle autocomplete="new-password" />

                <x-tenant::submit class="btn-lg ta-submit">Reset password</x-tenant::submit>
            </x-tenant::form>

            <a href="{{ route('tenant.login') }}" class="ta-link ta-back">Back to login</a>
        </section>
    </main>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/auth/login.js')
@endpush
