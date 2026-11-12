<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item
        data-modal-open="role-modal"
        data-modal-fill-url="{{ route('tenant.settings.roles-permissions.show', $role) }}"
        data-modal-action="{{ route('tenant.settings.roles-permissions.update', $role) }}"
        data-modal-method="PUT"
    >{{ __('Edit') }}</x-tenant::dropdown-item>

    <x-tenant::dropdown-item danger
        data-action-url="{{ route('tenant.settings.roles-permissions.destroy', $role) }}"
        data-action-method="DELETE"
        :data-confirm="__('Admins assigned to this role will become unassigned.')"
        data-confirm-danger
        data-success="reload-table:#roles-table"
    >{{ __('Delete') }}</x-tenant::dropdown-item>
</x-tenant::dropdown>
