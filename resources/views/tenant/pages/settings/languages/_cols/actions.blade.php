<div class="flex gap-2 flex-wrap">
    <button type="button" class="btn btn-secondary btn-sm"
        data-action-url="{{ route('tenant.settings.languages.toggle-active', $language) }}"
        data-action-method="PATCH"
        data-success="reload-table:#languages-table">
        {{ $language->is_active ? 'Disable' : 'Enable' }}
    </button>
    @unless ($language->is_default)
        <button type="button" class="btn btn-secondary btn-sm"
            data-action-url="{{ route('tenant.settings.languages.default', $language) }}"
            data-action-method="POST"
            data-success="reload-table:#languages-table">
            Make Default
        </button>
    @endunless
</div>
