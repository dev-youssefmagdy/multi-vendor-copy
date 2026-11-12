@extends('tenant.layouts.app')

@section('title', __('Tenant Admins'))

@section('content')
    <x-tenant::page-header :title="__('Tenant Admins')" :badge="__('Tenant Access')" :description="__('Manage vendor workspace administrators, role assignments, and access state inside this tenant.')">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-modal-open="admin-modal">{{ __('Add Tenant Admin') }}</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::stats-grid :stats="$stats" />

    <x-tenant::datatable id="admins-table" :url="route('tenant.settings.admins.data')" :columns="$columns" :title="__('Tenant Admin Users')" quick-search :search-placeholder="__('Name or email')">
        <x-slot:toolbar>
            <x-tenant::filters-card target="admins-table" :title="__('Filters')">
                <x-tenant::select2 name="status" :label="__('Status')" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" :placeholder="__('All statuses')" />
            </x-tenant::filters-card>
        </x-slot:toolbar>
    </x-tenant::datatable>

    <x-tenant::modal id="admin-modal" :title="__('Add / Edit Tenant Admin')">
        <x-tenant::form id="admin-form" :action="route('tenant.settings.admins.store')" method="POST" :validate="route('tenant.settings.admins.validate')" success="close-modal reload-table:#admins-table">
            <div class="form-grid form-grid-2">
                <x-tenant::input name="name" :label="__('Name')" required maxlength="255" wrapper-class="span-2" />
                <x-tenant::input type="email" name="email" :label="__('Email')" required maxlength="255" wrapper-class="span-2" />
                <x-tenant::select name="role_id" :label="__('Role')" wrapper-class="span-2" :options="['' => __('No role')] + $roleOptions" />
                <x-tenant::select name="status" :label="__('Status')" wrapper-class="span-2" :options="['active' => __('Active'), 'inactive' => __('Inactive')]" value="active" />
                <x-tenant::input type="password" name="password" :label="__('Password')" toggle wrapper-class="span-2" />
            </div>

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Save Admin') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/admins.js')
@endpush
