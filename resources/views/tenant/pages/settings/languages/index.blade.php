@extends('tenant.layouts.app')

@section('title', 'Languages')

@section('content')
    <x-tenant::page-header title="Languages" badge="Settings" description="Control tenant storefront languages, activation, and the default locale." />

    <x-tenant::datatable id="languages-table" :url="route('tenant.settings.languages.data')" :columns="$columns"
        title="Tenant Languages" description="Languages available in your tenant store."
        empty-title="No languages installed"
        empty-copy="No languages have been set up in your tenant store yet."
        mode="server" :order="[[0, 'asc']]" />
@endsection
