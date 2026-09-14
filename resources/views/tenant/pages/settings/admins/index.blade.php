@extends('tenant.layouts.app')

@section('title', 'Tenant Admins')

@section('content')
    <x-tenant::page-header title="Tenant Admins" badge="Tenant Access" description="Manage vendor workspace administrators, role assignments, and access state inside this tenant.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-modal-open="admin-modal">Add Tenant Admin</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::filters-card target="admins-table">
        <x-tenant::input name="filters[search]" label="Search" placeholder="Name or email" />
        <x-tenant::select name="filters[status]" label="Status" :options="['' => 'All statuses', 'active' => 'Active', 'inactive' => 'Inactive']" />
    </x-tenant::filters-card>

    <x-tenant::datatable id="admins-table" :url="route('tenant.settings.admins.data')" :columns="$columns" title="Tenant Admin Users" />

    <x-tenant::modal id="admin-modal" title="Add / Edit Tenant Admin">
        <x-tenant::form id="admin-form" :action="route('tenant.settings.admins.store')" method="POST" :validate="route('tenant.settings.admins.validate')" success="close-modal reload-table:#admins-table">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="name" label="Name" required maxlength="255" wrapper-class="span-2" />
                <x-tenant::input type="email" name="email" label="Email" required maxlength="255" wrapper-class="span-2" />
                <x-tenant::select name="role_id" label="Role" wrapper-class="span-2" :options="['' => 'No role'] + $roleOptions" />
                <x-tenant::select name="status" label="Status" wrapper-class="span-2" :options="['active' => 'Active', 'inactive' => 'Inactive']" value="active" />
                <x-tenant::input type="password" name="password" label="Password" toggle wrapper-class="span-2" />
            </div>

            <div class="page-actions compact-actions justify-end" style="margin-top:20px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Admin</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/admins.js')
@endpush
