{{-- @var \App\Models\Tenant\SocialLink $link --}}
<div class="flex gap-2">
    <button type="button" class="btn btn-secondary btn-sm"
        data-modal-open="social-link-modal"
        data-modal-fill-url="{{ route('tenant.store.appearance.social.show', $link) }}"
        data-modal-mode="PUT"
        data-modal-action="{{ route('tenant.store.appearance.social.update', $link) }}">Edit</button>
    <button type="button" class="btn btn-secondary btn-sm"
        data-action-url="{{ route('tenant.store.appearance.social.destroy', $link) }}"
        data-action-method="DELETE"
        data-confirm="Delete this social link?"
        data-confirm-danger
        data-success="reload-page">Delete</button>
</div>
