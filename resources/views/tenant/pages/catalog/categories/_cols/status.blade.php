<x-tenant::badge :color="$category->active ? 'green' : 'amber'">{{ $category->active ? 'Active' : 'Inactive' }}</x-tenant::badge>
@if($category->featured)
    <x-tenant::badge color="cyan">Featured</x-tenant::badge>
@endif
