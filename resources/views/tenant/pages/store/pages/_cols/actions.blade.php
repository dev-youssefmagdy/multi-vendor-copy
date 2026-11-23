<x-tenant::dropdown align="end">
    <x-tenant::dropdown-item :href="route('tenant.store.pages.edit', $page)">{{ __('Edit') }}</x-tenant::dropdown-item>
    @unless(\App\Http\Controllers\Tenant\Panel\Store\PagesController::isPlatformPage($page))
    <x-tenant::dropdown-item danger
        data-action-url="{{ route('tenant.store.pages.destroy', $page) }}"
        data-action-method="DELETE"
        :data-confirm="__('Delete page?')"
        data-success="reload-table:#pages-table">{{ __('Delete') }}</x-tenant::dropdown-item>
    @endunless
</x-tenant::dropdown>
