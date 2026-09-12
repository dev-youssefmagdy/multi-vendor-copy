@php($isActive = $admin->status === \App\Enums\ActivationStatus::Active)
<div class="flex gap-2 flex-wrap">
    <button type="button" class="btn btn-secondary btn-sm"
        data-modal-open="admin-modal"
        data-modal-fill-url="{{ route('tenant.settings.admins.show', $admin) }}"
        data-modal-action="{{ route('tenant.settings.admins.update', $admin) }}"
        data-modal-method="PUT"
    >Edit</button>

    @if($isActive)
        <button type="button" class="btn btn-secondary btn-sm"
            data-action-url="{{ route('tenant.settings.admins.deactivate', $admin) }}"
            data-action-method="POST"
            data-success="reload-table:#admins-table"
        >Disable</button>
    @else
        <button type="button" class="btn btn-secondary btn-sm"
            data-action-url="{{ route('tenant.settings.admins.activate', $admin) }}"
            data-action-method="POST"
            data-success="reload-table:#admins-table"
        >Enable</button>
    @endif

    @if((int) $currentId !== (int) $admin->id)
        <button type="button" class="btn btn-secondary btn-sm"
            data-action-url="{{ route('tenant.settings.admins.destroy', $admin) }}"
            data-action-method="DELETE"
            data-confirm="This tenant admin record will be removed from this workspace."
            data-confirm-danger
            data-success="reload-table:#admins-table"
        >Delete</button>
    @endif
</div>
