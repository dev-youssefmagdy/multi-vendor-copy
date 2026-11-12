@extends('tenant.layouts.app')

@section('title', __('Roles & Permissions'))

@section('content')
    <x-tenant::page-header :title="__('Roles & Permissions')" :badge="__('Tenant Access Matrix')" :description="__('Manage tenant-local role definitions and the permission slugs assigned to vendor admins.')">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-modal-open="role-modal">{{ __('Add Role') }}</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="roles-table" :url="route('tenant.settings.roles-permissions.data')" :columns="$columns" :title="__('Tenant Roles & Permission Sets')" quick-search :search-placeholder="__('Role name')" />

    <x-tenant::modal id="role-modal" :title="__('Add / Edit Role')" size="lg">
        <x-tenant::form id="role-form" :action="route('tenant.settings.roles-permissions.store')" method="POST" :validate="route('tenant.settings.roles-permissions.validate')" success="close-modal reload-table:#roles-table">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="name" :label="__('Role Name')" required maxlength="255" wrapper-class="span-2" />

                @foreach($permissionGroups as $group)
                    <x-tenant::checkbox-group
                        name="permissions"
                        :label="$group['label']"
                        :options="$group['options']"
                        select-all
                        wrapper-class="span-2"
                    />
                @endforeach
            </div>

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Save Role') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/roles-permissions.js')
@endpush
