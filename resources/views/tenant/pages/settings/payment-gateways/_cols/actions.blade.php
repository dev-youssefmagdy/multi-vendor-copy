<div class="flex gap-2">
    <button type="button" class="btn btn-secondary btn-sm"
        data-gateway-configure
        data-show-url="{{ route('tenant.settings.payment-gateways.show', $gateway) }}"
        data-modal-action="{{ route('tenant.settings.payment-gateways.update', $gateway) }}"
        data-modal-validate-url="{{ route('tenant.settings.payment-gateways.validate.update', $gateway) }}"
    >Configure</button>

    <button type="button" class="btn btn-outline btn-sm"
        data-action-url="{{ route('tenant.settings.payment-gateways.check', $gateway) }}"
        data-action-method="POST"
        data-success="reload-table:#gateways-table"
    >Recheck</button>

    @if($gateway->is_active && !$gateway->is_primary)
        <button type="button" class="btn btn-outline btn-sm"
            data-action-url="{{ route('tenant.settings.payment-gateways.primary', $gateway) }}"
            data-action-method="POST"
            data-success="reload-table:#gateways-table"
        >Set primary</button>
    @endif
</div>
