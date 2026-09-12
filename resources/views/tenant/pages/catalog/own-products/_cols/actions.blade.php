<div class="flex gap-2 flex-wrap">
    <a href="{{ route('tenant.own-products.edit', $product) }}" class="btn btn-secondary btn-sm">Edit</a>
    <button type="button" class="btn btn-secondary btn-sm btn-danger"
        data-action-url="{{ route('tenant.own-products.destroy', $product) }}"
        data-action-method="DELETE"
        data-confirm="Delete this product permanently?"
        data-success="reload-table:#own-products-table">Delete</button>
</div>
