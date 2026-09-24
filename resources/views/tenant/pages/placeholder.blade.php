@extends('tenant.layouts.app')

{{-- Empty page for sidebar modules that exist in the design but aren't built yet. --}}

@section('title', $title)

@section('content')
    <x-tenant::page-header :title="$title" />

    <x-tenant::card>
        <x-tenant::empty-state title="Coming soon" copy="This section is not available yet." />
    </x-tenant::card>
@endsection
