@props(['currency'])

@if($currency->is_default)
    <span class="badge badge-green">Default</span>
@else
    <x-tenant::switch
        :checked="(bool) $currency->is_active"
        :action-url="route('tenant.settings.currencies.toggle-active', $currency)"
        action-method="PATCH"
        payload-key="active"
        label=""
    />
@endif
