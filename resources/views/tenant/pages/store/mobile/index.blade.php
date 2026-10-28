@extends('tenant.layouts.app')

@section('title', 'Mobile App')

@section('content')
    <x-tenant::page-header title="Mobile App" badge="Storefront" description="Configure your store's mobile app: branding, features, navigation, links and release controls." />

    <section class="card form-card">
        <h3 class="panel-title">Deep links</h3>
        <p class="panel-copy">Customers open this store in the app using its code or domain.</p>
        <p><strong>Store code:</strong> <code>{{ $tenantCode }}</code></p>
        <p><strong>Domain:</strong> <code>{{ $domain ?? '—' }}</code></p>
    </section>

    <x-tenant::form id="mobile-app-form" :action="route('tenant.store.mobile-app.save')" method="PUT"
        :validate="route('tenant.store.mobile-app.validate')" :files="true" success="none">
        <section class="card form-card">
            <div class="panel-head mb-5">
                <div><h3 class="panel-title">Branding</h3></div>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
            <div class="form-grid form-grid-2">
                <x-tenant::input name="app_name" label="App name" :value="$s['app_name'] ?? ''" />
                <x-tenant::input name="primary_color" label="Primary color override" :value="$s['primary_color'] ?? ''" placeholder="#RRGGBB" />
                <x-tenant::input name="secondary_color" label="Secondary color override" :value="$s['secondary_color'] ?? ''" placeholder="#RRGGBB" />
                <x-tenant::input name="logo" type="file" label="Logo" />
                <x-tenant::input name="splash" type="file" label="Splash image" />
            </div>
            @if(!empty($s['logo']))<p><img src="{{ $s['logo'] }}" alt="" style="max-height:48px"></p>@endif
        </section>

        <section class="card form-card">
            <h3 class="panel-title">Features</h3>
            @foreach($featureKeys as $f)
                <x-tenant::switch :name="'features['.$f.']'" :checked="$features[$f]" :label="ucfirst(str_replace('_', ' ', $f))" />
            @endforeach
        </section>

        <section class="card form-card">
            <h3 class="panel-title">Bottom navigation (max 5)</h3>
            <div class="form-grid form-grid-2">
                @foreach($navItems as $n)
                    <label><input type="checkbox" name="nav_items[]" value="{{ $n }}" @checked(in_array($n, $navSelected, true))> {{ ucfirst($n) }}</label>
                @endforeach
            </div>
        </section>

        <section class="card form-card">
            <h3 class="panel-title">Locale, support and store links</h3>
            <div class="form-grid form-grid-2">
                <x-tenant::input name="default_locale" label="Default locale" :value="$s['default_locale'] ?? ''" />
                <x-tenant::input name="default_currency" label="Default currency" :value="$s['default_currency'] ?? ''" />
                <x-tenant::input name="support_email" label="Support email" :value="$s['support_email'] ?? ''" />
                <x-tenant::input name="support_phone" label="Support phone" :value="$s['support_phone'] ?? ''" />
                <x-tenant::input name="support_whatsapp" label="Support WhatsApp" :value="$s['support_whatsapp'] ?? ''" />
                <x-tenant::input name="play_store_url" label="Google Play URL" :value="$s['play_store_url'] ?? ''" />
                <x-tenant::input name="app_store_url" label="App Store URL" :value="$s['app_store_url'] ?? ''" />
            </div>
        </section>

        <section class="card form-card">
            <h3 class="panel-title">Release controls</h3>
            <div class="form-grid form-grid-2">
                <x-tenant::input name="min_app_version" label="Minimum app version (force update)" :value="$s['min_app_version'] ?? ''" placeholder="1.0.0" />
                <x-tenant::switch name="maintenance" :checked="(bool) ($s['maintenance'] ?? false)" label="Maintenance mode" />
                <x-tenant::input name="maintenance_message" label="Maintenance message" :value="$s['maintenance_message'] ?? ''" />
            </div>
        </section>
    </x-tenant::form>
@endsection
