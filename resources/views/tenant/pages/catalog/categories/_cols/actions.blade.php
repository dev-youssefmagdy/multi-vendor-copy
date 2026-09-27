<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item :href="route('tenant.categories.edit', $category)">Edit</x-tenant::dropdown-item>
    <x-tenant::dropdown-item :href="route('tenant.categories.products', $category)">Sort Products</x-tenant::dropdown-item>
    <x-tenant::dropdown-item
        data-action-url="{{ route('tenant.categories.toggle-active', $category) }}"
        data-action-method="PATCH"
        data-success="reload-table:#categories-table">{{ $category->active ? 'Disable' : 'Enable' }}</x-tenant::dropdown-item>
    <x-tenant::dropdown-item
        data-action-url="{{ route('tenant.categories.toggle-featured', $category) }}"
        data-action-method="PATCH"
        data-success="reload-table:#categories-table">{{ $category->featured ? 'Unfeature' : 'Feature' }}</x-tenant::dropdown-item>
    @if($category->central_category_id === null)
        <x-tenant::dropdown-item danger
            data-action-url="{{ route('tenant.categories.destroy', $category) }}"
            data-action-method="DELETE"
            data-confirm="Are you sure you want to delete this category?"
            data-success="reload-table:#categories-table">Delete</x-tenant::dropdown-item>
    @endif
</x-tenant::dropdown>
