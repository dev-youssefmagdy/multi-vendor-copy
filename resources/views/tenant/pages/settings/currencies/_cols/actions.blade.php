@props(['currency'])

@unless($currency->is_default)
    <x-tenant::dropdown align="end">
        <x-tenant::dropdown-item
            data-action-url="{{ route('tenant.settings.currencies.default', $currency) }}"
            data-action-method="POST"
            :data-confirm="__('Make :code the default currency?', ['code' => $currency->code])"
            data-success="reload-table:#currencies-table">{{ __('Make default') }}</x-tenant::dropdown-item>
    </x-tenant::dropdown>
@endunless
