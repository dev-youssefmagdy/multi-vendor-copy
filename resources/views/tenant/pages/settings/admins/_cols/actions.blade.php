@php($isActive = $admin->status === \App\Enums\ActivationStatus::Active)
<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item
        data-modal-open="admin-modal"
        data-modal-fill-url="{{ route('tenant.settings.admins.show', $admin) }}"
        data-modal-action="{{ route('tenant.settings.admins.update', $admin) }}"
        data-modal-method="PUT"
    >Edit</x-tenant::dropdown-item>

    @if($isActive)
        <x-tenant::dropdown-item
            data-action-url="{{ route('tenant.settings.admins.deactivate', $admin) }}"
            data-action-method="POST"
            data-success="reload-table:#admins-table"
        >Disable</x-tenant::dropdown-item>
    @else
        <x-tenant::dropdown-item
            data-action-url="{{ route('tenant.settings.admins.activate', $admin) }}"
            data-action-method="POST"
            data-success="reload-table:#admins-table"
        >Enable</x-tenant::dropdown-item>
    @endif

    @if((int) $currentId !== (int) $admin->id)
        <x-tenant::dropdown-item danger
            data-action-url="{{ route('tenant.settings.admins.destroy', $admin) }}"
            data-action-method="DELETE"
            data-confirm="This tenant admin record will be removed from this workspace."
            data-confirm-danger
            data-success="reload-table:#admins-table"
        >Delete</x-tenant::dropdown-item>
    @endif
</x-tenant::dropdown>
