@extends('tenant.layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
    <x-tenant::page-header title="Roles & Permissions" badge="Tenant Access Matrix" description="Manage tenant-local role definitions and the permission slugs assigned to vendor admins.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-modal-open="role-modal">Add Role</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="roles-table">
        <x-tenant::input name="filters[search]" label="Search" placeholder="Role name" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="roles-table" :url="route('tenant.settings.roles-permissions.data')" :columns="$columns" title="Tenant Roles & Permission Sets" />

    <x-tenant::modal id="role-modal" title="Add / Edit Role" size="lg">
        <x-tenant::form id="role-form" :action="route('tenant.settings.roles-permissions.store')" method="POST" :validate="route('tenant.settings.roles-permissions.validate')" success="close-modal reload-table:#roles-table">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="name" label="Role Name" required maxlength="255" wrapper-class="span-2" />

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

            <div class="page-actions compact-actions justify-end" style="margin-top:20px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Role</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/roles-permissions.js')
@endpush
