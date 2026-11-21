<x-tenant::badge :color="$page->active ? 'green' : 'amber'">{{ $page->active ? __('Active') : __('Draft') }}</x-tenant::badge>
@if (($page->source ?? null) === 'central')
    <x-tenant::badge color="cyan">{{ __('Platform') }}</x-tenant::badge>
@endif
