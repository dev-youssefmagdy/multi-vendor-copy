<div class="flex gap-2 flex-wrap">
    <a href="{{ route('tenant.store.pages.edit', $page) }}" class="btn btn-secondary btn-sm">Edit</a>
    <button type="button" class="btn btn-danger btn-sm"
        data-action-url="{{ route('tenant.store.pages.destroy', $page) }}"
        data-action-method="DELETE"
        data-confirm="Delete page?"
        data-success="reload-table:#pages-table">Delete</button>
</div>
