<div class="flex gap-2 flex-wrap">
    <button type="button" class="btn btn-secondary btn-sm"
        data-modal-open="role-modal"
        data-modal-fill-url="{{ route('tenant.settings.roles-permissions.show', $role) }}"
        data-modal-action="{{ route('tenant.settings.roles-permissions.update', $role) }}"
        data-modal-method="PUT"
    >Edit</button>

    <button type="button" class="btn btn-secondary btn-sm"
        data-action-url="{{ route('tenant.settings.roles-permissions.destroy', $role) }}"
        data-action-method="DELETE"
        data-confirm="Admins assigned to this role will become unassigned."
        data-confirm-danger
        data-success="reload-table:#roles-table"
    >Delete</button>
</div>
