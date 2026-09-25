@php
    $dots = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5.5v.01M12 12v.01M12 18.5v.01"/></svg>';
@endphp
<x-tenant::dropdown align="end" :icon="$dots">
    <x-tenant::dropdown-item :href="route('tenant.customers.show', $customer->id)">Edit</x-tenant::dropdown-item>
    <x-tenant::dropdown-item danger
        data-action-url="{{ route('tenant.customers.destroy', $customer->id) }}"
        data-action-method="DELETE"
        data-confirm="Delete customer? This customer record will be removed from the tenant workspace."
        data-success="reload-table:#customers-table">Delete</x-tenant::dropdown-item>
</x-tenant::dropdown>
