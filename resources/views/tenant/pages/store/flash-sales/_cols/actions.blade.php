@props(['flashSale'])

<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item
        data-modal-open="flash-sale-modal"
        data-modal-fill-url="{{ route('tenant.store.flash-sales.show', $flashSale) }}"
        data-modal-action="{{ route('tenant.store.flash-sales.update', $flashSale) }}"
        data-modal-method="PUT">Edit</x-tenant::dropdown-item>
    <x-tenant::dropdown-item danger
        data-action-url="{{ route('tenant.store.flash-sales.destroy', $flashSale) }}"
        data-action-method="DELETE"
        data-confirm="Delete flash sale?"
        data-success="reload-table:#flash-sales-table">Delete</x-tenant::dropdown-item>
</x-tenant::dropdown>
