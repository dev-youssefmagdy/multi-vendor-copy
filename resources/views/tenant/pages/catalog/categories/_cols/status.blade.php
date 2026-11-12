<x-tenant::badge :color="$category->active ? 'green' : 'amber'">{{ $category->active ? __('Active') : __('Inactive') }}</x-tenant::badge>
@if($category->featured)
    <x-tenant::badge color="cyan">{{ __('Featured') }}</x-tenant::badge>
@endif
