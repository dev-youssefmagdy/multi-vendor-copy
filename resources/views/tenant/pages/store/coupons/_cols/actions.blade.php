@props(['coupon'])

<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item
        data-modal-open="coupon-modal"
        data-modal-fill-url="{{ route('tenant.store.coupons.show', $coupon) }}"
        data-modal-action="{{ route('tenant.store.coupons.update', $coupon) }}"
        data-modal-method="PUT">{{ __('Edit') }}</x-tenant::dropdown-item>
    <x-tenant::dropdown-item danger
        data-action-url="{{ route('tenant.store.coupons.destroy', $coupon) }}"
        data-action-method="DELETE"
        :data-confirm="__('Delete coupon?')"
        data-success="reload-table:#coupons-table">{{ __('Delete') }}</x-tenant::dropdown-item>
</x-tenant::dropdown>
