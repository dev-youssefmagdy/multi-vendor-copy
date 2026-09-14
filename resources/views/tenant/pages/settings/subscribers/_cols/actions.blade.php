@props(['subscriber'])

<div class="flex gap-2">
    <button type="button" class="btn btn-secondary btn-sm"
        data-action-url="{{ route('tenant.settings.subscribers.destroy', $subscriber) }}"
        data-action-method="DELETE"
        data-confirm="Delete subscriber?"
        data-success="reload-table:#subscribers-table">Delete</button>
</div>
