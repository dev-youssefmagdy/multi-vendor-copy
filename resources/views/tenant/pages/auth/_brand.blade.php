@php
    $tenant = tenant();
    $logoUrl = \App\Models\Tenant\Setting::query()->where('name', 'logo_path_en')->value('value')
        ?: \App\Models\Tenant\Setting::query()->where('name', 'logo_path_ar')->value('value')
        ?: ($tenant?->data['logo_url'] ?? null);
    $logoAlt = $tenant?->data['shop_name'] ?? $tenant?->name ?? 'Logo';
@endphp

<section class="ta-brand" aria-label="{{ $logoAlt }}">
    <span class="ta-logo-plate">
        <img src="{{ $logoUrl ?: asset('tenant-panel/logo.svg') }}" alt="{{ $logoAlt }}" class="ta-logo">
    </span>
</section>
