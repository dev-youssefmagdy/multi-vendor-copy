<x-tenant::dropdown align="end">
    @if($theme->is_active)
        <x-tenant::dropdown-item
            data-action-url="{{ route('tenant.store.blade-theme.deactivate') }}"
            data-action-method="POST"
            data-confirm="Deactivate the blade theme? Your storefront will use the system theme again."
            data-success="reload-page">Deactivate</x-tenant::dropdown-item>
    @else
        <x-tenant::dropdown-item danger
            data-action-url="{{ route('tenant.store.blade-theme.destroy', $theme) }}"
            data-action-method="DELETE"
            data-confirm="Delete this theme?"
            data-success="reload-page">Delete</x-tenant::dropdown-item>
    @endif
</x-tenant::dropdown>
