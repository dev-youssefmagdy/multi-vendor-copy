<div class="flex gap-2 flex-wrap">
    <button type="button" class="btn btn-secondary btn-sm"
        data-action-url="{{ route('tenant.settings.languages-manage.toggle-active', $language) }}"
        data-action-method="PATCH"
        data-success="reload-table:#languages-manage-table">
        {{ $language->is_active ? 'Disable' : 'Enable' }}
    </button>
    @unless ($language->is_default)
        <button type="button" class="btn btn-secondary btn-sm"
            data-action-url="{{ route('tenant.settings.languages-manage.default', $language) }}"
            data-action-method="POST"
            data-success="reload-table:#languages-manage-table">
            Make Default
        </button>
    @endunless
</div>
