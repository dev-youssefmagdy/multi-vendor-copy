@props(['flashSale'])

<div class="flex gap-2">
    <button type="button" class="btn btn-secondary btn-sm"
        data-modal-open="flash-sale-modal"
        data-modal-fill-url="{{ route('tenant.store.flash-sales.show', $flashSale) }}"
        data-modal-action="{{ route('tenant.store.flash-sales.update', $flashSale) }}"
        data-modal-method="PUT">Edit</button>
    <button type="button" class="btn btn-danger btn-sm"
        data-action-url="{{ route('tenant.store.flash-sales.destroy', $flashSale) }}"
        data-action-method="DELETE"
        data-confirm="Delete flash sale?"
        data-success="reload-table:#flash-sales-table">Delete</button>
</div>
