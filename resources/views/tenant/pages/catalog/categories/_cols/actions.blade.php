<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item :href="route('tenant.categories.edit', $category)">{{ __('Edit') }}</x-tenant::dropdown-item>
    <x-tenant::dropdown-item :href="route('tenant.categories.products', $category)">{{ __('Sort Products') }}</x-tenant::dropdown-item>
    <x-tenant::dropdown-item
        data-action-url="{{ route('tenant.categories.toggle-active', $category) }}"
        data-action-method="PATCH"
        data-success="reload-table:#categories-table">{{ $category->active ? __('Disable') : __('Enable') }}</x-tenant::dropdown-item>
    <x-tenant::dropdown-item
        data-action-url="{{ route('tenant.categories.toggle-featured', $category) }}"
        data-action-method="PATCH"
        data-success="reload-table:#categories-table">{{ $category->featured ? __('Unfeature') : __('Feature') }}</x-tenant::dropdown-item>
    @if($category->central_category_id === null)
        <x-tenant::dropdown-item danger
            data-action-url="{{ route('tenant.categories.destroy', $category) }}"
            data-action-method="DELETE"
            :data-confirm="__('Are you sure you want to delete this category?')"
            data-success="reload-table:#categories-table">{{ __('Delete') }}</x-tenant::dropdown-item>
    @endif
</x-tenant::dropdown>
