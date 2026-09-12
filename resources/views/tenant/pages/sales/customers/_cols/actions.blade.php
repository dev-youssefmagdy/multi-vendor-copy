<div class="flex gap-2">
    <a href="{{ route('tenant.customers.show', $customer->id) }}" class="btn btn-primary btn-sm">Edit</a>
    <button type="button" class="btn btn-secondary btn-sm"
        data-action-url="{{ route('tenant.customers.destroy', $customer->id) }}"
        data-action-method="DELETE"
        data-confirm="Delete customer? This customer record will be removed from the tenant workspace."
        data-success="reload-table:#customers-table">Delete</button>
</div>
