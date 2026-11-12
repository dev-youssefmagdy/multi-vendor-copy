@extends('tenant.layouts.app')

@section('title', __('Languages'))

@section('content')
    <x-tenant::page-header :title="__('Languages')" :badge="__('Settings')" :description="__('Control tenant storefront languages, activation, and the default locale.')" />

    <x-tenant::datatable id="languages-table" :url="route('tenant.settings.languages.data')" :columns="$columns"
        :title="__('Tenant Languages')" :description="__('Languages available in your tenant store.')"
        :empty-title="__('No languages installed')"
        :empty-copy="__('No languages have been set up in your tenant store yet.')"
        mode="server" :order="[[0, 'asc']]" />
@endsection
