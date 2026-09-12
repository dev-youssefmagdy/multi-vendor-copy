@extends('tenant.layouts.app')

@section('title', 'General Settings')

@section('content')
    <x-tenant::page-header title="General Settings" badge="Settings" description="Store-wide defaults used across the vendor control panel.">
        <x-slot:actions>
            <button type="button" class="btn btn-secondary" data-modal-open="country-request-modal">Request Country Change</button>
            <button type="button" class="btn btn-secondary" data-modal-open="category-request-modal">Request Category Change</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::form id="general-form" :action="route('tenant.settings.general.update')" method="PUT" :validate="route('tenant.settings.general.validate')">
        <x-tenant::schema-fields :groups="$groups" :values="$values" />

        <div class="page-actions compact-actions justify-end" style="margin-top:20px">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </x-tenant::form>

    <x-tenant::card class="form-card">
        <div class="panel-head mb-5">
            <div>
                <h3 class="panel-title">Target Countries</h3>
                <p class="panel-copy">
                    @foreach($currentCountries as $country)
                        <span class="badge mr-1">{{ $country->flag_emoji }} {{ $country->name }}</span>
                    @endforeach
                    @if($currentCountries->isEmpty())No countries configured yet.@endif
                </p>
                @if($pendingCountryRequest)
                    <p class="field-hint mt-2">A change request is pending review.</p>
                @endif
            </div>
        </div>
    </x-tenant::card>

    <x-tenant::card class="form-card">
        <div class="panel-head mb-5">
            <div>
                <h3 class="panel-title">Categories</h3>
                <p class="panel-copy">
                    @foreach($currentCategories as $category)
                        <span class="badge mr-1">{{ $category->translations->first()?->name ?? $category->id }}</span>
                    @endforeach
                    @if($currentCategories->isEmpty())No categories configured yet.@endif
                </p>
                @if($pendingCategoryRequest)
                    <p class="field-hint mt-2">A change request is pending review.</p>
                @endif
            </div>
        </div>
    </x-tenant::card>

    <x-tenant::modal id="country-request-modal" title="Request Target Countries Change">
        <x-tenant::form id="country-request-form" :action="route('tenant.settings.general.country-request')" method="POST" :validate="route('tenant.settings.general.country-request.validate')" success="close-modal">
            <x-tenant::select2
                name="requested_country_ids"
                label="Target Countries"
                required
                multiple
                :options="$allCountries->pluck('name', 'id')->all()"
                :value="$currentCountryIds"
            />

            <div class="page-actions compact-actions justify-end" style="margin-top:20px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>

    <x-tenant::modal id="category-request-modal" title="Request Categories Change">
        <x-tenant::form id="category-request-form" :action="route('tenant.settings.general.category-request')" method="POST" :validate="route('tenant.settings.general.category-request.validate')" success="close-modal">
            <x-tenant::select2
                name="requested_category_ids"
                label="Categories"
                required
                multiple
                tree
                :options="$allCategories->map(fn ($category) => ['value' => $category->id, 'label' => $category->translations->first()?->name ?? $category->id])->all()"
                :value="$currentCategoryIds"
            />

            <div class="page-actions compact-actions justify-end" style="margin-top:20px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/general.js')
@endpush
