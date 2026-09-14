@props(['coupon'])

<div class="flex gap-2">
    <button type="button" class="btn btn-secondary btn-sm"
        data-modal-open="coupon-modal"
        data-modal-fill-url="{{ route('tenant.store.coupons.show', $coupon) }}"
        data-modal-action="{{ route('tenant.store.coupons.update', $coupon) }}"
        data-modal-method="PUT">Edit</button>
    <button type="button" class="btn btn-danger btn-sm"
        data-action-url="{{ route('tenant.store.coupons.destroy', $coupon) }}"
        data-action-method="DELETE"
        data-confirm="Delete coupon?"
        data-success="reload-table:#coupons-table">Delete</button>
</div>
