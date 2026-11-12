<div class="products-status-pills">
    <x-tenant::badge :color="$product->active ? 'green' : 'amber'">{{ $product->active ? __('Active') : __('Inactive') }}</x-tenant::badge>
    @if($product->featured)
        <x-tenant::badge color="cyan">{{ __('Featured') }}</x-tenant::badge>
    @endif
</div>
