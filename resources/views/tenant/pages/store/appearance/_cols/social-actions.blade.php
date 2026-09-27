{{-- @var \App\Models\Tenant\SocialLink $link --}}
<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item
        data-modal-open="social-link-modal"
        data-modal-fill-url="{{ route('tenant.store.appearance.social.show', $link) }}"
        data-modal-mode="PUT"
        data-modal-action="{{ route('tenant.store.appearance.social.update', $link) }}">Edit</x-tenant::dropdown-item>
    <x-tenant::dropdown-item danger
        data-action-url="{{ route('tenant.store.appearance.social.destroy', $link) }}"
        data-action-method="DELETE"
        data-confirm="Delete this social link?"
        data-confirm-danger
        data-success="reload-page">Delete</x-tenant::dropdown-item>
</x-tenant::dropdown>
