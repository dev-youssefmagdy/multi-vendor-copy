<x-tenant::badge :color="$product->active ? 'green' : 'amber'">{{ $product->active ? 'Active' : 'Inactive' }}</x-tenant::badge>
@if($product->featured)
    <x-tenant::badge color="cyan">Featured</x-tenant::badge>
@endif
