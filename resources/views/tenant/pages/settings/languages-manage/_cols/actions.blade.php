<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item
        data-action-url="{{ route('tenant.settings.languages-manage.toggle-active', $language) }}"
        data-action-method="PATCH"
        data-success="reload-table:#languages-manage-table">{{ $language->is_active ? 'Disable' : 'Enable' }}</x-tenant::dropdown-item>
    @unless ($language->is_default)
        <x-tenant::dropdown-item
            data-action-url="{{ route('tenant.settings.languages-manage.default', $language) }}"
            data-action-method="POST"
            data-success="reload-table:#languages-manage-table">Make Default</x-tenant::dropdown-item>
    @endunless
</x-tenant::dropdown>
