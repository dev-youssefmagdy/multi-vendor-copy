<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item :href="route('tenant.store.pages.edit', $page)">Edit</x-tenant::dropdown-item>
    <x-tenant::dropdown-item danger
        data-action-url="{{ route('tenant.store.pages.destroy', $page) }}"
        data-action-method="DELETE"
        data-confirm="Delete page?"
        data-success="reload-table:#pages-table">Delete</x-tenant::dropdown-item>
</x-tenant::dropdown>
