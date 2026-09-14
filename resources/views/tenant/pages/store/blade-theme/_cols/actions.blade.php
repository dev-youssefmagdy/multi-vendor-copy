<div class="flex gap-2 flex-wrap">
    @if($theme->is_active)
        <button type="button" class="btn btn-secondary btn-sm"
            data-action-url="{{ route('tenant.store.blade-theme.deactivate') }}"
            data-action-method="POST"
            data-confirm="Deactivate the blade theme? Your storefront will use the system theme again."
            data-success="reload-page">Deactivate</button>
    @else
        <button type="button" class="btn btn-danger btn-sm"
            data-action-url="{{ route('tenant.store.blade-theme.destroy', $theme) }}"
            data-action-method="DELETE"
            data-confirm="Delete this theme?"
            data-success="reload-page">Delete</button>
    @endif
</div>
