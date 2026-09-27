<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item
        data-gateway-configure
        data-show-url="{{ route('tenant.settings.payment-gateways.show', $gateway) }}"
        data-modal-action="{{ route('tenant.settings.payment-gateways.update', $gateway) }}"
        data-modal-validate-url="{{ route('tenant.settings.payment-gateways.validate.update', $gateway) }}"
    >Configure</x-tenant::dropdown-item>

    <x-tenant::dropdown-item
        data-action-url="{{ route('tenant.settings.payment-gateways.check', $gateway) }}"
        data-action-method="POST"
        data-success="reload-table:#gateways-table"
    >Recheck</x-tenant::dropdown-item>

    @if($gateway->is_active && !$gateway->is_primary)
        <x-tenant::dropdown-item
            data-action-url="{{ route('tenant.settings.payment-gateways.primary', $gateway) }}"
            data-action-method="POST"
            data-success="reload-table:#gateways-table"
        >Set primary</x-tenant::dropdown-item>
    @endif
</x-tenant::dropdown>
