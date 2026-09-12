@extends('tenant.layouts.app')

@section('title', 'Tracking')

@section('content')
    <x-tenant::page-header title="Tracking" badge="Settings" description="Connect Facebook, TikTok, Snapchat pixels and Google Analytics to your storefront." />

    <x-tenant::card class="form-card">
        <x-tenant::form id="tracking-form" :action="route('tenant.settings.tracking.update')" method="PUT" :validate="route('tenant.settings.tracking.validate')">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="fb_pixel_id" label="Facebook Pixel ID" :value="$values['fb_pixel_id']" maxlength="64" />
                <x-tenant::input name="tiktok_pixel_id" label="TikTok Pixel ID" :value="$values['tiktok_pixel_id']" maxlength="64" />
                <x-tenant::input name="snapchat_pixel_id" label="Snapchat Pixel ID" :value="$values['snapchat_pixel_id']" maxlength="64" />
                <x-tenant::input name="ga_measurement_id" label="GA4 Measurement ID" :value="$values['ga_measurement_id']" maxlength="32" placeholder="G-XXXXXXXX" />
            </div>

            <div class="page-actions compact-actions justify-end" style="margin-top:20px">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </x-tenant::form>
    </x-tenant::card>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/tracking.js')
@endpush
