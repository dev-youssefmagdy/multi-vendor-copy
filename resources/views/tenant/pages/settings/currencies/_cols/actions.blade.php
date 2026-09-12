@props(['currency'])

<div class="flex gap-2">
    @unless($currency->is_default)
        <button type="button" class="btn btn-secondary btn-sm"
            data-action-url="{{ route('tenant.settings.currencies.default', $currency) }}"
            data-action-method="POST"
            data-confirm="Make {{ $currency->code }} the default currency?"
            data-success="reload-table:#currencies-table">Make default</button>
    @endunless
</div>
