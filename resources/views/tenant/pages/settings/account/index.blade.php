@extends('tenant.layouts.app')

@section('title', 'Account Settings')

@section('content')
    @php
        $initials = strtoupper(substr($adminName ?: 'A', 0, 1))
                  . strtoupper(substr(strstr($adminName ?: '', ' ') ?: '', 1, 1));
    @endphp

    <x-tenant::page-header title="Account Settings" badge="Profile" description="Manage your admin credentials and store identity.">
        <x-slot:actions>
            <button type="submit" form="account-settings-form" class="btn btn-primary">Save Changes</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::form id="account-settings-form" :action="route('tenant.settings.account.update')" method="PUT"
        :validate="route('tenant.settings.account.validate')" success="redirect" class="acct-layout">
        <input type="hidden" name="from" value="{{ request('from') }}">

        {{-- ── Left column — Avatar card ─────────────────────────────── --}}
        <aside class="acct-sidebar">
            <div class="card acct-avatar-card fu d1">
                <div class="acct-avatar-wrap">
                    <div class="acct-avatar">
                        <span class="acct-avatar-initials">{{ $initials }}</span>
                    </div>
                </div>
                <div class="acct-avatar-meta">
                    <div class="acct-avatar-name D">{{ $adminName ?: 'Admin' }}</div>
                    <div class="acct-avatar-email">{{ $adminEmail }}</div>
                    <span class="page-badge" style="margin-top:8px;">Store Admin</span>
                </div>
                <div class="acct-info-list">
                    <div class="acct-info-row">
                        <svg class="acct-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="acct-info-text">{{ $shopName ?: '—' }}</span>
                    </div>
                    @if ($phone)
                        <div class="acct-info-row">
                            <svg class="acct-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span class="acct-info-text">{{ $phone }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </aside>

        {{-- ── Right column — Form sections ─────────────────────────── --}}
        <div class="acct-form-col">

            {{-- Profile & Store Identity --}}
            <section class="card form-card fu d2 section-gap">
                <div class="acct-section-head">
                    <div class="acct-section-icon-wrap">
                        <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="panel-title">Profile &amp; Store Identity</h3>
                        <p class="panel-copy">Your admin credentials and storefront-facing details.</p>
                    </div>
                </div>
                <div class="form-grid form-grid-2">
                    <x-tenant::input name="adminName" label="Admin Name" required maxlength="255" :value="$adminName" />
                    <x-tenant::input type="email" name="adminEmail" label="Admin Email" required maxlength="255" :value="$adminEmail" />
                    <x-tenant::phone name="phone" label="Store Phone" :value="$phone" />
                    <x-tenant::input name="shopName" label="Shop Name" required maxlength="255" :value="$shopName" />
                </div>
            </section>

            {{-- Store Details --}}
            <section class="card form-card fu d3 section-gap" id="store-details">
                <div class="acct-section-head">
                    <div class="acct-section-icon-wrap">
                        <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 21h18M5 21V7l8-4 8 4v14M9 21v-6h6v6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="panel-title">Store Details</h3>
                        <p class="panel-copy">Details shown to customers on your storefront.</p>
                    </div>
                </div>
                <div class="form-grid form-grid-2">
                    <x-tenant::input name="description" label="Store Description" maxlength="1000" :value="$description" wrapper-class="acct-span-full" />
                    <x-tenant::input name="address" label="Address" maxlength="255" :value="$address" wrapper-class="acct-span-full" />
                </div>
            </section>

            {{-- Password & Security --}}
            <section class="card form-card fu d4">
                <div class="acct-section-head">
                    <div class="acct-section-icon-wrap">
                        <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="panel-title">Password &amp; Security</h3>
                        <p class="panel-copy">Leave blank to keep your current password.</p>
                    </div>
                </div>
                <div class="form-grid form-grid-2">
                    <x-tenant::input type="password" name="password" label="New Password" toggle
                        help="Minimum 6 characters. Leave empty to keep your current password." wrapper-class="acct-span-full" />
                </div>
            </section>

            <div class="acct-mobile-save">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>

        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/account.js')
@endpush
